<?php

namespace App\Controller\Schema;

class ThemeSchema
{
    public static $jsonSchema = <<<'JSON'
{
    "type": "object",
    "properties": {
        "id": { "type": "string", "maxLength": 100 },
        "name": { "type": "string", "maxLength": 100 },
        "options": {
            "type": "object",
            "properties": {
                "mode": @@MODE@@,
                "groups": {
                    "type": "array",
                    "items": {
                        "type": "object",
                        "properties": {
                            "title": { "type": "string", "maxLength": 100 },
                            "titleVerticalPosition": @@ALIGN@@,
                            "titleHorizontalPosition": @@ALIGN@@
                        },
                        "additionalProperties": false
                    }
                },
                "col": {
                    "type": "array",
                    "items": {
                        "type": "object",
                        "additionalProperties": false,
                        "properties": {
                            "title": { "type": "string", "maxLength": 200 },
                            "bgColor": @@COLOR@@,
                            "txtColor": @@COLOR@@,
                            "width": { "type": "string", "maxLength": 20 },
                            "bgImage": @@IMAGE_URL@@
                        },
                        "required": ["title", "bgColor", "txtColor"]
                    }
                },
                "tableWidthMode": { "enum": ["", "auto", "custom"] },
                "tableWidth": { "type": "string", "maxLength": 20 },
                "tableCellDirection": { "enum": ["column", "row"] },
                "tableCellAlign": @@ALIGN@@,
                "titleTextColor": @@COLOR@@,
                "titleTextOpacity": { "type": "number", "minimum": 0, "maximum": 100, "multipleOf": 1 },
                "itemWidth": { "type": "number", "minimum": 16, "maximum": 300, "multipleOf": 1 },
                "itemWidthAuto": { "type": "boolean" },
                "itemImageCover": {
                    "enum": [
                        true,
                        false,
                        "opti"
                    ]
                },
                "itemMinWidth": { "type": "number", "minimum": 0, "maximum": 300, "multipleOf": 1 },
                "itemMaxWidth": { "type": "number", "minimum": 16, "maximum": 300, "multipleOf": 1 },
                "itemHeight": { "type": "number", "minimum": 16, "maximum": 300, "multipleOf": 1 },
                "itemHeightAuto": { "type": "boolean" },
                "itemMinHeight": { "type": "number", "minimum": 0, "maximum": 300, "multipleOf": 1 },
                "itemMaxHeight": { "type": "number", "minimum": 16, "maximum": 300, "multipleOf": 1 },
                "itemPadding": { "type": "number", "minimum": 0, "maximum": 20, "multipleOf": 1 },
                "itemBorder": { "type": "number", "minimum": 0, "maximum": 20, "multipleOf": 1 },
                "itemMargin": { "type": "number", "minimum": 0, "maximum": 20, "multipleOf": 1 },
                "itemBackgroundColor": @@COLOR@@,
                "itemBorderColor": @@COLOR@@,
                "itemBackgroundOpacity": { "type": "number", "minimum": 0, "maximum": 100, "multipleOf": 1 },
                "itemBorderOpacity": { "type": "number", "minimum": 0, "maximum": 100, "multipleOf": 1 },
                "itemTextMinLine": { "type": "number", "minimum": 0, "maximum": 10, "multipleOf": 1 },
                "itemTextMaxLine": { "type": "number", "minimum": 0, "maximum": 10, "multipleOf": 1 },
                "itemTextSize": { "type": "number", "minimum": 6, "maximum": 100, "multipleOf": 1 },
                "itemTextOnlySize": { "type": "number", "minimum": 6, "maximum": 100, "multipleOf": 1 },
                "itemTextColor": { "type": "string" },
                "itemTextOpacity": { "type": "number", "minimum": 0, "maximum": 100, "multipleOf": 1 },
                "itemTextPosition": @@TEXT_POSITION@@,
                "itemTextBackgroundColor": @@COLOR@@,
                "itemTextBackgroundOpacity": { "type": "number", "minimum": 0, "maximum": 100, "multipleOf": 1 },
                "lineBackgroundColor": @@COLOR@@,
                "lineBorderColor": @@COLOR@@,
                "lineBackgroundOpacity": { "type": "number", "minimum": 0, "maximum": 100, "multipleOf": 1 },
                "lineBorderOpacity": { "type": "number", "minimum": 0, "maximum": 100, "multipleOf": 1 },
                "imageBackgroundColor": @@COLOR@@,
                "imageBackgroundImage": @@BACKGROUND_IMAGE@@,
                "imageBackgroundOpacity": { "type": "number", "minimum": 0, "maximum": 100, "multipleOf": 1 },
                "imageBackgroundCustom": @@IMAGE_URL@@,
                "imageWidth": { "type": "number", "minimum": 100, "maximum": 4000, "multipleOf": 1 },
                "imageHeight": { "type": "number", "minimum": 100, "maximum": 4000, "multipleOf": 1 },
                "imageSize": { "enum": ["", "auto", "cover"] },
                "imagePosition": { "enum": ["", "auto", "center"] },
                "columnMinHeight": { "type": "number", "minimum": 0, "maximum": 4000, "multipleOf": 1 },
                "axisLineWidth": { "type": "number", "minimum": 0, "maximum": 12, "multipleOf": 1 },
                "axisLineColor": @@COLOR@@,
                "axisLineOpacity": { "type": "number", "minimum": 0, "maximum": 100, "multipleOf": 1 },
                "axisArrowWidth": { "type": "number", "minimum": 0, "maximum": 50, "multipleOf": 1 },
                "zoneFieldEdit": { "type": "boolean" },
                "nameWidth": { "type": "number", "minimum": 50, "maximum": 300, "multipleOf": 1 },
                "nameMinHeight": { "type": "number", "minimum": 0, "maximum": 300, "multipleOf": 1 },
                "nameFontSize": { "type": "number", "minimum": 50, "maximum": 300, "multipleOf": 1 },
                "nameBackgroundOpacity": { "type": "number", "minimum": 0, "maximum": 100, "multipleOf": 1 },
                "nameMarkdown": { "type": "boolean" },
                "nameBgImageSize": @@NAME_BG_IMAGE_SIZE@@,
                "borderRadius": { "type": "number", "minimum": 0, "maximum": 50, "multipleOf": 1 },
                "borderSpacing": { "type": "number", "minimum": -1, "maximum": 20, "multipleOf": 1 },
                "borderSize": { "type": "number", "minimum": 0, "maximum": 20, "multipleOf": 1 },
                "groupLineSize": { "type": "number", "minimum": 0, "maximum": 50, "multipleOf": 1 },
                "groupLineColor": @@COLOR@@,
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
