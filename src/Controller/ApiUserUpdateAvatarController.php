<?php

namespace App\Controller;

use App\Controller\Common\CodeError;
use App\Controller\Common\AbstractApiController;
use App\Controller\Common\TokenAuthenticatedController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use App\Entity\UserAvatar;
use App\EventSubscriber\TokenSubscriber;
use App\Utils\UploadedBase64Image;
use App\Utils\Utils;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Error;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class ApiUserUpdateAvatarController extends AbstractApiController implements TokenAuthenticatedController
{
    public function __construct(private TokenSubscriber $tokenSubscriber)
    {
    }

    #[Route(
        '/api/user/update/avatar',
        name: 'app_api_user_update_avatar',
        methods: ['POST'],
        defaults: [
            '_api_resource_class' => UserAvatar::class,
            '_api_item_operations_name' => 'app_api_user_update_avatar',
        ],
    )]
    public function __invoke(
        #[CurrentUser] ?User $user,
        Request $request,
        ManagerRegistry $doctrine,
    ): Response {
        if (null === $user) {
            return $this->error(CodeError::USER_MISSING_CREDENTIALS, 'Missing credentials', Response::HTTP_UNAUTHORIZED);
        }

        $entityManager = $doctrine->getManager();

        $userAvatar = new UserAvatar();
        $userAvatar->mapFromArray($request->toArray());

        // mapping
        $request = $request->toArray();
        if ($userAvatar->getAvatar() !== null) {
            $avatar = $request['avatar'];

            $url =  null;

            if (trim($avatar) === '') {
                $this->removeImage($user, $entityManager);
            } else {
                $url = $this->saveImage($avatar, $user, $entityManager);
            }

            return $this->OK([
                'avatar' => $user->getAvatar(),
                'url' => $url
            ]);
        } else {
            return $this->error(
                CodeError::INVALID_REQUEST,
                'Invalide resquest',
                Response::HTTP_BAD_REQUEST
            );
        }
    }

    private function saveImage(
        string $url,
        User $user,
        ObjectManager $entityManager,
        $widthTarget = UploadedBase64Image::MAX_WIDTH,
        $heightTarget = UploadedBase64Image::MAX_HEIGHT
    ) {
        if (preg_match("!^data:image/(webp|png|gif|jpeg|avif);base64,.*!", $url)) {
            // save image 
            $image = new UploadedBase64Image($url, $this->getParameter('kernel.project_dir') . '/public');
            $path = "avatar/{$user->getId()}.webp";
            list($url, $size, $present) = $image->saveImage($widthTarget, $heightTarget, $path, true);

            if (!$present) {
                // save 
                $user->setAvatar(true);

                try {
                    $entityManager->persist($user);
                    $entityManager->flush();
                } catch (Error $e) {
                    // alleady exist, ignore this
                }
            }
        }

        return Utils::siteURL() . "/images/avatar/{$user->getId()}.webp";
    }

    private function removeImage(
        User $user,
        ObjectManager $entityManager
    ) {
        $removed = UploadedBase64Image::removeImage(
            $this->getParameter('kernel.project_dir') . "/public/images/avatar/{$user->getId()}.webp"
        );

        if ($removed) {
            // save 
            $user->setAvatar(false);

            try {
                $entityManager->persist($user);
                $entityManager->flush();
            } catch (Error $e) {
                // alleady exist, ignore this
            }
        }
    }
}
