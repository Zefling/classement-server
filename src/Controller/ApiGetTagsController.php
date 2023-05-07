<?php

namespace App\Controller;

use App\Controller\Common\AbstractApiController;
use App\Entity\Tag;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
class ApiGetTagsController extends AbstractApiController
{

    #[Route(
        '/api/tags/{tag}',
        name: 'app_api_tags_search',
        methods: ['GET'],
        defaults: [
            '_api_resource_class' => Tag::class,
            '_api_item_operations_name' => 'app_api_tags_search',
        ],
    )]
    public function __invoke(string $tag, ManagerRegistry $doctrine): Response
    {
        $rep = $doctrine->getRepository(Tag::class);
        $tags = $rep->findByKeyLabel($tag);

        // return updated data
        return $this->OK(!empty($tags)
            ? array_map(function (Tag $tag): string {
                return $tag->getLabel();
            }, $tags)
            : []);
    }
}
