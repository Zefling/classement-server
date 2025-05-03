<?php

namespace App\Controller\Common;

class CodeError
{
    public const LOGIN_MISSING = 1001;
    public const PASSWORD_MISSING = 1010;
    public const PASSWORD_MISSING_OLD = 1011;
    public const PASSWORD_MISSING_NEW = 1012;
    public const PASSWORD_INVALID = 1013;
    public const EMAIL_MISSING = 1020;
    public const EMAIL_NO_MATCHING = 1021;
    public const USERNAME_IS_YOURS = 1025;
    public const USERNAME_ALREADY_EXISTS = 1026;
    public const INVALID_TOKEN = 1030;
    public const TOKEN_NOT_FOUND = 1031;
    public const SERVICE_NOT_FOUND = 1040;
    public const INVALID_TEST = 1100;
    public const DUPLICATE_CONTENT = 2000;
    public const USER_NOT_FOUND = 3000;
    public const USER_NO_PERMISSION = 3001;
    public const USER_NO_PERMISSION_ADMIN = 3002;
    public const USER_MISSING_CREDENTIALS = 3003;
    public const USER_BANNED = 3010;
    public const USER_NOT_VALIDATED = 3020;
    public const CLASSEMENT_NOT_FOUND = 3101;
    public const CLASSEMENTS_NOT_FOUND = 3102;
    public const CLASSEMENT_HISTORY_NOT_FOUND = 3103;
    public const CLASSEMENT_PASSWORD_REQUIRED = 3110;
    public const LINK_ID_ERROR = 3110;
    public const LINK_ID_DUPLICATE = 3110;
    public const TEMPLATE_NOT_FOUND = 3201;
    public const TEMPLATE_NO_ID = 3202;
    public const STATUS_ERROR = 3301;
    public const DB_SAVE_REQUEST_ERROR = 3400;
    public const THEME_NOT_FOUND = 3501;
    public const THEMES_NOT_FOUND = 3502;
    public const LOGIN_ALREADY_EXISTS = 4001;
    public const EMAIL_ALREADY_EXISTS = 4002;
    public const EMAIL_UNAVAILABLE = 4100;
    public const CATEGORY_ERROR = 5000;
    public const INVALID_DATA = 5100;
    public const STATS_ERROR = 6000;
    public const INVALID_REQUEST = 9999;
}
