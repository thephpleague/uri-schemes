<?php

/**
 * League.Uri (http://uri.thephpleague.com).
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

use Psr\Http\Message\UriInterface as Psr7UriInterface;
use TypeError;
use function array_pop;
use function count;
use function end;
use function explode;
use function gettype;
use function implode;
use function in_array;
use function sprintf;
use function str_repeat;
use function strpos;
use function substr;

final class Relativizer
{
    /**
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    /**
     * Relativize an URI according to a base URI.
     *
     * This method MUST retain the state of the submitted URI instance, and return
     * an URI instance of the same type that contains the applied modifications.
     *
     * This method MUST be transparent when dealing with error and exceptions.
     * It MUST not alter of silence them apart from validating its own parameters.
     *
     * @param Psr7UriInterface|Uri $uri
     * @param Psr7UriInterface|Uri $base_uri
     *
     * @return Psr7UriInterface|Uri
     */
    public static function relativize($uri, $base_uri)
    {
        $uri = self::filterUri($uri);
        $base_uri = self::filterUri($base_uri);
        if (!self::isRelativizable($uri, $base_uri)) {
            return $uri;
        }

        $null = $uri instanceof Psr7UriInterface ? '' : null;
        $uri = $uri->withScheme($null)->withPort(null)->withUserInfo($null)->withHost($null);
        $target_path = $uri->getPath();
        if ($target_path !== $base_uri->getPath()) {
            return $uri->withPath(self::relativizePath($target_path, $base_uri->getPath()));
        }

        if ($uri->getQuery() === $base_uri->getQuery()) {
            return $uri->withPath($null)->withQuery($null);
        }

        if ('' === $uri->getQuery()) {
            return $uri->withPath(self::formatPathWithEmptyBaseQuery($target_path));
        }

        return $uri->withPath('');
    }

    /**
     * Filter the URI object.
     *
     * @param null|mixed $uri
     *
     * @throws TypeError if the URI object does not implements the supported interfaces.
     *
     * @return Psr7UriInterface|Uri
     */
    private static function filterUri($uri)
    {
        if (!$uri instanceof Psr7UriInterface && !$uri instanceof Uri) {
            throw new TypeError(sprintf('The uri must be a valid URI object received `%s`', gettype($uri)));
        }

        return $uri->withHost(Uri::createFromComponents(['host' => $uri->getHost()])->getHost());
    }

    /**
     * Tell whether the submitted URI object can be relativize.
     *
     * @param Psr7UriInterface|Uri $uri
     * @param Psr7UriInterface|Uri $base_uri
     */
    private static function isRelativizable($uri, $base_uri): bool
    {
        return $base_uri->getScheme() === $uri->getScheme()
            && $base_uri->getAuthority() === $uri->getAuthority()
            && !UriInfo::isRelativePath($uri)
        ;
    }

    /**
     * Relative the URI for a authority-less target URI.
     */
    private static function relativizePath(string $path, string $basepath): string
    {
        $base_segments = self::getSegments($basepath);
        $target_segments = self::getSegments($path);
        $target_basename = array_pop($target_segments);
        array_pop($base_segments);
        foreach ($base_segments as $offset => $segment) {
            if (!isset($target_segments[$offset]) || $segment !== $target_segments[$offset]) {
                break;
            }
            unset($base_segments[$offset], $target_segments[$offset]);
        }
        $target_segments[] = $target_basename;

        return self::formatPath(
            str_repeat('../', count($base_segments)).implode('/', $target_segments),
            $basepath
        );
    }

    /**
     * returns the path segments.
     */
    private static function getSegments(string $path): array
    {
        if ('' !== $path && '/' === $path[0]) {
            $path = substr($path, 1);
        }

        return explode('/', $path);
    }

    /**
     * Formatting the path to keep a valid URI.
     */
    private static function formatPath(string $path, string $basepath): string
    {
        if ('' === $path) {
            return in_array($basepath, ['', '/'], true) ? $basepath : './';
        }

        if (false === ($colon_pos = strpos($path, ':'))) {
            return $path;
        }

        $slash_pos = strpos($path, '/');
        if (false === $slash_pos || $colon_pos < $slash_pos) {
            return "./$path";
        }

        return $path;
    }

    /**
     * Formatting the path to keep a resolvable URI.
     */
    private static function formatPathWithEmptyBaseQuery(string $path): string
    {
        $target_segments = self::getSegments($path);
        $basename = end($target_segments);

        return '' === $basename ? './' : $basename;
    }
}
