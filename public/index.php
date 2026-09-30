<?php

use App\Kernel;
use Webmozart\Assert\Assert;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return static function (array $context): Kernel {
    Assert::string($context['APP_ENV']);

    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
