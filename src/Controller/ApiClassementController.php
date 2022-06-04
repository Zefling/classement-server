<?php

namespace App\Controller;

use App\Entity\Classement;
use App\Entity\ClassementSubmit;
use App\Entity\User;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Util\Json;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[AsController]
class ApiClassementController extends AbstractApiController implements TokenAuthenticatedController
{

    #[Route(
        '/api/classement/add',
        name: 'app_api_classement_add',
        methods: ['POST'],
        defaults: [
            '_api_resource_class' => ClassementSubmit::class,
            '_api_collection_operationsname' => 'post_publication',
        ],
    )]
    public function __invoke(Request $request, ManagerRegistry $doctrine, UserInterface $user): Response
    {
        if ($user instanceof User) {

            $classementSubmit = new ClassementSubmit();
            $classementSubmit->mapFromArray($request->toArray());

            $userRep = $doctrine->getRepository(Classement::class);
            $classement = $userRep->findOneBy(['User' => $user, 'rankingId' => $classementSubmit->getRankingId()]);

            if ($classement === null) {
                $date = (string) (new DateTime())->getTimestamp();
                $templateId = sha1($user->getId() . 'template' . $date);
                $rankingId = sha1($user->getId() . 'ranking' . $date);

                $classement = new Classement();
                $classement->setUser($user);
                $classement->setTemplateId($templateId);
                if ($classementSubmit->getTemplateMode() === true) {
                    $classement->setRankingId('');
                } else {
                    $classement->setRankingId($rankingId);
                }
                $classement->setDateCreate(new DateTime());
            } else {
                $classement->setDateChange(new DateTime());
            }

            $data = $classementSubmit->getData();

            if (!empty($data)) {

                if (!empty($data['groups']) && is_array($data['groups'])) {
                    foreach ($data['groups'] as &$group) {
                        $this->testImages($group['list']);
                    }
                }
                $this->testImages($data['list']);
            }


            die();

            return new JsonResponse(
                [
                    'message' => $classementSubmit->toArray(),
                    'code' => Response::HTTP_OK,
                    'status' => 'OK'
                ]
            );
        }
    }

    private function testImages($list)
    {
        if (!empty($list) && is_array($list)) {
            foreach ($list as &$item) {
                if (preg_match("!^data:image/(webp|png|gif|jpeg);base64,.*!", $item['url'])) {
                    $image = new UploadedBase64Image($item['url'], $this->getParameter('kernel.project_dir'));
                    $item['url'] = $image->saveImage();
                }
            }
        }
    }
}
