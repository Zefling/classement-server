<?php

namespace App\Controller;

use App\Controller\Common\CodeError;
use App\Controller\Common\AbstractApiController;
use App\Entity\Classement;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class ApiGetCategoriesHomeController extends AbstractApiController
{

    // required API Platform 3.x
    public static function getName(): string
    {
        return 'app_api_group_home_get';
    }

    public function __invoke(Request $request, ManagerRegistry $doctrine): Response
    {
        $adult = $request->query->get('adult') === 'true';

        // control db
        $rep = $doctrine->getRepository(Classement::class);
        $classements = $rep->findByTemplateCategory($adult);

        if (!empty($classements)) {
            // for categories list
            $counts = $rep->countByCategories($adult);

            foreach ($classements as $classement) {
                if ($counts[$classement->getCategory()->value]) {
                    $classement->setTemplateTotal($counts[$classement->getCategory()->value]);
                }
            }
        }

        $classementSubmit = $this->mapClassements($classements);

        if ($classementSubmit !== null) {

            // return updated data
            return $this->OK($classementSubmit);
        } else {
            return $this->error(CodeError::CLASSEMENT_NOT_FOUND, 'Classement not found', Response::HTTP_NOT_FOUND);
        }
    }
}
