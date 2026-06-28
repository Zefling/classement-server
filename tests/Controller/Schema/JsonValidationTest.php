<?php

namespace App\Tests\Controller\Schema;

use App\Controller\Schema\ClassementSchema;
use App\Controller\Schema\JsonValidation;
use Exception;
use PHPUnit\Framework\TestCase;

/**
 * Tests for JsonValidation — pure unit tests, no kernel needed.
 */
class JsonValidationTest extends TestCase
{
    private JsonValidation $validator;

    /** Minimal valid classement data payload */
    private function validData(): array
    {
        return [
            'options' => ['title' => 'Test'], // must be a JSON object, not empty array
            'groups'  => [],
            'list'    => [],
        ];
    }

    protected function setUp(): void
    {
        $this->validator = new JsonValidation();
    }

    // ───────────────────────── valid cases ─────────────────────────

    public function testValidMinimalDataPasses(): void
    {
        $this->assertTrue(
            $this->validator->isValid($this->validData(), ClassementSchema::$jsonSchema)
        );
    }

    public function testValidDataWithListItemPasses(): void
    {
        $data = $this->validData();
        $data['list'][] = ['id' => 'item-1', 'url' => '', 'name' => 'My Item'];

        $this->assertTrue(
            $this->validator->isValid($data, ClassementSchema::$jsonSchema)
        );
    }

    public function testValidDataWithGroupPasses(): void
    {
        $data = $this->validData();
        $data['groups'][] = [
            'bgColor'  => '#ff0000',
            'txtColor' => '#ffffff',
            'list'     => [],
        ];

        $this->assertTrue(
            $this->validator->isValid($data, ClassementSchema::$jsonSchema)
        );
    }

    public function testValidDataWithAllOptionsFieldsPasses(): void
    {
        $data = $this->validData();
        $data['options'] = [
            'title'       => 'My Ranking',
            'category'    => 'movie',
            'description' => 'A great ranking',
            'tags'        => ['tag1', 'tag2'],
            'mode'        => 'default',
            'itemWidth'   => 100,
            'itemHeight'  => 100,
        ];

        $this->assertTrue(
            $this->validator->isValid($data, ClassementSchema::$jsonSchema)
        );
    }

    // ───────────────────────── invalid cases ─────────────────────────

    public function testMissingRequiredFieldThrowsException(): void
    {
        $this->expectException(Exception::class);

        // 'groups' is required but missing
        $this->validator->isValid(
            ['options' => [], 'list' => []],
            ClassementSchema::$jsonSchema
        );
    }

    public function testAdditionalPropertyThrowsException(): void
    {
        $this->expectException(Exception::class);

        $data = $this->validData();
        $data['unknownField'] = 'should not be here';

        $this->validator->isValid($data, ClassementSchema::$jsonSchema);
    }

    public function testNameTooLongThrowsException(): void
    {
        $this->expectException(Exception::class);

        $data = $this->validData();
        $data['name'] = str_repeat('a', 201); // maxLength is 200

        $this->validator->isValid($data, ClassementSchema::$jsonSchema);
    }

    public function testGroupMissingBgColorThrowsException(): void
    {
        $this->expectException(Exception::class);

        $data = $this->validData();
        $data['groups'][] = [
            // bgColor missing — it is required
            'txtColor' => '#ffffff',
            'list'     => [],
        ];

        $this->validator->isValid($data, ClassementSchema::$jsonSchema);
    }

    public function testGroupAdditionalPropertyThrowsException(): void
    {
        $this->expectException(Exception::class);

        $data = $this->validData();
        $data['groups'][] = [
            'bgColor'  => '#ff0000',
            'txtColor' => '#ffffff',
            'list'     => [],
            'extra'    => 'not allowed',
        ];

        $this->validator->isValid($data, ClassementSchema::$jsonSchema);
    }

    public function testItemAdditionalPropertyThrowsException(): void
    {
        $this->expectException(Exception::class);

        $data = $this->validData();
        $data['list'][] = ['id' => 'x', 'extra_field' => 'bad'];

        $this->validator->isValid($data, ClassementSchema::$jsonSchema);
    }

    public function testInvalidColorPatternThrowsException(): void
    {
        $this->expectException(Exception::class);

        $data = $this->validData();
        $data['groups'][] = [
            'bgColor'  => 'not-a-color',
            'txtColor' => '#ffffff',
            'list'     => [],
        ];

        $this->validator->isValid($data, ClassementSchema::$jsonSchema);
    }

    public function testInvalidModeThrowsException(): void
    {
        $this->expectException(Exception::class);

        $data = $this->validData();
        $data['options']['mode'] = 'invalid-mode';

        $this->validator->isValid($data, ClassementSchema::$jsonSchema);
    }

    public function testItemWidthBelowMinThrowsException(): void
    {
        $this->expectException(Exception::class);

        $data = $this->validData();
        $data['options']['itemWidth'] = 5; // minimum is 16

        $this->validator->isValid($data, ClassementSchema::$jsonSchema);
    }

    public function testItemWidthAboveMaxThrowsException(): void
    {
        $this->expectException(Exception::class);

        $data = $this->validData();
        $data['options']['itemWidth'] = 500; // maximum is 300

        $this->validator->isValid($data, ClassementSchema::$jsonSchema);
    }

    // ───────────────────────── property-based cases ─────────────────────────

    /**
     * @dataProvider validModeProvider
     */
    public function testAllValidModesPass(string $mode): void
    {
        $data = $this->validData();
        $data['options']['mode'] = $mode;

        $this->assertTrue(
            $this->validator->isValid($data, ClassementSchema::$jsonSchema)
        );
    }

    public static function validModeProvider(): array
    {
        return [
            ['default'],
            ['teams'],
            ['columns'],
            ['iceberg'],
            ['axis'],
            ['bingo'],
        ];
    }

    /**
     * @dataProvider validColorProvider
     */
    public function testAllValidColorFormatsPass(string $color): void
    {
        $data = $this->validData();
        $data['groups'][] = [
            'bgColor'  => $color,
            'txtColor' => $color,
            'list'     => [],
        ];

        $this->assertTrue(
            $this->validator->isValid($data, ClassementSchema::$jsonSchema)
        );
    }

    public static function validColorProvider(): array
    {
        return [
            [''],            // empty string — allowed
            ['#fff'],
            ['#ffff'],
            ['#ffffff'],
            ['#ffffffff'],
            ['transparent'],
        ];
    }
}
