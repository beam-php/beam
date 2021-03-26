<?php

namespace Beam;

use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Container\ContainerInterface;
use InvalidArgumentException;
use GuzzleHttp\Psr7\ServerRequest as GuzzleServerRequest;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Zend\Diactoros\ServerRequestFactory as ZendServerRequestFactory;
use Zend\Diactoros\ResponseFactory as ZendResponseFactory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Nyholm\Psr7\Factory\Psr17Factory as NyholmPsr17Factory;
use Slim\Psr7\Factory\ResponseFactory as SlimResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory as SlimServerRequestFactory;

/**
 * Minimal Service Locator (singleton) fallback when no container is used, no
 * explicit dependency injection is used, and no autowiring is available.
 *
 * This class is primarily intended for use by RAD or demo apps.
 * Service location is highly random, so prefer using a container or explicit
 * dependency injection.
 *
 * Usage:
 *
 *     $request = Locator::instance()->locate(ServerRequestInterface::class);
 */
final class Locator
{
    /** @var Locator */
    private static $instance;

    /** @var ContainerInterface */
    private $container;

    /** @var array */
    private $services = [];

    private function __construct(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param ContainerInterface|null $container
     * @return Locator
     */
    public static function instance(ContainerInterface $container = null): Locator
    {
        if (isset(static::$instance)) {
            // Inject the container if not set yet
            if (!is_null($container) && is_null(static::$instance->container)) {
                static::$instance->container = $container;
            }

            return static::$instance;
        }

        return static::$instance = new static($container);
    }

    /**
     * @param string $serviceName
     * @return object Instance of the requested service
     * @throws InvalidArgumentException
     */
    public function locate(string $serviceName)
    {
        switch ($serviceName) {
            case 'server-request':
            case ServerRequestInterface::class:
                return $this->getServerRequest();
                break;

            case 'server-request-factory':
            case ServerRequestFactoryInterface::class:
                if (isset($this->services[ServerRequestFactoryInterface::class])) {
                    return $this->services[ServerRequestFactoryInterface::class];
                }

            if ($factory = $this->getFromContainer(ServerRequestFactoryInterface::class, 'server-request-factory')) {
                    return $this->services[ServerRequestFactoryInterface::class] = $factory;
                }
                break;
            case 'response-factory':
            case ResponseFactoryInterface::class:
                return $this->getResponseFactory();
                break;
            default:
                if ($service = $this->getFromContainer($serviceName)) {
                    return $service;
                }
        }

        throw new InvalidArgumentException("Service '$serviceName' not found.");
    }

    public function getServerRequestFactory(): ServerRequestFactoryInterface
    {
        if (isset($this->services[ServerRequestFactoryInterface::class])) {
            return $this->services[ServerRequestFactoryInterface::class];
        }

        if ($factory = $this->getFromContainer(ServerRequestFactoryInterface::class, 'server-request-factory')) {
            return $this->services[ServerRequestFactoryInterface::class] = $factory;
        }

        if (class_exists(ZendServerRequestFactory::class)) {
            return $this->services[ServerRequestFactoryInterface::class] = new ZendServerRequestFactory;
        }

        if (class_exists(SlimServerRequestFactory::class)) {
            return $this->services[ServerRequestFactoryInterface::class] = new SlimServerRequestFactory;
        }

        throw new InvalidArgumentException("Service 'ServerRequestFactory' not found.");
    }

    public function getRequestFactory(): RequestFactoryInterface
    {
        return $this->locate(RequestFactoryInterface::class);
    }

    public function getResponseFactory(): ResponseFactoryInterface
    {
        if (isset($this->services[ResponseFactoryInterface::class])) {
            return $this->services[ResponseFactoryInterface::class];
        }

        if ($factory = $this->getFromContainer(ResponseFactoryInterface::class, 'response-factory')) {
            return $this->services[ResponseFactoryInterface::class] = $factory;
        }

        if (class_exists(HttpFactory::class)) {
            return $this->services[ResponseFactoryInterface::class] = new HttpFactory;
        }

        if (class_exists(GuzzleResponse::class)) {
            return $this->services[ResponseFactoryInterface::class] = new class implements ResponseFactoryInterface {
                public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface {
                    return new GuzzleResponse($code, [], null, '1.1', $reasonPhrase);
                }
            };
        }

        if (class_exists(ZendResponseFactory::class)) {
            return $this->services[ResponseFactoryInterface::class] = new ZendResponseFactory;
        }

        if (class_exists(NyholmPsr17Factory::class)) {
            return $this->services[ResponseFactoryInterface::class] = new NyholmPsr17Factory;
        }

        if (class_exists(SlimResponseFactory::class)) {
            return $this->services[ResponseFactoryInterface::class] = new SlimResponseFactory;
        }

        throw new InvalidArgumentException("Service 'ServerRequest' not found.");
   }

    public function getStreamFactory(): StreamFactoryInterface
    {
        return $this->locate(StreamFactoryInterface::class);
    }

    /** @return ServerRequestInterface Server request from globals */
    public function getServerRequest(): ServerRequestInterface
    {
        if (isset($this->services[ServerRequestInterface::class])) {
            return $this->services[ServerRequestInterface::class];
        }

        if ($request = $this->getFromContainer(ServerRequestInterface::class, 'server-request')) {
            return $this->services[ServerRequestInterface::class] = $request;
        }

        if (class_exists(GuzzleServerRequest::class)) {
            return $this->services[ServerRequestInterface::class] = GuzzleServerRequest::fromGlobals();
        }

        if (class_exists(ServerRequestCreator::class)) {
            $creator = new ServerRequestCreator(
                $this->locate(ServerRequestFactoryInterface::class),
                $this->locate(UriFactoryInterface::class),
                $this->locate(UploadedFileFactoryInterface::class),
                $this->locate(StreamFactoryInterface::class)
            );

            return $this->services[ServerRequestInterface::class] = $creator->fromGlobals();
        }

        try {
            $factory = $this->getServerRequestFactory();
            return $factory->fromGlobals();
        } catch (InvalidArgumentException $e) {
        }

        throw new InvalidArgumentException("Service 'ServerRequest' not found.");
    }

    private function getFromContainer(string ...$keys)
    {
        if (!$this->container) {
            return;
        }

        foreach ($keys as $key) {
            if ($this->container->has($key)) {
                return $this->container->get($key);
            }
        }
    }
}
