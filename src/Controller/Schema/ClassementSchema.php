<?php

namespace App\Controller\Schema;

class ClassementSchema
{
    public static $jsonSchema = <<<'JSON'
{
    "type": "object",
    "properties": {
        "id": {
            "type": "string"
        },
        "rankingId": {
            "type": "string"
        },
        "templateId": {
            "type": "string"
        },
        "parentId": {
            "type": "string"
        },
        "banner": {
            "type": "string"
        },
        "options": {
            "$ref": "#/definitions/Options"
        },
        "groups": {
            "type": "array",
            "items": {
                "$ref": "#/definitions/FormatedGroup"
            }
        },
        "list": {
            "type": "array",
            "items": {
                "$ref": "#/definitions/FileString"
            }
        }
    },
    "required":["options", "groups", "list"],
    "definitions": {
        "Options": {
            "type": "object",
            "properties": {
                "titleTextColor": {
                    "type": "string"
                },
                "titleTextOpacity": {
                    "type": "number",
                    "minimum": 0,
                    "maximum": 100
                },
                "itemWidth": {
                    "type": "number",
                    "minimum": 0
                },
                "itemWidthAuto": {
                    "type": "boolean"
                },
                "itemHeight": {
                    "type": "number",
                    "minimum": 0
                },
                "itemPadding": {
                    "type": "number",
                    "minimum": 0
                },
                "itemBorder": {
                    "type": "number",
                    "minimum": 0
                },
                "itemMargin": {
                    "type": "number",
                    "minimum": 0
                },
                "itemBackgroundColor": {
                    "type": "string"
                },
                "itemBorderColor": {
                    "type": "string"
                },
                "itemBackgroundOpacity": {
                    "type": "number",
                    "minimum": 0,
                    "maximum": 100
                },
                "itemBorderOpacity": {
                    "type": "number",
                    "minimum": 0,
                    "maximum": 100
                },
                "itemTextColor": {
                    "type": "string"
                },
                "itemTextOpacity": {
                    "type": "number"
                },
                "itemTextPosition": {
                    "type": "string",
                    "type": "'bottom' | 'bottom-over' | 'top' | 'top-over'"
                },
                "itemTextBackgroundColor": {
                    "type": "string"
                },
                "itemTextBackgroundOpacity": {
                    "type": "number",
                    "minimum": 0,
                    "maximum": 100
                },
                "lineBackgroundColor": {
                    "type": "string"
                },
                "lineBorderColor": {
                    "type": "string"
                },
                "lineBackgroundOpacity": {
                    "type": "number",
                    "minimum": 0,
                    "maximum": 100
                },
                "lineBorderOpacity": {
                    "type": "number",
                    "minimum": 0,
                    "maximum": 100
                },
                "imageBackgroundColor": {
                    "type": "string"
                },
                "imageBackgroundImage": {
                    "type": "string"
                },
                "imageBackgroundCustom": {
                    "type": "string"
                },
                "imageWidth": {
                    "type": "number",
                    "minimum": 0
                },
                "nameWidth": {
                    "type": "number",
                    "minimum": 0
                },
                "nameFontSize": {
                    "type": "number",
                    "minimum": 0
                },
                "nameBackgroundOpacity": {
                    "type": "number",
                    "minimum": 0,
                    "maximum": 100
                },
                "autoSave": {
                    "type": "boolean"
                },
                "showAdvancedOptions": {
                    "type": "boolean"
                },
                "title": {
                    "type": "string"
                },
                "category": {
                    "type": "string"
                },
                "description": {
                    "type": "string"
                },
                "tags": {
                    "type": "array",
                    "items": {
                        "type": "string"
                    }
                },
            },
            "required":[
                "titleTextColor",
                "titleTextOpacity",
                "itemWidth",
                "itemWidthAuto",
                "itemHeight",
                "itemPadding",
                "itemBorder",
                "itemMargin",
                "itemBackgroundColor",
                "itemBorderColor",
                "itemBackgroundOpacity",
                "itemBorderOpacity",
                "itemTextColor",
                "itemTextOpacity",
                "itemTextPosition",
                "itemTextBackgroundColor",
                "itemTextBackgroundOpacity",
                "lineBackgroundColor",
                "lineBorderColor",
                "lineBackgroundOpacity",
                "lineBorderOpacity",
                "imageBackgroundColor",
                "imageBackgroundImage",
                "imageBackgroundCustom",
                "imageWidth",
                "nameWidth",
                "nameFontSize",
                "nameBackgroundOpacity",
                "showAdvancedOptions",
                "title",
                "category",
                "description",
                "tags"
            ]
        },
        "FormatedGroup": {
            "type": "object",
            "properties": {
                "name": {
                    "type": "string"
                }, 
                "bgColor": {
                    "type": "string"
                }, 
                "txtColor": {
                    "type": "string"
                }, 
                "list": {
                    "type": "array",
                    "items": {
                        "$ref": "#/definitions/FileString"
                    }
                }
            },
            "required":[
                "name",
                "bgColor",
                "txtColor",
                "list"
            ]
        },
        "FormatedGroup": {
            "type": "object",
            "properties": {
                "url": {
                    "type": "string"
                },
                "name": {
                    "type": "string"
                },
                "height": {
                    "type": "number",
                    "minimum": 0
                },
                "width": {
                    "type": "number",
                    "minimum": 0
                },
                "size": {
                    "type": "number",
                    "minimum": 0
                },
                "realSize": {
                    "type": "number",
                    "minimum": 0
                },
                "type": {
                    "type": "string"
                },
                "date": {
                    "type": "number",
                    "minimum": 0
                },
                "title": {
                    "type": "string"
                },
                "annotation": {
                    "type": "string"
                },
            },
            "required":[
                "name",
                "size",
                "realSize",
                "type",
                "date"
            ]
        },
    }
}
JSON;
}
