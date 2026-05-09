<?php

namespace App\Controller\Common;

use App\Entity\Classement;
use App\Entity\Theme;
use App\Service\EntityMapperService;
use Error;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\Attribute\Required;

class AbstractApiController extends AbstractController
{
    protected EntityMapperService $entityMapper;

    #[Required]
    public function setEntityMapper(EntityMapperService $entityMapper): void
    {
        $this->entityMapper = $entityMapper;
    }

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
        return $this->entityMapper->mapClassement($classement, $withStatus);
    }

    public function mapClassements(array &$classements, $withStatus = false): array
    {
        return $this->entityMapper->mapClassements($classements, $withStatus);
    }

    public function mapTheme(?Theme $theme, $withStatus = false): ?array
    {
        return $this->entityMapper->mapTheme($theme, $withStatus);
    }

    public function mapThemes(array &$themes, $withStatus = false): array
    {
        return $this->entityMapper->mapThemes($themes, $withStatus);
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
