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
class ApiTestLinkIdController extends AbstractApiController
{

    // required API Platform 3.x
    public static function getName(): string
    {
        return 'app_api_link_id_test';
    }

    public function __invoke(Request $request, ManagerRegistry $doctrine): Response
    {
        $array = $request->toArray();
        $test = false;
        $array['rankingId'] ??= '';

        if (isset($array['linkId'])) {
            $classement = $doctrine->getRepository(Classement::class)->findOneByLinkId($array['linkId']);
            $test = true;
        }

        return $test
            ? $this->json(
                $classement === null ||
                    $classement !== null &&
                    !(empty($array['rankingId']) || $classement->getRankingId() !== $array['rankingId'])
            )
            : $this->error(CodeError::INVALID_TEST, 'Test invalid');
    }
}
