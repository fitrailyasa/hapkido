<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
            if (
                ($errno === E_DEPRECATED || $errno === E_USER_DEPRECATED) &&
                str_contains(str_replace('\\', '/', $errfile), 'config/database.php')
            ) {
                return true;
            }

            return false;
        });

        return parent::createApplication();
    }
}
