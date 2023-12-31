<?php

namespace App\Controller;

use App\Entity\Classement;
use App\Entity\Url;
use App\Utils\Utils;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SitemapController extends AbstractController
{

    #[Route(
        '/sitemap.xml',
        name: 'site_map',
        //    defaults: ["format" => "xml"]
    )]
    public function sitemap(Request $request, ManagerRegistry $doctrine)
    {

        $limit = intval($request->query->get('limit'), 10);
        $limit = $limit ? $limit : 1000;
        $limit = min(max($limit, 1), 1000);

        $classements = $doctrine->getRepository(Classement::class)->findAllLast($limit);

        $urls = [];
        if (!empty($classements)) {
            foreach ($classements as $classement) {
                $urls[] = (new Url(
                    $this->getParameter('client.url.sitemap') . 'navigate/view/' .
                        ($classement->getLinkId() ? $classement->getLinkId() : $classement->getRankingId())
                ))
                    ->setLastmod($classement->getDateCreate() ?? $classement->getDateCreate())
                    ->setImage(Utils::siteURL() . $classement->getBanner(), $classement->getName());
            }
        }

        // return updated data
        return $this->render('sitemap.html.twig', [
            'urls' => $urls,
        ]);
    }
}
