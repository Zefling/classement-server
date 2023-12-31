<?php

namespace App\Entity;

use DateTime;
use DateTimeInterface;

class SiteMapImage
{
    public function __construct(
        public string $loc,
        public ?string $title
    ) {
    }
}

class Url
{

    public ?string $lastmod;
    public ?string $changefreq;
    public ?string $priority;
    public ?SiteMapImage $image;

    public function __construct(public string $loc)
    {
    }

    function setLastmod(DateTimeInterface $date): Url
    {
        $this->lastmod = $date->format('Y-m-d');
        return $this;
    }

    function setImage(string $loc, ?string $title)
    {
        $this->image = new SiteMapImage($loc, $title);
        return $this;
    }
}
