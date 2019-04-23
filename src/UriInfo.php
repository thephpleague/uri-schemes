<?php

/**
 * League.Uri (https://uri.thephpleague.com)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace League\Uri;

use League\Uri\Contract\UriInterface;
use Psr\Http\Message\UriInterface as Psr7UriInterface;
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
     *     - League\Uri\UriInterface
     *     - Psr\Http\Message\UriInterface
     *
     * @param mixed $uri the URI to validate
     *
     * @throws TypeError if the URI object does not implements the supported interfaces.
     *
     * @return Psr7UriInterface|UriInterface
     */
    private static function filterUri($uri)
    {
        if ($uri instanceof Psr7UriInterface || $uri instanceof UriInterface) {
            return $uri;
        }

        throw new TypeError(sprintf('The uri must be a valid URI object received `%s`', is_object($uri) ? get_class($uri) : gettype($uri)));
    }

    /**
     * Normalize an URI for comparison.
     *
     * @param Psr7UriInterface|UriInterface $uri
     *
     * @return Psr7UriInterface|UriInterface
     */
    private static function normalize($uri)
    {
        $uri = self::filterUri($uri);
        $null = $uri instanceof Psr7UriInterface ? '' : null;

        $path = $uri->getPath();
        if ('/' === ($path[0] ?? '') || '' !== $uri->getScheme().$uri->getAuthority()) {
            $path = UriResolver::resolve($uri, $uri->withPath('')->withQuery($null))->getPath();
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
     * @param Psr7UriInterface|UriInterface $uri
     */
    public static function isAbsolute($uri): bool
    {
        $null = $uri instanceof Psr7UriInterface ? '' : null;

        return $null !== self::filterUri($uri)->getScheme();
    }

    /**
     * Tell whether the URI represents a network path.
     *
     * @param Psr7UriInterface|UriInterface $uri
     */
    public static function isNetworkPath($uri): bool
    {
        $uri = self::filterUri($uri);
        $null = $uri instanceof Psr7UriInterface ? '' : null;

        return $null === $uri->getScheme() && $null !== $uri->getAuthority();
    }

    /**
     * Tell whether the URI represents an absolute path.
     *
     * @param Psr7UriInterface|UriInterface $uri
     */
    public static function isAbsolutePath($uri): bool
    {
        $uri = self::filterUri($uri);
        $null = $uri instanceof Psr7UriInterface ? '' : null;

        return $null === $uri->getScheme()
            && $null === $uri->getAuthority()
            && '/' === ($uri->getPath()[0] ?? '');
    }

    /**
     * Tell whether the URI represents a relative path.
     *
     * @param Psr7UriInterface|UriInterface $uri
     */
    public static function isRelativePath($uri): bool
    {
        $uri = self::filterUri($uri);
        $null = $uri instanceof Psr7UriInterface ? '' : null;

        return $null === $uri->getScheme()
            && $null === $uri->getAuthority()
            && '/' !== ($uri->getPath()[0] ?? '');
    }

    /**
     * Tell whether both URI refers to the same document.
     *
     * @param Psr7UriInterface|UriInterface $uri
     * @param Psr7UriInterface|UriInterface $base_uri
     */
    public static function isSameDocument($uri, $base_uri): bool
    {
        $uri = self::normalize($uri);
        $base_uri = self::normalize($base_uri);

        return (string) $uri->withFragment($uri instanceof Psr7UriInterface ? '' : null)
            === (string) $base_uri->withFragment($base_uri instanceof Psr7UriInterface ? '' : null);
    }
}
