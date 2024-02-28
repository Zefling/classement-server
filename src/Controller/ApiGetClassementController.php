<?php

namespace App\Controller;

use App\Controller\Common\CodeError;
use App\Controller\Common\AbstractApiController;
use App\Entity\Classement;
use App\Entity\ClassementHistory;
use App\Entity\ClassementSubmit;
use App\Utils\Utils;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
class ApiGetClassementController extends AbstractApiController
{

    #[Route(
        '/api/classement/{id}',
        name: 'app_api_classement_get',
        methods: ['GET'],
        defaults: [
            '_api_resource_class' => ClassementSubmit::class,
            '_api_item_operations_name' => 'get_publication',
        ],
    )]
    public function __invoke(
        string $id,
        ManagerRegistry $doctrine,
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {

        $idHistory = $request->query->get('history') ?? null;
        $classementHistory = null;

        // control db
        $rep = $doctrine->getRepository(Classement::class);
        $classement = $rep->findByIdOrlinkName($id);

        // test history
        $repHist = $doctrine->getRepository(ClassementHistory::class);
        $classementHistory =  $repHist->findOneBy([
            'rankingId' => $classement->getRankingId(),
            'id' => $idHistory,
            'deleted' => false
        ]);

        if ($classement !== null) {

            // test if password required
            if ($classement->getHidden() && !empty($classement->getPassword())) {
                $password = $request->headers->get('X-PASSWORD');

                if (
                    empty($password) ||
                    !$passwordHasher->isPasswordValid(
                        $classement,
                        $password
                    )
                ) {
                    return $this->error(
                        CodeError::CLASSEMENT_PASSWORD_REQUIRED,
                        'Classement valid password required',
                        Response::HTTP_UNAUTHORIZED
                    );
                }
            }

            // add total ranking by template
            $counts = $rep->countByTemplateId([$classement->getTemplateId()]);
            if (isset($counts[$classement->getTemplateId()])) {
                $classement->setTemplateTotal($counts[$classement->getTemplateId()]);
            }

            $classementSubmit = $this->mapClassement($classement, true);
            unset($classementSubmit['deleted']);
            $classementSubmit['withHistory'] = $classementHistory !== null ? 1 : 0;

            if ($idHistory !== null && $classementHistory !== null) {
                // mapping
                $classementSubmit['data']        = Utils::formatData($classementHistory->getData());
                $classementSubmit['banner']      = Utils::siteURL() . $classementHistory->getBanner();
                $classementSubmit['name']        = $classementHistory->getName();
                $classementSubmit['totalGroups'] = $classementHistory->getTotalGroups();
                $classementSubmit['totalItems']  = $classementHistory->getTotalItems();
                $classementSubmit['historyId']   = $classementHistory->getId();

                if ($classementSubmit['dateCreate'] !== $classementHistory->getDate()) {
                    $classementSubmit['dateChange'] = $classementHistory->getDate();
                }
            }

            // return updated data
            return $this->OK($classementSubmit);
        } else {
            return $this->error(CodeError::CLASSEMENT_NOT_FOUND, 'Classement not found', Response::HTTP_NOT_FOUND);
        }
    }
}
