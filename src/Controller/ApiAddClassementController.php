<?php

namespace App\Controller;

use App\Controller\Common\AbstractApiController;
use App\Controller\Common\CodeError;
use App\Controller\Common\TokenAuthenticatedController;
use App\Entity\Category;
use App\Entity\Classement;
use App\Entity\ClassementHistory;
use App\Entity\ClassementSubmit;
use App\Entity\File;
use App\Entity\Mode;
use App\Entity\Tag;
use App\Entity\User;
use App\Utils\UploadedBase64Image;
use App\Utils\Utils;
use DateTimeImmutable;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Error;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use ValueError;

#[AsController]
class ApiAddClassementController extends AbstractApiController implements TokenAuthenticatedController
{
    public ?ObjectManager $entityManager;

    public array $files = [];

    #[Route(
        '/api/classement',
        name: 'app_api_classement_add',
        methods: ['POST'],
        defaults: [
            '_api_resource_class' => ClassementSubmit::class,
            '_api_collection_operations_name' => 'post_publication',
        ],
    )]
    public function __invoke(
        #[CurrentUser] ?User $user,
        Request $request,
        ManagerRegistry $doctrine,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        if ($user instanceof User) {
            $this->entityManager = $doctrine->getManager();

            // mapping
            $classementSubmit = new ClassementSubmit();
            $classementSubmit->mapFromArray($request->toArray());

            // control db
            $userRep = $doctrine->getRepository(Classement::class);
            $classement = $classementSubmit->getRankingId() !== null
                ? $userRep->findOneBy(['User' => $user, 'rankingId' => $classementSubmit->getRankingId()])
                : null;

            $classementHistory = null;

            $new = true;

            if ($classement === null) { // if not exist create a new classement

                $date = (string) (new DateTimeImmutable())->getTimestamp();

                $templateId = sha1($user->getId() . 'template' . $date);
                $rankingId = sha1($user->getId() . 'ranking' . $date);

                $classement = new Classement();

                if ($classementSubmit->getLinkId() && trim($classementSubmit->getLinkId())) {
                    if ($userRep->findOneBy(['linkId' => trim($classementSubmit->getLinkId())]) !== null) {
                        return $this->error(CodeError::LINK_ID_DUPLICATE, "This link already exists.");
                    } else {
                        $classement->setLinkId(trim($classementSubmit->getLinkId()));
                    }
                }

                if ($classementSubmit->getTemplateId() === null) {
                    // add new template & ranking
                    $classement->setRankingId($rankingId);
                    $classement->setTemplateId($templateId);
                    $classement->setParent(true);
                } else {
                    // add new ranking (base on other template)
                    $classement->setTemplateId($classementSubmit->getTemplateId());
                    $classement->setRankingId($rankingId);
                    $classement->setParentId($classementSubmit->getParentId() ?? $classementSubmit->getTemplateId());
                    $classement->setParent(false);
                }

                $classement->setDateCreate(new DateTimeImmutable());
                $classement->setUser($user);
                $classement->setDeleted(false);
            } else {
                // history
                if ($classementSubmit->getHistory()) {
                    $classementHistory = new ClassementHistory($classement);
                }

                // update date
                $classement->setDateChange(new DateTimeImmutable());
                $classementSubmit->setDateChange($classement->getDateChange());

                if (
                    $classementSubmit->getLinkId() &&
                    trim($classementSubmit->getLinkId()) &&
                    trim($classementSubmit->getLinkId()) !== $classement->getLinkId()
                ) {
                    if ($userRep->findOneBy(['linkId' => trim($classementSubmit->getLinkId())]) !== null) {
                        return $this->error(CodeError::LINK_ID_DUPLICATE, "This link already exists. Edit impossible.");
                    } else {
                        $classement->setLinkId(trim($classementSubmit->getLinkId()));
                    }
                }

                $new = false;
            }

            $classement->setHidden($classementSubmit->getHidden() ?? false);

            $classementSubmit->setTemplateId($classement->getTemplateId());
            $classementSubmit->setRankingId($classement->getRankingId());
            $classementSubmit->setUser($user->getUsername());
            $classementSubmit->setDateCreate($classement->getDateCreate());

            if ($classementSubmit->getLocalId()) {
                $classement->setLocalId($classementSubmit->getLocalId());
            }

            // update hashed password only if value and private
            if (
                $classementSubmit->getHidden() &&
                !empty($classementSubmit->getPassword()) &&
                !empty(trim($classementSubmit->getPassword()))
            ) {
                $classement->setPassword($classementSubmit->getPassword());

                $hashedPassword = $passwordHasher->hashPassword(
                    $user,
                    $classementSubmit->getPassword()
                );
                $classement->setPassword($hashedPassword);
            }

            $countItems = 0;
            $countGroups = 0;

            // update image base64 to uri (save image ni files)
            $data = $classementSubmit->getData();
            if (!empty($data)) {
                if (!empty($data['groups']) && is_array($data['groups'])) {
                    foreach ($data['groups'] as &$group) {
                        if ($classementSubmit->getMode() !==  Mode::Teams->value) {
                            $countItems += $this->testImages($group['list']);
                        }
                        $countGroups++;
                    }
                }
                $countItems += $this->testImages($data['list']);

                if (!empty($data['options']['imageBackgroundCustom'])) {
                    $data['options']['imageBackgroundCustom'] =
                        $this->saveImage($data['options']['imageBackgroundCustom'], 1000, 1000);
                }
            }

            $classementSubmit->setData($data);
            $classement->setData($data);

            $classementSubmit->setTotalGroups($countGroups);
            $classement->setTotalGroups($countGroups);

            $classementSubmit->setTotalItems($countItems);
            $classement->setTotalItems($countItems);

            // save tags
            $this->updateTags($doctrine, $data, $classement);

            // save banner
            $classementSubmit->setBanner($this->saveImage($classementSubmit->getBanner()));
            $classement->setBanner($classementSubmit->getBanner());

            // save other data
            $classement->setName($classementSubmit->getName());
            try {

                // update category only if parent
                if (
                    $classement->getParent() &&
                    $classement->getCategory() !== Category::from($classementSubmit->getCategory())
                ) {
                    $userRep->updateCatagoryByTemplateId(
                        $classement->getTemplateId(),
                        Category::from($classementSubmit->getCategory())
                    );
                }
                $classement->setCategory(Category::from($classementSubmit->getCategory()));

                // mode
                $classement->setMode(Mode::from($classementSubmit->getMode()));

                // list of files
                $fileRep = $doctrine->getRepository(File::class);
                $files = !empty($this->files)
                    ? $fileRep->findBy(['path' => $this->files])
                    : null;

                $classement->getFiles()->clear();
                if ($files) {
                    foreach ($files as $file) {
                        $classement->addFile($file);
                    }
                }

                // save db data
                $this->entityManager->persist($classement);
                $this->entityManager->flush();

                // history 
                if (isset($classementHistory) && $classement->getDateChange()) {
                    $this->saveHistory($classementHistory);
                }

                // add total ranking by templateId
                $counts = $userRep->countByTemplateId([$classement->getTemplateId()]);
                if (isset($counts[$classement->getTemplateId()])) {
                    $classementSubmit->setTemplateTotal($counts[$classement->getTemplateId()]);
                }

                if (!$new) {
                    // add total history by rankingId
                    $histoRep = $doctrine->getRepository(ClassementHistory::class);
                    $counts = $histoRep->countByRankingId([$classement->getRankingId()]);
                    if (isset($counts[$classement->getRankingId()])) {
                        $classementSubmit->setWithHistory($counts[$classement->getRankingId()]);
                    }
                }

                // update links
                $classementSubmit->setData(Utils::formatData($classement->getData()));
                $classementSubmit->setBanner(Utils::siteURL() . $classement->getBanner());

                // remove password
                $classementSubmit->setPassword($classement->getPassword() ? 'true' : 'false');

                // return updated data
                return $this->OK($classementSubmit->toArray());
            } catch (UniqueConstraintViolationException $ex) {
                return $this->error(CodeError::DUPLICATE_CONTENT, $ex->getMessage());
            } catch (ValueError $ex) {
                return $this->error(CodeError::CATEGORY_ERROR, $ex->getMessage());
            }
        }
    }

    private function updateTags(ManagerRegistry $doctrine, array $data, Classement $classement)
    {
        // save tags
        $tags = $classement->getTags();

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
                        $classement->removeTag($tag);
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
                            $this->entityManager->persist($tag);
                            $this->entityManager->flush();

                            $classement->addTag($tag);
                        } catch (Error $e) {
                            // alleady exist, ignore this
                        }
                    }
                    if ($tag) {
                        $classement->addTag($tag);
                    }
                }
            }
        } else {
            $tags->clear();
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
                    // alleady exist, ignore this
                }
            }
        } else if (str_starts_with($url, 'http')) {
            $url = str_replace(Utils::siteURL(), '', $url);
        }
        return $url;
    }

    private function saveHistory(ClassementHistory $classement)
    {
        $this->entityManager->persist($classement);
        $this->entityManager->flush();
    }
}
