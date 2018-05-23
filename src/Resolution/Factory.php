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

namespace League\Uri\Resolution;

use League\Uri;
use League\Uri\Data;
use League\Uri\Exception\InvalidUri;
use League\Uri\Exception\InvalidUriComponent;
use League\Uri\File;
use League\Uri\Ftp;
use League\Uri\Http;
use League\Uri\UriInterface as LeagueUriInterface;
use League\Uri\Ws;
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
     * @throws InvalidUriComponent if the scheme is invalid
     * @throws InvalidUri          if the class does not implements a supported interface
     */
    private function addMap(string $scheme, string $className)
    {
        if (!preg_match(self::REGEXP_SCHEME, $scheme)) {
            throw new InvalidUriComponent(sprintf('the scheme `%s` is invalid', $scheme));
        }

        if (empty(array_intersect((new ReflectionClass($className))->getInterfaceNames(), self::$uri_interfaces))) {
            throw new InvalidUri(sprintf('the class `%s` does not implement a supported class', $className));
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
     * @throws InvalidUri if there's no base URI and the submitted URI is not absolute
     *
     * @return LeagueUriInterface|UriInterface
     */
    public function create($uri, $base_uri = null)
    {
        if (null !== $base_uri) {
            $base_uri = $this->create($base_uri);
        }

        if (!$uri instanceof UriInterface && !$uri instanceof LeagueUriInterface) {
            $components = Uri\parse($uri);
            $uri = (new ReflectionClass($this->getClassName($components['scheme'], $base_uri)))
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

        if (null !== $base_uri) {
            return Uri\resolve($uri, $base_uri);
        }

        if ('' === $uri->getScheme()) {
            throw new InvalidUri(sprintf('the URI `%s` must be absolute', $uri));
        }

        if ('' === $uri->getAuthority()) {
            return $uri;
        }

        return Uri\resolve($uri, $uri->withFragment('')->withQuery('')->withPath(''));
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
        if (null !== $base_uri && in_array($scheme, [$base_uri->getScheme(), ''], true)) {
            return get_class($base_uri);
        }

        return $this->map[$scheme] ?? Uri\Uri::class;
    }
}
