<?php

namespace Creonit\NuxtBundle\Routing;

use Propel\Common\Config\Exception\JsonParseException;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;


class NuxtLoader implements LoaderInterface
{
    protected $loaded = false;
    /**
     * @var FileLocatorInterface
     */
    protected $locator;
    protected $file;

    public function __construct(FileLocatorInterface $locator, $file)
    {
        $this->locator = $locator;
        $this->file = $file;
    }

    private function parseRoutes(RouteCollection $routes, $data, $basePath = '')
    {
        foreach ($data as $route) {
            if (isset($route['children'])) {
                $this->parseRoutes($routes, $route['children'], $route['path']);
                continue;
            }

            $defaults = [];
            $path = $basePath . $route['path'];
            $path = preg_replace_callback('#/:([^/]+)(\??)#usi', function ($match) {
                if ($match[2]) {
                    $defaults[$match[1]] = '';
                }

                return "/{{$match[1]}}";
            }, $path);

            $path = rtrim($path, '/');

            $routes->add($route['name'], new Route($path, $defaults));
        }
    }

    /**
     * Loads a resource.
     *
     * @param mixed $resource The resource
     *
     * @return RouteCollection
     * @throws \Exception If something went wrong
     */
    public function load($resource, string $type = null)
    {
        if (true === $this->loaded) {
            throw new \RuntimeException('Do not add the "nuxt" loader twice');
        }

        $routes = new RouteCollection();

        try {
            $path = $this->locator->locate($this->file);

        } catch (FileLocatorFileNotFoundException $exception) {
            return $routes;
        }

        if (!is_readable($path)) {
            return $routes;
        }

        $json = file_get_contents($path);

        $content = [];

        if ('' !== $json) {
            $content = json_decode($json, true);
            $error = json_last_error();

            if (JSON_ERROR_NONE !== $error) {
                throw new JsonParseException($error);
            }
        }

        $this->parseRoutes($routes, $content);

        $this->loaded = true;

        return $routes;
    }

    /**
     * Returns whether this class supports the given resource.
     *
     * @param mixed $resource A resource
     *
     * @return bool True if this class supports the given resource, false otherwise
     */
    public function supports($resource, string $type = null)
    {
        return 'nuxt' === $type;
    }

    /**
     * Gets the loader resolver.
     *
     * @return LoaderResolverInterface A LoaderResolverInterface instance
     */
    public function getResolver()
    {
    }

    /**
     * Sets the loader resolver.
     */
    public function setResolver(LoaderResolverInterface $resolver)
    {
    }
}
