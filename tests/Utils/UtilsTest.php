<?php

namespace App\Tests\Utils;

use App\Utils\Utils;
use PHPUnit\Framework\TestCase;

class UtilsTest extends TestCase
{
    private string $originalHost;
    private string $originalHttps;
    private string $originalPort;

    protected function setUp(): void
    {
        $this->originalHost  = $_SERVER['HTTP_HOST']    ?? '';
        $this->originalHttps = $_SERVER['HTTPS']        ?? '';
        $this->originalPort  = $_SERVER['SERVER_PORT']  ?? '';

        $_SERVER['HTTP_HOST']   = 'example.com';
        $_SERVER['SERVER_PORT'] = '80';
        unset($_SERVER['HTTPS']);
    }

    protected function tearDown(): void
    {
        $_SERVER['HTTP_HOST']   = $this->originalHost;
        $_SERVER['HTTPS']       = $this->originalHttps;
        $_SERVER['SERVER_PORT'] = $this->originalPort;
    }

    // ──────────────────────────── siteURL ────────────────────────────

    public function testSiteUrlReturnsHttpWhenNoHttps(): void
    {
        $url = Utils::siteURL();
        $this->assertStringStartsWith('http://', $url);
        $this->assertStringContainsString('example.com', $url);
    }

    public function testSiteUrlReturnsHttpsWhenHttpsIsOn(): void
    {
        $_SERVER['HTTPS'] = 'on';
        $url = Utils::siteURL();
        $this->assertStringStartsWith('https://', $url);
    }

    public function testSiteUrlReturnsHttpsWhenHttpsIsNotOff(): void
    {
        $_SERVER['HTTPS'] = '1'; // truthy, not 'off'
        $url = Utils::siteURL();
        $this->assertStringStartsWith('https://', $url);
    }

    public function testSiteUrlReturnsHttpWhenHttpsIsOff(): void
    {
        $_SERVER['HTTPS'] = 'off';
        $url = Utils::siteURL();
        $this->assertStringStartsWith('http://', $url);
    }

    public function testSiteUrlReturnsHttpsOnPort443(): void
    {
        unset($_SERVER['HTTPS']);
        $_SERVER['SERVER_PORT'] = '443';
        $url = Utils::siteURL();
        $this->assertStringStartsWith('https://', $url);
    }

    // ──────────────────────────── formatList ────────────────────────────

    public function testFormatListPrefixesRelativeUrls(): void
    {
        $list = [
            ['url' => '/images/foo.webp', 'name' => 'Foo'],
            ['url' => '/images/bar.webp', 'name' => 'Bar'],
        ];
        Utils::formatList($list);

        $base = Utils::siteURL();
        $this->assertStringStartsWith($base . '/images/', $list[0]['url']);
        $this->assertStringStartsWith($base . '/images/', $list[1]['url']);
    }

    public function testFormatListDoesNotTouchAbsoluteUrls(): void
    {
        $list = [['url' => 'https://cdn.example.com/img.jpg', 'name' => 'CDN']];
        Utils::formatList($list);

        $this->assertEquals('https://cdn.example.com/img.jpg', $list[0]['url']);
    }

    public function testFormatListDoesNotTouchEmptyUrl(): void
    {
        $list = [['url' => '', 'name' => 'Empty']];
        Utils::formatList($list);

        $this->assertEquals('', $list[0]['url']);
    }

    public function testFormatListIgnoresItemsWithoutUrl(): void
    {
        $list = [['name' => 'No URL']];
        Utils::formatList($list);

        $this->assertArrayNotHasKey('url', $list[0]);
    }

    public function testFormatListHandlesEmptyArray(): void
    {
        $list = [];
        Utils::formatList($list);
        $this->assertEmpty($list);
    }

    // ──────────────────────────── formatData ────────────────────────────

    public function testFormatDataPrefixesUrlsInList(): void
    {
        $data = [
            'options' => [],
            'groups'  => [],
            'list'    => [['url' => '/images/img.webp']],
        ];

        $result = Utils::formatData($data);
        $base   = Utils::siteURL();

        $this->assertStringStartsWith($base, $result['list'][0]['url']);
    }

    public function testFormatDataPrefixesUrlsInGroupsList(): void
    {
        $data = [
            'options' => [],
            'groups'  => [
                ['list' => [['url' => '/images/group-img.webp']]],
            ],
            'list' => [],
        ];

        $result = Utils::formatData($data);
        $base   = Utils::siteURL();

        $this->assertStringStartsWith($base, $result['groups'][0]['list'][0]['url']);
    }

    public function testFormatDataPrefixesImageBackgroundCustom(): void
    {
        $data = [
            'options' => ['imageBackgroundCustom' => '/images/bg.webp'],
            'groups'  => [],
            'list'    => [],
        ];

        $result = Utils::formatData($data);
        $base   = Utils::siteURL();

        $this->assertStringStartsWith($base, $result['options']['imageBackgroundCustom']);
    }

    public function testFormatDataDoesNotPrefixAbsoluteImageBackgroundCustom(): void
    {
        $data = [
            'options' => ['imageBackgroundCustom' => 'https://cdn.example.com/bg.webp'],
            'groups'  => [],
            'list'    => [],
        ];

        $result = Utils::formatData($data);

        $this->assertEquals(
            'https://cdn.example.com/bg.webp',
            $result['options']['imageBackgroundCustom']
        );
    }

    public function testFormatDataHandlesEmptyData(): void
    {
        $result = Utils::formatData([]);
        $this->assertEquals([], $result);
    }

    public function testFormatDataHandlesDataWithoutGroups(): void
    {
        $data = [
            'options' => [],
            'list'    => [['url' => '/images/img.webp']],
        ];

        $result = Utils::formatData($data);
        $base   = Utils::siteURL();

        $this->assertStringStartsWith($base, $result['list'][0]['url']);
    }

    public function testFormatDataDoesNotTouchAbsoluteUrlsInList(): void
    {
        $data = [
            'options' => [],
            'groups'  => [],
            'list'    => [['url' => 'https://cdn.example.com/img.jpg']],
        ];

        $result = Utils::formatData($data);

        $this->assertEquals('https://cdn.example.com/img.jpg', $result['list'][0]['url']);
    }

    public function testFormatDataHandlesMultipleGroups(): void
    {
        $data = [
            'options' => [],
            'groups'  => [
                ['list' => [['url' => '/images/a.webp'], ['url' => '/images/b.webp']]],
                ['list' => [['url' => '/images/c.webp']]],
            ],
            'list' => [],
        ];

        $result = Utils::formatData($data);
        $base   = Utils::siteURL();

        $this->assertStringStartsWith($base, $result['groups'][0]['list'][0]['url']);
        $this->assertStringStartsWith($base, $result['groups'][0]['list'][1]['url']);
        $this->assertStringStartsWith($base, $result['groups'][1]['list'][0]['url']);
    }
}
