<?php

use App\Kernel;

error_reporting(E_ALL ^ E_DEPRECATED);

require_once dirname(__DIR__) . '/vendor/autoload_runtime.php';


return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
