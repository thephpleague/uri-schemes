<?php

/**
 * League.Uri (http://uri.thephpleague.com).
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

use League\Uri\Exception\InvalidUri;
use League\Uri\Exception\MalformedUri;
use League\Uri\Parser\RFC3986;
use Psr\Http\Message\UriInterface as Psr7UriInterface;
use ReflectionClass;

final class Factory
{
    /**
     * @internal
     */
    const REGEXP_SCHEME = '/^[a-z][a-z\+\.\-]*$/';

    /**
     * Supported schemes.
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
     * supported URI interfaces.
     *
     * @var array
     */
    private static $uri_interfaces = [
        Psr7UriInterface::class,
        UriInterface::class,
    ];

    /**
     * new instance.
     *
     * @param iterable $map An override map of URI classes indexed by their supported schemes.
     */
    public function __construct(iterable $map = [])
    {
        foreach ($map as $scheme => $className) {
            $this->addMap(strtolower($scheme), $className);
        }
    }

    /**
     * Add a new classname for a given scheme URI.
     *
     * @throws MalformedUri if the scheme is invalid
     * @throws InvalidUri   if the class does not implements a supported interface
     */
    private function addMap(string $scheme, string $className): void
    {
        if (1 !== preg_match(self::REGEXP_SCHEME, $scheme)) {
            throw new MalformedUri(sprintf('The scheme `%s` is invalid', $scheme));
        }

        if ([] === array_intersect((new ReflectionClass($className))->getInterfaceNames(), self::$uri_interfaces)) {
            throw new InvalidUri(sprintf('The class `%s` does not implement a supported class', $className));
        }

        $this->map[$scheme] = $className;
    }

    /**
     * Create a new absolute URI optionally according to another absolute base URI object.
     *
     * The base URI can be
     * <ul>
     * <li>UriInterface
     * <li>Psr7UriInterface
     * <li>a string
     * </ul>
     *
     * @param null|mixed $uri
     * @param null|mixed $base_uri
     *
     * @throws MalformedUri                  if there's no base URI and the submitted URI is not absolute
     * @return Psr7UriInterface|UriInterface
     */
    public function create($uri, $base_uri = null)
    {
        if (null !== $base_uri) {
            $base_uri = $this->create($base_uri);
        }

        if (!$uri instanceof UriInterface && !$uri instanceof Psr7UriInterface) {
            $components = RFC3986::parse($uri);
            $uri = $this->getUriObject($components['scheme'], $base_uri)
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
            return Resolver::resolve($uri, $base_uri);
        }

        if ('' === $uri->getScheme()) {
            throw new MalformedUri(sprintf('the URI `%s` must be absolute', (string) $uri));
        }

        if ('' === $uri->getAuthority()) {
            return $uri;
        }

        return Resolver::resolve($uri, $uri->withFragment('')->withQuery('')->withPath(''));
    }

    /**
     * Returns the className to use to instantiate the URI object.
     *
     * @param string|null $scheme   URI scheme component
     * @param mixed       $base_uri base URI object
     *
     * @return Psr7UriInterface|UriInterface
     */
    private function getUriObject(string $scheme = null, $base_uri)
    {
        $scheme = strtolower($scheme ?? '');
        $className = $this->map[$scheme] ?? Uri::class;
        if (null !== $base_uri && in_array($scheme, [$base_uri->getScheme(), ''], true)) {
            $className = $base_uri;
        }

        return (new ReflectionClass($className))->newInstanceWithoutConstructor();
    }
}
