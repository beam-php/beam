<?php

namespace Beam;

use Middlewares\Utils\RequestHandlerContainer;
use Psr\Container\ContainerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class Dispatcher
{
    /** @var ContainerInterface */
    private static $container;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(ContainerInterface $container = null)
    {
        $this->container = $container ?: self::getContainer();
        $this->logger = $this->container->has(LoggerInterface::class) ? $this->container->get(LoggerInterface::class) : new NullLogger;
    }

    public static function getContainer(): ContainerInterface
    {
        if (isset(self::$container)) {
            return self::$container;
        }

        return self::$container = new RequestHandlerContainer;
    }
}
