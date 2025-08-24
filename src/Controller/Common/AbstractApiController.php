<?php

namespace App\Controller\Common;

use App\Entity\Category;
use App\Utils\Utils;
use App\Entity\Classement;
use App\Entity\ClassementSubmit;
use App\Entity\Mode;
use App\Entity\Theme;
use App\Entity\ThemeSubmit;
use Error;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class AbstractApiController extends AbstractController
{
    // required API Platform 3.x
    public static function getPriority(): int
    {
        return 0; // default
    }

    public function error($code, $message, $codeHttp = Response::HTTP_BAD_REQUEST): Response
    {
        return $this->json(
            [
                'errorCode' => $code,
                'errorMessage' => $message,
                'status' => 'KO',
                'code' => $codeHttp
            ],
            $codeHttp
        );
    }

    public function OK($message = null): Response
    {
        return $message
            ? $this->json(
                [
                    'message' => $message,
                    'code' => Response::HTTP_OK,
                    'status' => 'OK'
                ],
                Response::HTTP_OK
            )
            : $this->json(
                [
                    'code' => Response::HTTP_OK,
                    'status' => 'OK'
                ],
                Response::HTTP_OK
            );
    }

    public function mapClassement(?Classement $classement, $withStatus = false): ?array
    {
        if (!$classement) {
            return null;
        }

        // mapping
        $classementSubmit = new ClassementSubmit();
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

        return  $classementSubmit->toArray();
    }

    public function mapClassements(array &$classements, $withStatus = false): array
    {
        $list = [];
        if (!empty($classements)) {
            foreach ($classements as $classement) {
                $list[] = $this->mapClassement($classement, $withStatus);
            }
        }

        return $list;
    }


    public function mapTheme(?Theme $theme, $withStatus = false): ?array
    {
        if (!$theme) {
            return null;
        }

        // mapping
        $themeSubmit = new ThemeSubmit();
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

        return  $themeSubmit->toArray();
    }


    public function mapThemes(array &$themes, $withStatus = false): array
    {
        $list = [];
        if (!empty($themes)) {
            foreach ($themes as $theme) {
                $list[] = $this->mapTheme($theme, $withStatus);
            }
        }

        return $list;
    }


    protected function formatDomain(string $url, ?string $domain = null): string
    {
        $list = explode(',', $this->getParameter('client.allow.domains'));
        $host = $domain;
        try {
            $host ??= isset($_SERVER['HTTP_REFERER'])
                ?  preg_replace('!https?://([^/]*)/?.*!', '$1', $_SERVER['HTTP_REFERER'])
                : '';
        } catch (Error $e) {
        }

        if (!in_array($host, $list)) {
            $host = $list[0];
        }
        return str_replace('%domain%', $host, $url);
    }
}
