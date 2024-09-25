<?php

namespace App\Utils;

use App\Entity\Tag;

interface WithTags
{
    function getTags();

    function addTag(Tag $tag);

    function removeTag(Tag $tag);
}
