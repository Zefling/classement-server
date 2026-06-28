<?php

namespace App\Tests\Service;

use App\Entity\Classement;
use App\Entity\Theme;
use App\Entity\User;
use App\Enum\Category;
use App\Enum\Mode;
use App\Service\EntityMapperService;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * Tests for EntityMapperService — pure unit tests, no kernel/DB needed.
 */
class EntityMapperServiceTest extends TestCase
{
    private EntityMapperService $mapper;

    protected function setUp(): void
    {
        // Ensure siteURL() works in a CLI context
        $_SERVER['HTTP_HOST']   = 'localhost:8000';
        $_SERVER['SERVER_PORT'] = '80';
        unset($_SERVER['HTTPS']);

        $this->mapper = new EntityMapperService();
    }

    // ───────────────────────── helpers ─────────────────────────

    private function makeUser(
        int $id = 1,
        string $username = 'testuser',
        bool $avatar = false
    ): User {
        $user = new User($id);
        $user->setUsername($username);
        $user->setAvatar($avatar);
        return $user;
    }

    private function makeClassement(
        string $templateId = 'tpl-abc',
        string $rankingId  = 'rnk-abc',
        ?User  $user       = null
    ): Classement {
        $user ??= $this->makeUser();

        $classement = new Classement();
        $classement->setTemplateId($templateId);
        $classement->setRankingId($rankingId);
        $classement->setName('Test Classement');
        $classement->setCategory(Category::Movie);
        $classement->setMode(Mode::Default);
        $classement->setBanner('/images/banner.webp');
        $classement->setDateCreate(new DateTimeImmutable('2024-01-15'));
        $classement->setUser($user);
        $classement->setTotalGroups(2);
        $classement->setTotalItems(10);
        $classement->setHidden(false);
        $classement->setDeleted(false);
        $classement->setAdult(false);
        $classement->setParent(true);
        $classement->setData(['options' => [], 'groups' => [], 'list' => []]);

        return $classement;
    }

    private function makeTheme(?User $user = null): Theme
    {
        $user ??= $this->makeUser();

        $theme = new Theme();
        $theme->setThemeId('theme-abc');
        $theme->setName('Test Theme');
        $theme->setMode(Mode::Default);
        $theme->setDateCreate(new DateTimeImmutable('2024-01-15'));
        $theme->setUser($user);
        $theme->setHidden(false);
        $theme->setDeleted(false);
        $theme->setData(['options' => [], 'groups' => [], 'list' => []]);

        return $theme;
    }

    // ───────────────────── mapClassement — null ─────────────────────

    public function testMapClassementReturnsNullForNullInput(): void
    {
        $this->assertNull($this->mapper->mapClassement(null));
    }

    // ───────────────────── mapClassement — basic fields ─────────────────────

    public function testMapClassementReturnsBasicFields(): void
    {
        $classement = $this->makeClassement();
        $result = $this->mapper->mapClassement($classement);

        $this->assertIsArray($result);
        $this->assertEquals('tpl-abc', $result['templateId']);
        $this->assertEquals('rnk-abc', $result['rankingId']);
        $this->assertEquals('Test Classement', $result['name']);
        $this->assertEquals('testuser', $result['user']);
        $this->assertEquals(2, $result['totalGroups']);
        $this->assertEquals(10, $result['totalItems']);
        $this->assertEquals('movie', $result['category']);
        $this->assertEquals('default', $result['mode']);
        $this->assertFalse($result['adult']);
        $this->assertTrue($result['parent']);
    }

    public function testMapClassementBannerPrefixedWithSiteUrl(): void
    {
        $classement = $this->makeClassement();
        $result = $this->mapper->mapClassement($classement);

        $this->assertStringStartsWith('http://', $result['banner']);
        $this->assertStringContainsString('/images/banner.webp', $result['banner']);
    }

    // ───────────────────── mapClassement — withStatus ─────────────────────

    public function testMapClassementWithStatusIncludesHiddenDeletedPassword(): void
    {
        $classement = $this->makeClassement();
        $classement->setHidden(true);
        $classement->setDeleted(false);
        $classement->setPassword('hashed_password');

        $result = $this->mapper->mapClassement($classement, true);

        $this->assertTrue($result['hidden']);
        $this->assertFalse($result['deleted']);
        // password is set + hidden → 'true'
        $this->assertEquals('true', $result['password']);
    }

    public function testMapClassementWithStatusPasswordFalseWhenNotHidden(): void
    {
        $classement = $this->makeClassement();
        $classement->setHidden(false);
        $classement->setPassword('hashed_password');

        $result = $this->mapper->mapClassement($classement, true);

        // not hidden → password displayed as 'false'
        $this->assertEquals('false', $result['password']);
    }

    public function testMapClassementWithoutStatusHiddenIsNull(): void
    {
        $classement = $this->makeClassement();
        $result = $this->mapper->mapClassement($classement, false);

        // toArray() always includes all properties — without withStatus they are null
        $this->assertNull($result['hidden']);
        $this->assertNull($result['deleted']);
    }

