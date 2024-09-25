<?php

namespace App\Utils;

use App\Entity\Tag;
use Doctrine\Persistence\ManagerRegistry;
use Error;

class TagsTools
{

    static function updateTags(ManagerRegistry $doctrine, array $data, WithTags $theme)
    {
        $entityManager = $doctrine->getManager();

        // save tags
        $tags = $theme->getTags();

        if (isset($data['options']['tags']) && !empty($data['options']['tags'])) {
            $tagRep = $doctrine->getRepository(Tag::class);

            $tagsData = $data['options']['tags'];

            $tagsCurrentArray = $tags->toArray();
            $tagsCurrent = array_map(function (Tag $tag): string {
                return $tag->getLabel();
            }, $tagsCurrentArray);

            // remove tags 
            if (!$tags?->isEmpty()) {
                foreach ($tagsCurrentArray as $tag) {
                    if (array_search($tag->getLabel(), $tagsData) === false) {
                        $theme->removeTag($tag);
                    }
                }
            }

            // add tags
            foreach ($tagsData as $tagName) {

                if (array_search($tagName, $tagsCurrent) === false) {

                    $tag = $tagRep->findOneBy(['label' => $tagName]);

                    if (!$tag) {
                        $tag = new Tag();
                        $tag->setLabel($tagName);

                        try {
                            $entityManager->persist($tag);
                            $entityManager->flush();

                            $theme->addTag($tag);
                        } catch (Error $e) {
                            // already exist, ignore this
                        }
                    }
                    if ($tag) {
                        $theme->addTag($tag);
                    }
                }
            }
        } else {
            $tags->clear();
        }
    }
}
