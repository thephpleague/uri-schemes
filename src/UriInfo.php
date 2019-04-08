<?php

/**
 * League.Uri (http://uri.thephpleague.com)
 *
 * @package    League\Uri
 * @subpackage League\Uri\Schemes
 * @author     Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @license    https://github.com/thephpleague/uri-schemes/blob/master/LICENSE (MIT License)
 * @version    2.0.0
 * @link       https://github.com/thephpleague/uri-schemes
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace League\Uri;

use Psr\Http\Message\UriInterface;
use TypeError;
use function explode;
use function implode;
use function preg_replace_callback;
use function rawurldecode;
use function sprintf;

final class UriInfo
{
    private const REGEXP_ENCODED_CHARS = ',%(2[D|E]|3[0-9]|4[1-9|A-F]|5[0-9|A|F]|6[1-9|A-F]|7[0-9|E]),i';

    /**
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    /**
     * Filter the URI object.
     *
     * To be valid an URI MUST implement at least one of the following interface:
     *     - League\Uri\RFC3986Uri
     *     - Psr\Http\Message\UriInterface
     *
     * @param null|mixed $uri
     *
     * @throws TypeError if the URI object does not implements the supported interfaces.
     *
     * @return UriInterface|RFC3986Uri
     */
    private static function filterUri($uri)
    {
        if ($uri instanceof UriInterface || $uri instanceof RFC3986Uri) {
            return $uri;
        }

        throw new TypeError(sprintf('The uri must be a valid URI object received `%s`', is_object($uri) ? get_class($uri) : gettype($uri)));
    }

    /**
     * Normalize an URI for comparison.
     *
     * @param UriInterface|RFC3986Uri $uri
     *
     * @return UriInterface|RFC3986Uri
     */
    private static function normalize($uri)
    {
        $uri = self::filterUri($uri);
        $null = $uri instanceof UriInterface ? '' : null;

        $path = $uri->getPath();
        if ('/' === ($path[0] ?? '') || '' !== $uri->getScheme().$uri->getAuthority()) {
            $path = Resolver::resolve($uri, $uri->withPath('')->withQuery($null))->getPath();
        }

        $query = $uri->getQuery();
        $fragment = $uri->getFragment();
        $fragmentOrig = $fragment;
        $pairs = null === $query ? [] : explode('&', $query);
        sort($pairs, SORT_REGULAR);

        $replace = static function (array $matches): string {
            return rawurldecode($matches[0]);
        };

        $retval = preg_replace_callback(self::REGEXP_ENCODED_CHARS, $replace, [$path, implode('&', $pairs), $fragment]);
        if (null !== $retval) {
            [$path, $query, $fragment] = $retval + ['', $null, $null];
        }

        if ($null !== $uri->getAuthority() && '' === $path) {
            $path = '/';
        }

        return $uri
            ->withHost(Uri::createFromComponents(['host' => $uri->getHost()])->getHost())
            ->withPath($path)
            ->withQuery([] === $pairs ? $null : $query)
            ->withFragment($null === $fragmentOrig ? $fragmentOrig : $fragment);
    }

    /**
     * Tell whether the URI represents an absolute URI.
     *
     * @param UriInterface|RFC3986Uri $uri
     */
    public static function isAbsolute($uri): bool
    {
        $null = $uri instanceof UriInterface ? '' : null;

        return $null !== self::filterUri($uri)->getScheme();
    }

    /**
     * Tell whether the URI represents a network path.
     *
     * @param UriInterface|RFC3986Uri $uri
     */
    public static function isNetworkPath($uri): bool
    {
        $uri = self::filterUri($uri);
        $null = $uri instanceof UriInterface ? '' : null;

        return $null === $uri->getScheme() && $null !== $uri->getAuthority();
    }

    /**
     * Tell whether the URI represents an absolute path.
     *
     * @param UriInterface|RFC3986Uri $uri
     */
    public static function isAbsolutePath($uri): bool
    {
        $uri = self::filterUri($uri);
        $null = $uri instanceof UriInterface ? '' : null;

        return $null === $uri->getScheme()
            && $null === $uri->getAuthority()
            && '/' === ($uri->getPath()[0] ?? '');
    }

    /**
     * Tell whether the URI represents a relative path.
     *
     * @param UriInterface|RFC3986Uri $uri
     */
    public static function isRelativePath($uri): bool
    {
        $uri = self::filterUri($uri);
        $null = $uri instanceof UriInterface ? '' : null;

        return $null === $uri->getScheme()
            && $null === $uri->getAuthority()
            && '/' !== ($uri->getPath()[0] ?? '');
    }

    /**
     * Tell whether both URI refers to the same document.
     *
     * @param UriInterface|RFC3986Uri $uri
     * @param UriInterface|RFC3986Uri $base_uri
     */
    public static function isSameDocument($uri, $base_uri): bool
    {
        $uri = self::normalize($uri);
        $base_uri = self::normalize($base_uri);

        return (string) $uri->withFragment($uri instanceof UriInterface ? '' : null)
            === (string) $base_uri->withFragment($base_uri instanceof UriInterface ? '' : null);
    }
}
