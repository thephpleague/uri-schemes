<?php
/**
 * League.Uri (http://uri.thephpleague.com)
 *
 * @package    League.uri
 * @subpackage League\Uri\Modifiers
 * @author     Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @copyright  2017 Ignace Nyamagana Butera
 * @license    https://github.com/thephpleague/uri-manipulations/blob/master/LICENSE (MIT License)
 * @version    1.1.1
 * @link       https://github.com/thephpleague/uri-manipulations
 */
declare(strict_types=1);

namespace League\Uri;

use League\Uri\Interfaces\Uri as LeagueUriInterface;
use Psr\Http\Message\UriInterface;
use ReflectionClass;

/**
 * Factory class to ease loading URI object
 *
 * @package    League\Uri
 * @subpackage League\Uri\Schemes
 * @author     Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @since      1.1.0
 */
class Factory
{
    /**
     * Supported schemes
     *
     * @var string[]
     */
    protected $map = [
        'http' => Http::class,
        'https' => Http::class,
        'ftp' => Ftp::class,
        'ws' => Ws::class,
        'wss' => Ws::class,
        'data' => Data::class,
        'file' => File::class,
    ];

    /**
     * Dot segments
     *
     * @var array
     */
    protected static $dot_segments = ['.' => 1, '..' => 1];

    /**
     * supported URI interfaces
     *
     * @var array
     */
    protected static $uri_interfaces = [
        LeagueUriInterface::class,
        UriInterface::class,
    ];

    /**
     * new instance
     *
     * @param array $map An override map of URI classes indexed by their supported schemes.
     */
    public function __construct($map = [])
    {
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
     * @throws Exception if the scheme is invalid
     * @throws Exception if the class does not implements a supported interface
     */
    protected function addMap(string $scheme, string $className)
    {
        if (!is_scheme($scheme)) {
            throw new Exception(sprintf('Please verify the submitted scheme `%s`', $scheme));
        }

        if (empty(array_intersect((new ReflectionClass($className))->getInterfaceNames(), self::$uri_interfaces))) {
            throw new Exception(sprintf('Please verify the submitted class `%s`', $className));
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
     * @throws Exception if there's no base URI and the submitted URI is not absolute
     *
     * @return LeagueUriInterface|UriInterface
     */
    public function create(string $uri, $base_uri = null)
    {
        $components = parse($uri);
        if (null !== $base_uri) {
            $base_uri = $this->filterBaseUri($base_uri);
            $className = $this->getClassName($components['scheme'], $base_uri);

            return $this->resolve($this->newInstance($components, $className), $base_uri);
        }

        if (null !== $components['scheme']) {
            $className = $this->getClassName($components['scheme']);

            return $this->newInstance($components, $className);
        }

        throw new Exception(sprintf('the submitted URI `%s` must be an absolute URI', $uri));
    }

    /**
     * Returns the Base URI.
     *
     * @param LeagueUriInterface|UriInterface|string $uri
     *
     * @throws Exception if the Base Uri is not an absolute URI
     *
     * @return LeagueUriInterface|UriInterface
     */
    protected function filterBaseUri($uri)
    {
        if (!$uri instanceof UriInterface && !$uri instanceof LeagueUriInterface) {
            return $this->create($uri);
        }

        if ('' !== $uri->getScheme()) {
            return $uri;
        }

        throw new Exception(sprintf('The submitted URI `%s` must be an absolute URI', $uri));
    }

    /**
     * Returns the className to use to instantiate the URI object.
     *
     * @param string|null $scheme   URI scheme component
     * @param mixed       $base_uri base URI object
     *
     * @return string
     */
    protected function getClassName($scheme, $base_uri = null): string
    {
        $scheme = strtolower($scheme ?? '');
        if (isset($base_uri) && in_array($scheme, [$base_uri->getScheme(), ''])) {
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
    protected function newInstance(array $components, string $className)
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

    /**
     * Resolve an URI against a base URI.
     *
     * @param LeagueUriInterface|UriInterface $uri
     * @param LeagueUriInterface|UriInterface $base_uri
     *
     * @return LeagueUriInterface|UriInterface
     */
    protected function resolve($uri, $base_uri)
    {
        if ('' !== $uri->getScheme()) {
            return $uri
                ->withPath($this->removeDotSegments($uri->getPath()));
        }

        if ('' !== $uri->getAuthority()) {
            return $uri
                ->withScheme($base_uri->getScheme())
                ->withPath($this->removeDotSegments($uri->getPath()));
        }

        list($base_uri_user, $base_uri_pass) = explode(':', $base_uri->getUserInfo(), 2) + ['', null];
        list($uri_path, $uri_query) = $this->resolvePathAndQuery($uri, $base_uri);

        return $uri
            ->withPath($this->removeDotSegments($uri_path))
            ->withQuery($uri_query)
            ->withHost($base_uri->getHost())
            ->withPort($base_uri->getPort())
            ->withUserInfo($base_uri_user, $base_uri_pass)
            ->withScheme($base_uri->getScheme())
        ;
    }

    /**
     * Remove dot segments from the URI path.
     *
     * @internal used internally to create an URI object
     *
     * @param string $path
     *
     * @return string
     */
    protected function removeDotSegments(string $path): string
    {
        if (false === strpos($path, '.')) {
            return $path;
        }

        $old_segments = explode('/', $path);
        $new_path = implode('/', array_reduce($old_segments, [$this, 'reducer'], []));
        if (isset(self::$dot_segments[end($old_segments)])) {
            $new_path .= '/';
        }

        if (strpos($path, '/') === 0 && strpos($new_path, '/') !== 0) {
            return '/'.$new_path;
        }

        return $new_path;
    }

    /**
     * Remove dot segments.
     *
     * @param array  $carry
     * @param string $segment
     *
     * @return array
     */
    protected function reducer(array $carry, string $segment)
    {
        if ('..' === $segment) {
            array_pop($carry);

            return $carry;
        }

        if (!isset(self::$dot_segments[$segment])) {
            $carry[] = $segment;
        }

        return $carry;
    }

    /**
     * Resolve an URI path and query component.
     *
     * @internal used internally to create an URI object
     *
     * @param LeagueUriInterface|UriInterface $uri
     * @param LeagueUriInterface|UriInterface $base_uri
     *
     * @return string[]
     */
    protected function resolvePathAndQuery($uri, $base_uri)
    {
        $target_path = $uri->getPath();
        $target_query = $uri->getQuery();

        if (0 === strpos($target_path, '/')) {
            return [$target_path, $target_query];
        }

        if ('' === $target_path) {
            if ('' === $target_query) {
                $target_query = $base_uri->getQuery();
            }

            $target_path = $base_uri->getPath();
            //@codeCoverageIgnoreStart
            //because some PSR-7 Uri implementations allow this RFC3986 forbidden construction
            if ('' !== $base_uri->getAuthority() && 0 !== strpos($target_path, '/')) {
                $target_path = '/'.$target_path;
            }
            //@codeCoverageIgnoreEnd

            return [$target_path, $target_query];
        }

        $base_path = $base_uri->getPath();
        if ('' !== $base_uri->getAuthority() && '' === $base_path) {
            $target_path = '/'.$target_path;
        }

        if ('' !== $base_path) {
            $segments = explode('/', $base_path);
            array_pop($segments);
            if (!empty($segments)) {
                $target_path = implode('/', $segments).'/'.$target_path;
            }
        }

        return [$target_path, $target_query];
    }
}
