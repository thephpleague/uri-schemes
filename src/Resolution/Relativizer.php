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
use League\Uri\UriInterface as LeagueUriInterface;
use Psr\Http\Message\UriInterface;
use TypeError;

final class Relativizer
{
    /**
     * Relativize an URI according to a base URI
     *
     * This method MUST retain the state of the submitted URI instance, and return
     * an URI instance of the same type that contains the applied modifications.
     *
     * This method MUST be transparent when dealing with error and exceptions.
     * It MUST not alter of silence them apart from validating its own parameters.
     *
     * @param LeagueUriInterface|UriInterface $uri
     * @param LeagueUriInterface|UriInterface $base_uri
     *
     * @return LeagueUriInterface|UriInterface
     */
    public function relativize($uri, $base_uri)
    {
        $uri = $this->filterUri($uri);
        $base_uri = $this->filterUri($base_uri);
        if (!$this->isRelativizable($uri, $base_uri)) {
            return $uri;
        }

        $uri = $uri->withScheme('')->withPort(null)->withUserInfo('')->withHost('');
        $target_path = $uri->getPath();
        if ($target_path !== $base_uri->getPath()) {
            return $uri->withPath($this->relativizePath($target_path, $base_uri->getPath()));
        }

        if ($uri->getQuery() === $base_uri->getQuery()) {
            return $uri->withPath('')->withQuery('');
        }

        if ('' === $uri->getQuery()) {
            return $uri->withPath($this->formatPathWithEmptyBaseQuery($target_path));
        }

        return $uri->withPath('');
    }

    /**
     * Filter the URI object.
     *
     * @param mixed $uri
     *
     * @throws TypeError if the URI object does not implements the supported interfaces.
     *
     * @return LeagueUriInterface|UriInterface
     */
    private function filterUri($uri)
    {
        if (!$uri instanceof LeagueUriInterface && !$uri instanceof UriInterface) {
            throw new TypeError(sprintf('The uri must be a valid URI object received `%s`', gettype($uri)));
        }

        return $uri->withHost(Uri\Uri::createFromComponents(['host' => $uri->getHost()])->getHost());
    }

    /**
     * Tell whether the submitted URI object can be relativize.
     *
     * @param LeagueUriInterface|UriInterface $uri
     * @param LeagueUriInterface|UriInterface $base_uri
     *
     * @return bool
     */
    private function isRelativizable($uri, $base_uri): bool
    {
        return $base_uri->getScheme() === $uri->getScheme()
            && $base_uri->getAuthority() === $uri->getAuthority()
            && !Uri\is_relative_path($uri)
        ;
    }

    /**
     * Relative the URI for a authority-less target URI.
     *
     * @param string $path
     * @param string $basepath
     *
     * @return string
     */
    private function relativizePath(string $path, string $basepath): string
    {
        $base_segments = $this->getSegments($basepath);
        $target_segments = $this->getSegments($path);
        $target_basename = array_pop($target_segments);
        array_pop($base_segments);
        foreach ($base_segments as $offset => $segment) {
            if (!isset($target_segments[$offset]) || $segment !== $target_segments[$offset]) {
                break;
            }
            unset($base_segments[$offset], $target_segments[$offset]);
        }
        $target_segments[] = $target_basename;

        return $this->formatPath(
            str_repeat('../', count($base_segments)).implode('/', $target_segments),
            $basepath
        );
    }

    /**
     * returns the path segments.
     *
     * @param string $path
     *
     * @return array
     */
    private function getSegments(string $path): array
    {
        if ('' !== $path && '/' === $path[0]) {
            $path = substr($path, 1);
        }

        return explode('/', $path);
    }

    /**
     * Formatting the path to keep a valid URI.
     *
     * @param string $path
     * @param string $basepath
     *
     * @return string
     */
    private function formatPath(string $path, string $basepath): string
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
     *
     * @param string $path
     *
     * @return string
     */
    private function formatPathWithEmptyBaseQuery(string $path): string
    {
        $target_segments = $this->getSegments($path);
        $basename = end($target_segments);

        return '' === $basename ? './' : $basename;
    }
}
