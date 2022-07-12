<?php

namespace App\Controller;

class CodeError
{
    public const LOGIN_MISSING = 1001;
    public const PASSWORD_MISSING = 1010;
    public const PASSWORD_MISSING_OLD = 1011;
    public const PASSWORD_MISSING_NEW = 1012;
    public const PASSWORD_INVALID = 1013;
    public const EMAIL_MISSING = 1020;
    public const EMAIL_NO_MATCHING = 1021;
    public const INVALID_TOKEN = 1030;
    public const INVALID_TEST = 1031;
    public const DUPLICATE_CONTENT = 2000;
    public const USER_NOT_FOUND = 3000;
    public const USER_NO_PERMISSION = 3001;
    public const CLASSEMENT_NOT_FOUND = 3101;
    public const CLASSEMENTS_NOT_FOUND = 3102;
    public const TOKEN_NOT_FOUND = 3003;
    public const LOGIN_ALREADY_EXISTS = 4001;
    public const EMAIL_ALREADY_EXISTS = 4002;
    public const CATEGORY_ERROR = 5000;
}
