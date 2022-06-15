<?php

namespace App\Controller;

class CodeError
{
    public const LOGIN_MISSING = 1001;
    public const PASSWORD_MISSING = 1002;
    public const EMAIL_MISSING = 1002;
    public const INVALID_TOKEN = 1010;
    public const DUPLICATE_CONTENT = 2000;
    public const USER_NOT_FOUND = 3000;
    public const USER_NO_PERMISSION = 3001;
    public const CLASSEMENT_NOT_FOUND = 3101;
    public const CLASSEMENTS_NOT_FOUND = 3102;
    public const TOKEN_NOT_FOUND = 3003;
    public const LOGIN_ALREADY_EXISTS = 4001;
    public const EMAIL_ALREADY_EXISTS = 4002;
}
