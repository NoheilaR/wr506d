<?php

namespace App\Service;

use Symfony\Component\String\Slugger\AsciiSlugger;

class SlugifyService
{
    private AsciiSlugger $slugger;

    public function __construct()
    {
        $this->slugger = new AsciiSlugger();
    }

    public function slugify(string $text): string
    {
        return strtolower($this->slugger->slug($text));
    }
}
