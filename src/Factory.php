<?php

/**
 * League.Uri (http://uri.thephpleague.com)
 *
 * @package    League\Uri
 * @subpackage League\Uri\Schemes
 * @author     Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @license    https://github.com/thephpleague/uri-components/blob/master/LICENSE (MIT License)
 * @version    2.0.0
 * @link       https://github.com/thephpleague/uri-schemes
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace League\Uri;

use League\Uri\Exception\CreatingUriFailed;
use League\Uri\Exception\MappingUriFailed;
use League\Uri\UriInterface as LeagueUriInterface;
use Psr\Http\Message\UriInterface;
use ReflectionClass;
use Traversable;
use TypeError;

final class Factory
{
    /**
     * @internal
     */
    const REGEXP_SCHEME = '/^[a-z][a-z\+\.\-]*$/';

    /**
     * Supported schemes
     *
     * @var string[]
     */
    private $map = [
        'http' => Http::class,
        'https' => Http::class,
        'ftp' => Ftp::class,
        'ws' => Ws::class,
        'wss' => Ws::class,
        'data' => Data::class,
        'file' => File::class,
    ];

    /**
     * supported URI interfaces
     *
     * @var array
     */
    private static $uri_interfaces = [
        LeagueUriInterface::class,
        UriInterface::class,
    ];

    /**
     * new instance
     *
     * @param array|Traversable $map An override map of URI classes indexed by their supported schemes.
     */
    public function __construct($map = [])
    {
        if (!is_array($map) && !$map instanceof Traversable) {
            throw new TypeError(sprintf('The map must be an iterable structure, `%s` given', gettype($map)));
        }

        foreach ($map as $scheme => $className) {
            $this->addMap(strtolower($scheme), $className);
        }
    }

    /**
     * Add a new classname for a given scheme URI
     *
     * @param string $scheme    valid URI scheme
     * @param string $className classname which implements LeagueUriInterface or UriInterface
     *
     * @throws MappingUriFailed if the scheme is invalid
     * @throws MappingUriFailed if the class does not implements a supported interface
     */
    private function addMap(string $scheme, string $className)
    {
        if (!preg_match(self::REGEXP_SCHEME, $scheme)) {
            throw new MappingUriFailed(sprintf('the scheme `%s` is invalid', $scheme));
        }

        if (empty(array_intersect((new ReflectionClass($className))->getInterfaceNames(), self::$uri_interfaces))) {
            throw new MappingUriFailed(sprintf('the class `%s` does not implement a supported class', $className));
        }

        $this->map[$scheme] = $className;
    }

    /**
     * Create a new absolute URI optionally according to another absolute base URI object.
     *
     * The base URI can be
     * <ul>
     * <li>UriInterface
     * <li>LeagueUriInterface
     * <li>a string
     * </ul>
     *
     * @param mixed $uri
     * @param mixed $base_uri
     *
     * @throws CreatingUriFailed if there's no base URI and the submitted URI is not absolute
     *
     * @return LeagueUriInterface|UriInterface
     */
    public function create($uri, $base_uri = null)
    {
        $components = parse($uri);
        $base_uri = $this->filterBaseUri($base_uri);

        if (null !== $base_uri) {
            return resolve($this->newInstance($components, $base_uri), $base_uri);
        }

        if ('' === (string) $components['scheme']) {
            throw new CreatingUriFailed(sprintf('the URI `%s` must be absolute', $uri));
        }

        $new = $this->newInstance($components, $base_uri);
        if ('' === $new->getAuthority()) {
            return $new;
        }

        return resolve($new, $new->withFragment('')->withQuery('')->withPath(''));
    }

    /**
     * Returns the Base URI.
     *
     * @param mixed $uri
     *
     * @throws CreatingUriFailed if the base URI is not an absolute URI
     *
     * @return LeagueUriInterface|UriInterface|null
     */
    private function filterBaseUri($uri)
    {
        if ($uri instanceof UriInterface || $uri instanceof LeagueUriInterface) {
            if ('' !== $uri->getScheme()) {
                return $uri;
            }

            throw new CreatingUriFailed(sprintf('the URI `%s` must be absolute', $uri));
        }

        if (null === $uri) {
            return $uri;
        }

        return $this->create($uri);
    }

    /**
     * Creates a new URI object from its name using Reflection.
     *
     * @param array $components
     * @param mixed $base_uri
     *
     * @return LeagueUriInterface|UriInterface
     */
    private function newInstance(array $components, $base_uri)
    {
        return (new ReflectionClass($this->getClassName($components['scheme'], $base_uri)))
            ->newInstanceWithoutConstructor()
            ->withHost($components['host'] ?? '')
            ->withPort($components['port'])
            ->withUserInfo($components['user'] ?? '', $components['pass'])
            ->withScheme($components['scheme'] ?? '')
            ->withPath($components['path'] ?? '')
            ->withQuery($components['query'] ?? '')
            ->withFragment($components['fragment'] ?? '')
        ;
    }

    /**
     * Returns the className to use to instantiate the URI object.
     *
     * @param string|null $scheme   URI scheme component
     * @param mixed       $base_uri base URI object
     *
     * @return string
     */
    private function getClassName(string $scheme = null, $base_uri): string
    {
        $scheme = strtolower($scheme ?? '');
        if (isset($base_uri) && in_array($scheme, [$base_uri->getScheme(), ''], true)) {
            return get_class($base_uri);
        }

        return $this->map[$scheme] ?? Uri::class;
    }
}
