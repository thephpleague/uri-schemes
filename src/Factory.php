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

use League\Uri\Exception\MalformedUri;
use League\Uri\Parser\RFC3986;
use Psr\Http\Message\UriInterface as Psr7UriInterface;
use ReflectionClass;
use function sprintf;
use function strtolower;

final class Factory
{
    /**
     * @codeCoverageIgnore
     */
    private function __construct()
    {
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
    public static function create($uri, $base_uri = null)
    {
        if (null !== $base_uri) {
            $base_uri = self::create($base_uri);
        }

        if (!$uri instanceof UriInterface && !$uri instanceof Psr7UriInterface) {
            $components = RFC3986::parse($uri);
            $uri = self::getUriObject($components['scheme'], $base_uri)
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
    private static function getUriObject(string $scheme = null, $base_uri)
    {
        if ($base_uri instanceof Psr7UriInterface && null === $scheme) {
            return Http::createFromString('');
        }

        if (null !== $scheme) {
            $scheme = strtolower($scheme);
        }

        static $map = [
            'http' => Http::class,
            'https' => Http::class,
            'ftp' => Ftp::class,
            'file' => File::class,
            'data' => Data::class,
            'ws' => Ws::class,
            'wss' => Ws::class,
        ];

        return (new ReflectionClass($map[$scheme] ?? Uri::class))->newInstanceWithoutConstructor();
    }
}