    // ───────────────────── mapClassement — avatar ─────────────────────

    public function testMapClassementWithAvatarIncludesUserAvatar(): void
    {
        $user = $this->makeUser(42, 'avatar_user', true);
        $classement = $this->makeClassement('tpl', 'rnk', $user);

        $result = $this->mapper->mapClassement($classement);

        $this->assertArrayHasKey('userAvatar', $result);
        $this->assertStringContainsString('/images/avatar/42.webp', $result['userAvatar']);
    }

    public function testMapClassementWithoutAvatarUserAvatarIsNull(): void
    {
        $user = $this->makeUser(1, 'no_avatar', false);
        $classement = $this->makeClassement('tpl', 'rnk', $user);

        $result = $this->mapper->mapClassement($classement);

        // userAvatar is only set when user has avatar — otherwise null
        $this->assertNull($result['userAvatar']);
    }

    // ───────────────────── mapClassement — ids & dates ─────────────────────

    public function testMapClassementSetsOptionalIds(): void
    {
        $classement = $this->makeClassement();
        $classement->setParentId('parent-123');
        $classement->setLocalId('local-456');
        $classement->setLinkId('my-link');

        $result = $this->mapper->mapClassement($classement);

        $this->assertEquals('parent-123', $result['parentId']);
        $this->assertEquals('local-456', $result['localId']);
        $this->assertEquals('my-link', $result['linkId']);
    }

    // ───────────────────── mapClassements ─────────────────────

    public function testMapClassementsReturnsEmptyArrayForEmptyInput(): void
    {
        $this->assertSame([], $this->mapper->mapClassements([]));
    }

    public function testMapClassementsReturnsMappedArray(): void
    {
        $c1 = $this->makeClassement('tpl-1', 'rnk-1');
        $c2 = $this->makeClassement('tpl-2', 'rnk-2');

        $result = $this->mapper->mapClassements([$c1, $c2]);

        $this->assertCount(2, $result);
        $this->assertEquals('rnk-1', $result[0]['rankingId']);
        $this->assertEquals('rnk-2', $result[1]['rankingId']);
    }

    // ───────────────────── mapTheme — null ─────────────────────

    public function testMapThemeReturnsNullForNullInput(): void
    {
        $this->assertNull($this->mapper->mapTheme(null));
    }

    // ───────────────────── mapTheme — basic fields ─────────────────────

    public function testMapThemeReturnsBasicFields(): void
    {
        $theme = $this->makeTheme();
        $result = $this->mapper->mapTheme($theme);

        $this->assertIsArray($result);
        $this->assertEquals('theme-abc', $result['themeId']);
        $this->assertEquals('Test Theme', $result['name']);
        $this->assertEquals('testuser', $result['user']);
        $this->assertEquals('default', $result['mode']);
    }

    public function testMapThemeWithStatusIncludesHiddenDeleted(): void
    {
        $theme = $this->makeTheme();
        $theme->setHidden(true);
        $theme->setDeleted(false);

        $result = $this->mapper->mapTheme($theme, true);

        $this->assertTrue($result['hidden']);
        $this->assertFalse($result['deleted']);
    }

    public function testMapThemeWithoutStatusHiddenDeletedAreNull(): void
    {
        $theme = $this->makeTheme();
        $result = $this->mapper->mapTheme($theme, false);

        // toArray() always includes all properties — without withStatus they are null
        $this->assertNull($result['hidden']);
        $this->assertNull($result['deleted']);
    }

    // ───────────────────── mapThemes ─────────────────────

    public function testMapThemesReturnsEmptyArrayForEmptyInput(): void
    {
        $this->assertSame([], $this->mapper->mapThemes([]));
    }

    public function testMapThemesReturnsMappedArray(): void
    {
        $t1 = $this->makeTheme();
        $t1->setThemeId('theme-1');

        $t2 = $this->makeTheme();
        $t2->setThemeId('theme-2');

        $result = $this->mapper->mapThemes([$t1, $t2]);

        $this->assertCount(2, $result);
        $this->assertEquals('theme-1', $result[0]['themeId']);
        $this->assertEquals('theme-2', $result[1]['themeId']);
    }

    // ───────────────────── mode / category fallback ─────────────────────

    public function testMapClassementHandlesModeTeams(): void
    {
        $classement = $this->makeClassement();
        $classement->setMode(Mode::Teams);

        $result = $this->mapper->mapClassement($classement);

        $this->assertEquals('teams', $result['mode']);
    }

    public function testMapClassementHandlesCategoryAnime(): void
    {
        $classement = $this->makeClassement();
        $classement->setCategory(Category::Anime);

        $result = $this->mapper->mapClassement($classement);

        $this->assertEquals('anime', $result['category']);
    }
}
