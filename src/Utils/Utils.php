<?php

namespace App\Utils;

class Utils
{

    public static function formatData(array $data)
    {
        if (!empty($data)) {
            if (!empty($data['groups']) && is_array($data['groups'])) {
                foreach ($data['groups'] as &$group) {
                    self::formatList($group['list']);
                    if (!empty($group['bgImage'])) {
                        $group['bgImage'] = self::formatImageUrl($group['bgImage']);
                    }
                }
                unset($group);
            }
            if (!empty($data['list'])) {
                self::formatList($data['list']);
            }
        }

        if (!empty($data['options']['imageBackgroundCustom'])) {
            $data['options']['imageBackgroundCustom'] = self::formatImageUrl($data['options']['imageBackgroundCustom']);
        }

        if (!empty($data['options']['col']) && is_array($data['options']['col'])) {
            foreach ($data['options']['col'] as &$col) {
                if (!empty($col['bgImage'])) {
                    $col['bgImage'] = self::formatImageUrl($col['bgImage']);
                }
            }
            unset($col);
        }

        return $data;
    }

    public static function formatImageUrl(string $url): string
    {
        if (!empty($url) && $url[0] === '/') {
            return self::siteURL() . $url;
        }
        return $url;
    }

    public static function formatList(array &$list)
    {
        if (!empty($list) && is_array($list)) {
            $domaine =  self::siteURL();
            foreach ($list as &$item) {
                if (!empty($item['url']) && $item['url'][0] === '/') {
                    $item['url'] = $domaine . $item['url'];
                }
            }
        }
    }

    public static function siteURL()
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $domainName = $_SERVER['HTTP_HOST'];
        return $protocol . $domainName;
    }
}
