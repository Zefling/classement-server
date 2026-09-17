<?php

namespace App\Controller\Schema;

/**
 * JSON Schema fragments shared by ClassementSchema and ThemeSchema.
 *
 * The schemas are written as nowdoc (<<<'JSON') and use tokens (@@COLOR@@, @@IMAGE_URL@@, ...) that are substituted 
 * once by self::expand() to avoid duplicating patterns (color regex, image URL regex, shared enumerations).
 */
class SchemaDefinitions
{
    /** Hex color pattern (#rgb, #rgba, #rrggbb, #rrggbbaa), empty or "transparent". */
    public const COLOR_PATTERN = '^(|#[0-9a-fA-F]{3,4}|#[0-9a-fA-F]{6}(?:[0-9a-fA-F]{2})?|transparent)$';

    /** Image URL pattern: empty, base64 data-URL, or local/API .webp file. */
    public const IMAGE_URL_PATTERN = '^(|data:image\\\\/.*|http:\\\\/\\\\/localhost:8000\\\\/images\\\\/.*\\\\.webp|https:\\\\/\\\\/api\\\\.classement\\\\.ikilote\\\\.net\\\\/images\\\\/.*\\\\.webp)$';

    /** Full fragment: string property validated as a color. */
    public const COLOR = '{ "type": "string", "pattern": "' . self::COLOR_PATTERN . '" }';

    /** Full fragment: string property validated as an image URL. */
    public const IMAGE_URL = '{ "type": "string", "pattern": "' . self::IMAGE_URL_PATTERN . '" }';

    /** Ranking mode enumeration. */
    public const MODE = '{ "enum": ["default", "teams", "columns", "iceberg", "axis", "bingo", "table"] }';

    /** Alignment enumeration (used for title positions, cells, etc.). */
    public const ALIGN = '{ "enum": ["start", "center", "end"] }';

    /** Item text position enumeration. */
    public const TEXT_POSITION = '{ "enum": ["hidden", "bottom", "bottom-over", "bottom-over-hover", "bottom-bubble", "top", "top-over", "top-over-hover", "top-bubble"] }';

    /** Predefined background image enumeration. */
    public const BACKGROUND_IMAGE = '{ "enum": ["none", "custom", "sakura", "etoile", "ciel", "iceberg", "axis"] }';

    /** Label background image size enumeration. */
    public const NAME_BG_IMAGE_SIZE = '{ "enum": ["auto", "cover", "contain", "50% auto", "75% auto", "100% auto", "125% auto", "150% auto", "50% 50%", "75% 75%", "100% 100%", "125% 125%", "150% 150%"] }';

    /**
     * Replace the @@...@@ tokens of a schema with their matching fragments.
     */
    public static function expand(string $schema): string
    {
        return strtr($schema, [
            '@@COLOR@@' => self::COLOR,
            '@@IMAGE_URL@@' => self::IMAGE_URL,
            '@@MODE@@' => self::MODE,
            '@@ALIGN@@' => self::ALIGN,
            '@@TEXT_POSITION@@' => self::TEXT_POSITION,
            '@@BACKGROUND_IMAGE@@' => self::BACKGROUND_IMAGE,
            '@@NAME_BG_IMAGE_SIZE@@' => self::NAME_BG_IMAGE_SIZE,
        ]);
    }
}
