<?php

namespace App\Service;

use App\Dto\ClassementSubmitDto;
use App\Dto\ThemeSubmitDto;
use App\Entity\Classement;
use App\Entity\Theme;
use App\Enum\Category;
use App\Enum\Mode;
use App\Utils\Utils;
use Error;

class EntityMapperService
{
    public function mapClassement(?Classement $classement, bool $withStatus = false): ?array
    {
        if (!$classement) {
            return null;
        }

        // mapping
        $classementSubmit = new ClassementSubmitDto();
        $classementSubmit
            ->setTemplateId($classement->getTemplateId())
            ->setRankingId($classement->getRankingId())
            ->setParentId($classement->getParentId())
            ->setLocalId($classement->getLocalId())
            ->setLinkId($classement->getLinkId())
            ->setData(Utils::formatData($classement->getData()))
            ->setBanner(Utils::siteURL() . $classement->getBanner())
            ->setName($classement->getName())
            ->setDateCreate($classement->getDateCreate())
            ->setDateChange($classement->getDateChange())
            ->setUser($classement->getUser()->getUsername())
            ->setTotalGroups($classement->getTotalGroups())
            ->setTotalItems($classement->getTotalItems())
            ->setTemplateTotal($classement->getTemplateTotal())
            ->setWithHistory($classement->getWithHistory())
            ->setAdult($classement->getAdult())
            ->setParent($classement->getParent());

        if ($classement->getUser()->getAvatar()) {
            $classementSubmit->setUserAvatar(Utils::siteURL() . "/images/avatar/{$classement->getUser()->getId()}.webp");
        }

        if ($withStatus) {
            $classementSubmit
                ->setHidden($classement->getHidden())
                ->setDeleted($classement->getDeleted())
                ->setPassword($classement->getHidden() && $classement->getPassword() ? 'true' : 'false');
        }

        try {
            $classementSubmit->setCategory($classement->getCategory()->value);
        } catch (Error $e) {
            $classementSubmit->setCategory(Category::Other->value);
        }

        try {
            $classementSubmit->setMode($classement->getMode()->value);
        } catch (Error $e) {
            $classementSubmit->setMode(Mode::Default->value);
        }

        return $classementSubmit->toArray();
    }

    public function mapClassements(array $classements, bool $withStatus = false): array
    {
        $list = [];
        if (!empty($classements)) {
            foreach ($classements as $classement) {
                $list[] = $this->mapClassement($classement, $withStatus);
            }
        }

        return $list;
    }

    public function mapTheme(?Theme $theme, bool $withStatus = false): ?array
    {
        if (!$theme) {
            return null;
        }

        // mapping
        $themeSubmit = new ThemeSubmitDto();
        $themeSubmit
            ->setThemeId($theme->getThemeId())
            ->setData(Utils::formatData($theme->getData()))
            ->setName($theme->getName())
            ->setDateCreate($theme->getDateCreate())
            ->setDateChange($theme->getDateChange())
            ->setUser($theme->getUser()->getUsername());

        try {
            $themeSubmit->setMode($theme->getMode()->value);
        } catch (Error $e) {
            $themeSubmit->setMode(Mode::Default->value);
        }

        if ($withStatus) {
            $themeSubmit
                ->setHidden($theme->getHidden())
                ->setDeleted($theme->getDeleted());
        }

        return $themeSubmit->toArray();
    }

    public function mapThemes(array $themes, bool $withStatus = false): array
    {
        $list = [];
        if (!empty($themes)) {
            foreach ($themes as $theme) {
                $list[] = $this->mapTheme($theme, $withStatus);
            }
        }

        return $list;
    }
}
