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
                }
            }
            if (!empty($data['list'])) {
                self::formatList($data['list']);
            }
        }

        if (!empty($data['options']['imageBackgroundCustom']) && $data['options']['imageBackgroundCustom'][0] === '/') {
            $data['options']['imageBackgroundCustom']
                = self::siteURL() . $data['options']['imageBackgroundCustom'];
        }

        return $data;
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
