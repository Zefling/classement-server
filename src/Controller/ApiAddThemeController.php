<?php

namespace App\Controller;

use App\Controller\Common\AbstractApiController;
use App\Controller\Common\CodeError;
use App\Controller\Common\TokenAuthenticatedController;
use App\Controller\Schema\JsonValidation;
use App\Controller\Schema\ThemeSchema;
use App\Entity\Theme;
use App\Entity\ThemeSubmit;
use App\Entity\File;
use App\Entity\Mode;
use App\Entity\User;
use App\Utils\TagsTools;
use App\Utils\UploadedBase64Image;
use App\Utils\Utils;
use DateTimeImmutable;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Error;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use ValueError;

#[AsController]
class ApiAddThemeController extends AbstractApiController implements TokenAuthenticatedController
{
    public ?ObjectManager $entityManager;

    public array $files = [];

    #[Route(
        '/api/theme',
        name: 'app_api_theme_add',
        methods: ['POST'],
        defaults: [
            '_api_resource_class' => ThemeSubmit::class,
            '_api_collection_operations_name' => 'post_publication',
        ],
    )]
    public function __invoke(
        #[CurrentUser] ?User $user,
        Request $request,
        ManagerRegistry $doctrine
    ): Response {
        if ($user instanceof User) {
            $this->entityManager = $doctrine->getManager();

            // mapping
            $themeSubmit = new ThemeSubmit();
            $themeSubmit->mapFromArray($request->toArray());

            // control db
            $userRep = $doctrine->getRepository(Theme::class);
            $theme = $themeSubmit->getThemeId() !== null
                ? $userRep->findOneBy(['User' => $user, 'themeId' => $themeSubmit->getThemeId()])
                : null;

            if ($theme === null) {
                // if not exist create a new theme
                $date = (string) (new DateTimeImmutable())->getTimestamp();
                $theme = new Theme();
                $theme->setDateCreate(new DateTimeImmutable());
                $theme->setThemeId(sha1($user->getId() . 'theme' . $date));
                $theme->setUser($user);
                $theme->setDeleted(false);
            } else {
                // update date
                $theme->setDateChange(new DateTimeImmutable());
                $themeSubmit->setDateChange($theme->getDateChange());
            }

            $theme->setHidden($themeSubmit->getHidden() ?? false);

            $themeSubmit->setThemeId($theme->getThemeId());
            $themeSubmit->setDateCreate($theme->getDateCreate());

            $data = $themeSubmit->getData();

            try {
                if (!(new JsonValidation())->isValid($data, ThemeSchema::$jsonSchema)) {
                    return $this->error(CodeError::INVALID_DATA, 'Invalid data');
                }
            } catch (Exception $ex) {
                return $this->error(CodeError::INVALID_DATA, 'Schema: ' . $ex->getMessage());
            }

            // update image base64 to uri (save image ni files)
            if (!empty($data['options']['imageBackgroundCustom'])) {
                $data['options']['imageBackgroundCustom'] =
                    $this->saveImage($data['options']['imageBackgroundCustom'], 1000, 1000);

                $this->files[] = $data['options']['imageBackgroundCustom'];
            }

            $themeSubmit->setData($data);
            $theme->setData($data);

            // save tags
            TagsTools::updateTags($doctrine, $data, $theme);

            // save other data
            $theme->setName($themeSubmit->getName());
            try {

                // mode
                $theme->setMode(Mode::from($themeSubmit->getMode()));

                // list of files
                $fileRep = $doctrine->getRepository(File::class);
                $files = !empty($this->files)
                    ? $fileRep->findBy(['path' => $this->files])
                    : null;

                $theme->getFiles()->clear();
                if ($files) {
                    foreach ($files as $file) {
                        $theme->addFile($file);
                    }
                }

                // save db data
                $this->entityManager->persist($theme);
                $this->entityManager->flush();

                // update links
                $themeSubmit->setData(Utils::formatData($theme->getData()));

                // return updated data
                return $this->OK($themeSubmit->toArray());
            } catch (UniqueConstraintViolationException $ex) {
                return $this->error(CodeError::DUPLICATE_CONTENT, $ex->getMessage());
            } catch (ValueError $ex) {
                return $this->error(CodeError::CATEGORY_ERROR, $ex->getMessage());
            }
        }
    }


    private function testImages(array &$list): int
    {
        $count = 0;

        if (!empty($list) && is_array($list)) {
            foreach ($list as &$item) {
                if (isset($item['url']) && !empty($item['url'])) {

                    $item['url'] = $this->saveImage($item['url']);
                    $this->files[] = $item['url'];

                    // remove unnecessary data
                    unset(
                        $item['name'],
                        $item['size'],
                        $item['realSize'],
                        $item['type'],
                        $item['date'],
                        $item['height'],
                        $item['width']
                    );
                }

                $count++;
            }
        }
        return $count;
    }

    private function saveImage(
        string $url,
        $widthTarget = UploadedBase64Image::MAX_WIDTH,
        $heightTarget = UploadedBase64Image::MAX_HEIGHT
    ) {
        if (preg_match("!^data:image/(webp|png|gif|jpeg|avif);base64,.*!", $url)) {
            // save image 
            $image = new UploadedBase64Image($url, $this->getParameter('kernel.project_dir') . '/public');
            list($url, $size, $present) = $image->saveImage($widthTarget, $heightTarget);

            if (!$present) {
                // save 
                $file = new File();
                $file->setPath($url);
                $file->setSize($size);
                $file->setDate(new DateTimeImmutable());

                try {
                    $this->entityManager->persist($file);
                    $this->entityManager->flush();
                } catch (Error $e) {
                    // already exist, ignore this
                }
            }
        } else if (str_starts_with($url, 'http')) {
            $url = str_replace(Utils::siteURL(), '', $url);
        }
        return $url;
    }
}
