<?php
declare(strict_types=1);

namespace Test;

use Monolog\Logger;
use PHPUnit\Framework\TestCase as PhpUnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * class de base pour les tests
 */
class TestCase extends PhpUnitTestCase
{
    protected function getLogger(): LoggerInterface
    {
        return new Logger('test');
    }
}
