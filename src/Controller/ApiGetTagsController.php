<?php

namespace App\Controller;

use App\Controller\Common\AbstractApiController;
use App\Entity\Tag;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class ApiGetTagsController extends AbstractApiController
{

    // required API Platform 3.x
    public static function getName(): string
    {
        return 'app_api_tags_search';
    }

    public function __invoke(string $tag, ManagerRegistry $doctrine): Response
    {
        $rep = $doctrine->getRepository(Tag::class);
        $tags = $rep->findByLabel($tag);

        // return updated data
        return $this->OK(!empty($tags)
            ? array_map(function (Tag $tag): string {
                return $tag->getLabel();
            }, $tags)
            : []);
    }
}
