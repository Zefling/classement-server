<?php

namespace App\Controller\Schema;

class ThemeSchema
{
    public static $jsonSchema = <<<'JSON'
{
    "type": "array",
    "properties": {
        "id": { "type": "string", "maxLength": 100 },
        "name": { "type": "string", "maxLength": 100 },
        "options": {
            "type": "array",
            "properties": {
                "mode": { "enum": ["default", "teams", "columns", "iceberg", "axis", "bingo"] },
                "groups": {
                    "type": "array",
                    "items": {
                        "type": "array",
                        "properties": {
                            "title": { "type": "string", "maxLength": 100 },
                            "titleVerticalPosition": { "enum": ["start", "center", "end"] },
                            "titleHorizontalPosition": { "enum": ["start", "center", "end"] }
                        },
                        "additionalProperties": false
                    }
                },
                "titleTextColor": { "type": "string", "pattern": "^(|#[0-9a-fA-F]{3,4}|#[0-9a-fA-F]{6}(?:[0-9a-fA-F]{2})?|transparent)$" },
                "titleTextOpacity": { "type": "number", "minimum": 0, "maximum": 100, "multipleOf": 1 },
                "itemWidth": { "type": "number", "minimum": 16, "maximum": 300, "multipleOf": 1 },
                "itemWidthAuto": { "type": "boolean" },
                "itemImageCover": { "type": "boolean" },
                "itemMaxWidth": { "type": "number", "minimum": 16, "maximum": 300, "multipleOf": 1 },
                "itemHeight": { "type": "number", "minimum": 16, "maximum": 300, "multipleOf": 1 },
                "itemHeightAuto": { "type": "boolean" },
                "itemMaxHeight": { "type": "number", "minimum": 16, "maximum": 300, "multipleOf": 1 },
                "itemPadding": { "type": "number", "minimum": 0, "maximum": 20, "multipleOf": 1 },
                "itemBorder": { "type": "number", "minimum": 0, "maximum": 20, "multipleOf": 1 },
                "itemMargin": { "type": "number", "minimum": 0, "maximum": 20, "multipleOf": 1 },
                "itemBackgroundColor": { "type": "string", "pattern": "^(|#[0-9a-fA-F]{3,4}|#[0-9a-fA-F]{6}(?:[0-9a-fA-F]{2})?|transparent)$" },
                "itemBorderColor": { "type": "string", "pattern": "^(|#[0-9a-fA-F]{3,4}|#[0-9a-fA-F]{6}(?:[0-9a-fA-F]{2})?|transparent)$" },
                "itemBackgroundOpacity": { "type": "number", "minimum": 0, "maximum": 100, "multipleOf": 1 },
                "itemBorderOpacity": { "type": "number", "minimum": 0, "maximum": 100, "multipleOf": 1 },
                "itemTextMinLine": { "type": "number", "minimum": 0, "maximum": 10, "multipleOf": 1 },
                "itemTextMaxLine": { "type": "number", "minimum": 0, "maximum": 10, "multipleOf": 1 },
                "itemTextSize": { "type": "number", "minimum": 6, "maximum": 100, "multipleOf": 1 },
                "itemTextOnlySize": { "type": "number", "minimum": 6, "maximum": 100, "multipleOf": 1 },
                "itemTextColor": { "type": "string" },
                "itemTextOpacity": { "type": "number", "minimum": 0, "maximum": 100, "multipleOf": 1 },
                "itemTextPosition": {
                    "enum": [
                        "hidden",
                        "bottom",
                        "bottom-over",
                        "bottom-over-hover",
                        "bottom-bubble",
                        "top",
                        "top-over",
                        "top-over-hover",
                        "top-bubble"
                    ]
                },
                "itemTextBackgroundColor": { "type": "string", "pattern": "^(|#[0-9a-fA-F]{3,4}|#[0-9a-fA-F]{6}(?:[0-9a-fA-F]{2})?|transparent)$" },
                "itemTextBackgroundOpacity": { "type": "number", "minimum": 0, "maximum": 100, "multipleOf": 1 },
                "lineBackgroundColor": { "type": "string", "pattern": "^(|#[0-9a-fA-F]{3,4}|#[0-9a-fA-F]{6}(?:[0-9a-fA-F]{2})?|transparent)$" },
                "lineBorderColor": { "type": "string", "pattern": "^(|#[0-9a-fA-F]{3,4}|#[0-9a-fA-F]{6}(?:[0-9a-fA-F]{2})?|transparent)$" },
                "lineBackgroundOpacity": { "type": "number", "minimum": 0, "maximum": 100, "multipleOf": 1 },
                "lineBorderOpacity": { "type": "number", "minimum": 0, "maximum": 100, "multipleOf": 1 },
                "imageBackgroundColor": { "type": "string", "pattern": "^(|#[0-9a-fA-F]{3,4}|#[0-9a-fA-F]{6}(?:[0-9a-fA-F]{2})?|transparent)$" },
                "imageBackgroundImage": { "enum": ["none", "custom", "sakura", "etoile", "ciel", "iceberg", "axis"] },
                "imageBackgroundCustom": {
                    "type": "string",
                    "pattern": "^(|data:image\\/.*|http:\\/\\/localhost:8000\\/images\\/.*\\.webp|https:\\/\\/api\\.classement\\.ikilote\\.net\\/images\\/.*\\.webp)$"
                },
                "imageWidth": { "type": "number", "minimum": 100, "maximum": 4000, "multipleOf": 1 },
                "imageHeight": { "type": "number", "minimum": 100, "maximum": 4000, "multipleOf": 1 },
                "imageSize": { "enum": ["", "cover"] },
                "imagePosition": { "enum": ["", "center"] },
                "columnMinHeight": { "type": "number", "minimum": 0, "maximum": 4000, "multipleOf": 1 },
                "axisLineWidth": { "type": "number", "minimum": 0, "maximum": 12, "multipleOf": 1 },
                "axisLineColor": { "type": "string", "pattern": "^(|#[0-9a-fA-F]{3,4}|#[0-9a-fA-F]{6}(?:[0-9a-fA-F]{2})?|transparent)$" },
                "axisLineOpacity": { "type": "number", "minimum": 0, "maximum": 100, "multipleOf": 1 },
                "axisArrowWidth": { "type": "number", "minimum": 0, "maximum": 50, "multipleOf": 1 },
                "nameWidth": { "type": "number", "minimum": 50, "maximum": 300, "multipleOf": 1 },
                "nameMinHeight": { "type": "number", "minimum": 0, "maximum": 300, "multipleOf": 1 },
                "nameFontSize": { "type": "number", "minimum": 50, "maximum": 300, "multipleOf": 1 },
                "nameBackgroundOpacity": { "type": "number", "minimum": 0, "maximum": 100, "multipleOf": 1 },
                "nameMarkdown": { "type": "boolean" },
                "borderRadius": { "type": "number", "minimum": 0, "maximum": 50, "multipleOf": 1 },
                "borderSpacing": { "type": "number", "minimum": -1, "maximum": 20, "multipleOf": 1 },
                "borderSize": { "type": "number", "minimum": 0, "maximum": 20, "multipleOf": 1 },
                "groupLineSize": { "type": "number", "minimum": 0, "maximum": 50, "multipleOf": 1 },
                "groupLineColor": { "type": "string", "pattern": "^(|#[0-9a-fA-F]{3,4}|#[0-9a-fA-F]{6}(?:[0-9a-fA-F]{2})?|transparent)$" },
                "groupLineOpacity": { "type": "number", "minimum": 0, "maximum": 100, "multipleOf": 1 },
                "direction": { "enum": ["ltr", "rtl"] },
                "sizeX": { "type": "number", "minimum": 2, "maximum": 20, "multipleOf": 1 },
                "sizeY": { "type": "number", "minimum": 2, "maximum": 20, "multipleOf": 1 },
                "font": { "type": "string", "pattern": "|^[A-Za-z0-9 ]{1,50}$" }
            }
        }
    },
    "additionalProperties": false,
    "required": ["id", "name", "options"]
}
JSON;
}
