<?php

namespace App\Controller;

use App\Entity\Classement;
use App\Entity\ClassementSubmit;
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

            // mapping
            $classementSubmit = new ClassementSubmit();
            $classementSubmit->mapFromArray($request->toArray());

            // control db
            $userRep = $doctrine->getRepository(Classement::class);
            $classement = $userRep->findOneBy(['User' => $user, 'rankingId' => $classementSubmit->getRankingId()]);

            if ($classement === null) { // if not exist create a new classement

                $date = (string) (new DateTimeImmutable())->getTimestamp();
                $templateId = sha1($user->getId() . 'template' . $date);
                $rankingId = sha1($user->getId() . 'ranking' . $date);

                $classement = new Classement();
                $classement->setUser($user);

                if ($classementSubmit->getTemplateId() === null) {
                    // add new template & ranking
                    $classement->setRankingId($rankingId);
                    $classement->setTemplateId($templateId);

                    $classementSubmit->setRankingId($rankingId);
                    $classementSubmit->setTemplateId($templateId);

                    $classement->setParent(true);
                } else if ($classementSubmit->getRankingId() === null) {
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
                $classementSubmit->setRankingId($classement->setRankingId());
            }

            // update image base64 to uri (save image ni files)
            $data = $classementSubmit->getData();
            if (!empty($data)) {
                if (!empty($data['groups']) && is_array($data['groups'])) {
                    foreach ($data['groups'] as &$group) {
                        $this->testImages($group['list']);
                    }
                }
                $this->testImages($data['list']);
            }
            $classementSubmit->setData($data);
            $classement->setData($data);

            // save banner
            $image = new UploadedBase64Image($classementSubmit->getBanner(), $this->getParameter('kernel.project_dir'));
            $classementSubmit->setBanner($image->saveImage());
            $classement->setBanner($classementSubmit->getBanner());

            // save other data
            $classement->setName($classementSubmit->getName());
            $classement->setGroupName($classementSubmit->getGroupName());

            try {
                //save db data
                $entityManager = $doctrine->getManager();
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

    private function testImages(&$list)
    {
        if (!empty($list) && is_array($list)) {
            foreach ($list as &$item) {
                if (preg_match("!^data:image/(webp|png|gif|jpeg);base64,.*!", $item['url'])) {

                    // save image 
                    $image = new UploadedBase64Image($item['url'], $this->getParameter('kernel.project_dir'));
                    $item['url'] = $image->saveImage();

                    // remove unnecessary data
                    unset($item['name']);
                    unset($item['size']);
                    unset($item['realSize']);
                    unset($item['type']);
                    unset($item['date']);
                }
            }
        }
    }
}
