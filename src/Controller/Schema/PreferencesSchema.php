<?php

namespace App\Controller\Schema;

class PreferencesSchema
{
    public static $jsonSchema = <<<'JSON'
{
  "type": "object",
  "properties": {
    "interfaceLanguage": { "type": "string" },
    "interfaceTheme": { "type": "string" },
    "nameCopy": { "type": "boolean" },
    "newColor": { "enum": ["mixed", "same"] },
    "newLine": { "enum": ["below", "above", "ask-me"] },
    "lineOption": { "enum": ["auto", "reduce", "hidden"] },
    "mode": { "type": "string" },
    "autoResize": { "enum": ["300×300", "500×500", "origin"] },
    "theme": { "type": "string" },
    "pageSize": { "type": "integer", "minimum": 1 },
    "mainMenuReduce": { "type": "boolean" },
    "emojiList": { "type": "array", "items": { "type": "string" } },
    "zoomMobile": { "type": "integer", "minimum": 50, "maximum": 200 },
    "adult": { "type": "boolean" },
    "advancedOptions": { "type": "boolean" },
    "advancedFork": { "type": "boolean" },
    "authApiKeys": {
      "type": "object",
      "properties": {
        "tmdb": { "type": "string" }
      }
    },
    "api": {
      "type": "object",
      "properties": {
        "anilist": { "type": "boolean" }
      }
    }
  }
}
JSON;
}
