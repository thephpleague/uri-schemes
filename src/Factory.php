<?php
/**
 * League.Uri (http://uri.thephpleague.com)
 *
 * @package    League.uri
 * @subpackage League\Uri\Modifiers
 * @author     Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @copyright  2017 Ignace Nyamagana Butera
 * @license    https://github.com/thephpleague/uri-manipulations/blob/master/LICENSE (MIT License)
 * @version    2.0.0
 * @link       https://github.com/thephpleague/uri-manipulations
 */
declare(strict_types=1);

namespace League\Uri;

use League\Uri\UriInterface as LeagueUriInterface;
use Psr\Http\Message\UriInterface;
use ReflectionClass;
use Traversable;
use TypeError;

/**
 * Factory class to ease loading URI object
 *
 * @package    League\Uri
 * @subpackage League\Uri\Schemes
 * @author     Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @since      1.1.0
 */
final class Factory
{
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
     * @throws UriException if the scheme is invalid
     * @throws UriException if the class does not implements a supported interface
     */
    private function addMap(string $scheme, string $className)
    {
        static $pattern = '/^[a-z][a-z\+\.\-]*$/';
        if (!preg_match($pattern, $scheme)) {
            throw new UriException(sprintf('Please verify the submitted scheme `%s`', $scheme));
        }

        if (empty(array_intersect((new ReflectionClass($className))->getInterfaceNames(), self::$uri_interfaces))) {
            throw new UriException(sprintf('Please verify the submitted class `%s`', $className));
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
     * @param string $uri
     * @param mixed  $base_uri
     *
     * @throws UriException if there's no base URI and the submitted URI is not absolute
     *
     * @return LeagueUriInterface|UriInterface
     */
    public function create(string $uri, $base_uri = null)
    {
        $components = parse($uri);
        if (null !== $base_uri) {
            $base_uri = $this->filterBaseUri($base_uri);
            $className = $this->getClassName($components['scheme'], $base_uri);

            return resolve($this->newInstance($components, $className), $base_uri);
        }

        if (null == $components['scheme']) {
            throw new UriException(sprintf('the submitted URI `%s` must be an absolute URI', $uri));
        }

        $className = $this->getClassName($components['scheme']);
        $uri = $this->newInstance($components, $className);
        if ('' === $uri->getAuthority()) {
            return $uri;
        }

        return resolve($uri, $uri->withPath('')->withQuery(''));
    }

    /**
     * Returns the Base URI.
     *
     * @param LeagueUriInterface|UriInterface|string $uri
     *
     * @throws UriException if the Base Uri is not an absolute URI
     *
     * @return LeagueUriInterface|UriInterface
     */
    private function filterBaseUri($uri)
    {
        if (!$uri instanceof UriInterface && !$uri instanceof LeagueUriInterface) {
            return $this->create($uri);
        }

        if ('' !== $uri->getScheme()) {
            return $uri;
        }

        throw new UriException(sprintf('The submitted URI `%s` must be an absolute URI', $uri));
    }

    /**
     * Returns the className to use to instantiate the URI object.
     *
     * @param string|null $scheme   URI scheme component
     * @param mixed       $base_uri base URI object
     *
     * @return string
     */
    private function getClassName(string $scheme = null, $base_uri = null): string
    {
        $scheme = strtolower($scheme ?? '');
        if (isset($base_uri) && in_array($scheme, [$base_uri->getScheme(), ''], true)) {
            return get_class($base_uri);
        }

        return $this->map[$scheme] ?? Uri::class;
    }

    /**
     * Creates a new URI object from its name using Reflection.
     *
     * @param array  $components
     * @param string $className
     *
     * @return LeagueUriInterface|UriInterface
     */
    private function newInstance(array $components, string $className)
    {
        return (new ReflectionClass($className))
            ->newInstanceWithoutConstructor()
            ->withHost($components['host'] ?? '')
            ->withPort($components['port'] ?? null)
            ->withUserInfo($components['user'] ?? '', $components['pass'] ?? null)
            ->withScheme($components['scheme'] ?? '')
            ->withPath($components['path'] ?? '')
            ->withQuery($components['query'] ?? '')
            ->withFragment($components['fragment'] ?? '')
        ;
    }
}
