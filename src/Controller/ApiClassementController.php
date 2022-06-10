<?php

namespace App\Controller;

use App\Entity\Classement;
use App\Entity\ClassementSubmit;
use App\Entity\File;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

#[AsController]
class ApiClassementController extends AbstractApiController implements TokenAuthenticatedController
{

    #[Route(
        '/api/classement/add',
        name: 'app_api_classement_add',
        methods: ['POST'],
        defaults: [
            '_api_resource_class' => ClassementSubmit::class,
            '_api_collection_operations_name' => 'post_publication',
        ],
    )]
    public function __invoke(Request $request, ManagerRegistry $doctrine, UserInterface $user): Response
    {
        if ($user instanceof User) {
            $entityManager = $doctrine->getManager();

            // mapping
            $classementSubmit = new ClassementSubmit();
            $classementSubmit->mapFromArray($request->toArray());

            // control db
            $userRep = $doctrine->getRepository(Classement::class);
            $classement = $classementSubmit->getRankingId() !== null
                ? $userRep->findOneBy(['User' => $user, 'rankingId' => $classementSubmit->getRankingId()])
                : null;

            if ($classement === null) { // if not exist create a new classement

                $date = (string) (new DateTimeImmutable())->getTimestamp();

                $templateId = sha1($user->getId() . 'template' . $date);
                $rankingId = sha1($user->getId() . 'ranking' . $date);

                $classement = new Classement();
                $classement->setUser($user);

                if ($classementSubmit->getTemplateId() === null) {
                    // add new template & ranking
                    $classement->setRankingId($rankingId);
                    $classementSubmit->setRankingId($rankingId);

                    $classement->setTemplateId($templateId);
                    $classementSubmit->setTemplateId($templateId);

                    $classement->setParent(true);
                } else {
                    // add new ranking (base on other template)
                    $classement->setTemplateId($classementSubmit->getTemplateId());

                    $classement->setRankingId($rankingId);
                    $classementSubmit->setRankingId($rankingId);

                    $classement->setParent(false);
                }
                $classement->setDateCreate(new DateTimeImmutable());
                $classement->setUser($user);
                $classement->setHide(false);
                $classement->setDeleted(false);
            } else {
                // update data
                $classement->setDateChange(new DateTimeImmutable());
                $classementSubmit->setTemplateId($classement->getTemplateId());
                $classementSubmit->setRankingId($classement->getRankingId());
            }

            // update image base64 to uri (save image ni files)
            $data = $classementSubmit->getData();
            if (!empty($data)) {
                if (!empty($data['groups']) && is_array($data['groups'])) {
                    foreach ($data['groups'] as &$group) {
                        $this->testImages($group['list'], $entityManager);
                    }
                }
                $this->testImages($data['list'], $entityManager);
            }
            $classementSubmit->setData($data);
            $classement->setData($data);

            // save banner
            $classementSubmit->setBanner($this->saveImage($classementSubmit->getBanner(), $entityManager));
            $classement->setBanner($classementSubmit->getBanner());

            // save other data
            $classement->setName($classementSubmit->getName());
            $classement->setGroupName($classementSubmit->getGroupName());

            try {
                //save db data
                $entityManager->persist($classement);
                $entityManager->flush();

                // return updated data
                return new JsonResponse(
                    [
                        'message' => $classementSubmit->toArray(),
                        'code' => Response::HTTP_OK,
                        'status' => 'OK'
                    ]
                );
            } catch (UniqueConstraintViolationException $ex) {
                return $this->error(CodeError::DUPLICATE_CONTENT, $ex->getMessage());
            }
        }
    }

    private function testImages(&$list, $entityManager)
    {
        if (!empty($list) && is_array($list)) {
            foreach ($list as &$item) {

                $item['url'] = $this->saveImage($item['url'], $entityManager);

                // remove unnecessary data
                unset($item['name']);
                unset($item['size']);
                unset($item['realSize']);
                unset($item['type']);
                unset($item['date']);
            }
        }
    }

    private function saveImage($url, $entityManager)
    {
        if (preg_match("!^data:image/(webp|png|gif|jpeg);base64,.*!", $url)) {
            // save image 
            $image = new UploadedBase64Image($url, $this->getParameter('kernel.project_dir'));
            list($url, $size, $present) = $image->saveImage();

            echo $present . ';';

            if (!$present) {
                // save 
                $file = new File();
                $file->setPath($url);
                $file->setSize($size);
                $file->setDate(new DateTimeImmutable());

                $entityManager->persist($file);
                $entityManager->flush();
            }
        }
        return $url;
    }
}
