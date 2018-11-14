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

use Psr\Http\Message\UriInterface as Psr7UriInterface;
use TypeError;
use function explode;
use function implode;
use function preg_replace_callback;
use function rawurldecode;
use function sprintf;

final class Info
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
     * @param null|mixed $uri
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

        $path = $uri->getPath();
        if ('/' === ($path[0] ?? '') || '' !== $uri->getScheme().$uri->getAuthority()) {
            $path = Resolver::resolve($uri, $uri->withPath('')->withQuery(''))->getPath();
        }

        $query = $uri->getQuery();
        $fragment = $uri->getFragment();
        $pairs = explode('&', $query);
        sort($pairs, SORT_REGULAR);

        $replace = static function (array $matches): string {
            return rawurldecode($matches[0]);
        };

        $retval = preg_replace_callback(self::REGEXP_ENCODED_CHARS, $replace, [$path, implode('&', $pairs), $fragment]);
        if (null !== $retval) {
            [$path, $query, $fragment] = $retval + ['', '', ''];
        }

        if ('' !== $uri->getAuthority() && '' === $path) {
            $path = '/';
        }

        return $uri
            ->withHost(Uri::createFromComponents(['host' => $uri->getHost()])->getHost())
            ->withPath($path)
            ->withQuery($query)
            ->withFragment($fragment);
    }

    /**
     * Tell whether the URI represents an absolute URI.
     *
     * @param Psr7UriInterface|UriInterface $uri
     */
    public static function isAbsolute($uri): bool
    {
        return '' !== self::filterUri($uri)->getScheme();
    }

    /**
     * Tell whether the URI represents a network path.
     *
     * @param Psr7UriInterface|UriInterface $uri
     */
    public static function isNetworkPath($uri): bool
    {
        $uri = self::filterUri($uri);

        return '' === $uri->getScheme() && '' !== $uri->getAuthority();
    }

    /**
     * Tell whether the URI represents an absolute path.
     *
     * @param Psr7UriInterface|UriInterface $uri
     */
    public static function isAbsolutePath($uri): bool
    {
        $uri = self::filterUri($uri);

        return '' === $uri->getScheme().$uri->getAuthority() && '/' === ($uri->getPath()[0] ?? '');
    }

    /**
     * Tell whether the URI represents a relative path.
     *
     * @param Psr7UriInterface|UriInterface $uri
     */
    public static function isRelativePath($uri): bool
    {
        $uri = self::filterUri($uri);

        return '' === $uri->getScheme().$uri->getAuthority()  && '/' !== ($uri->getPath()[0] ?? '');
    }

    /**
     * Tell whether both URI refers to the same document.
     *
     * @param Psr7UriInterface|UriInterface $uri
     * @param Psr7UriInterface|UriInterface $base_uri
     */
    public static function isSameDocument($uri, $base_uri): bool
    {
        return (string) self::normalize($uri)->withFragment('') === (string) self::normalize($base_uri)->withFragment('');
    }
}
