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

namespace League\Uri;

use League\Uri\UriInterface as LeagueUriInterface;
use Psr\Http\Message\UriInterface;
use TypeError;

final class Resolver
{
    /**
     * @var array
     */
    const DOT_SEGMENTS = ['.' => 1, '..' => 1];

    /**
     * Resolve an URI against a base URI using RFC3986 rules.
     *
     * @param LeagueUriInterface|UriInterface $uri
     * @param LeagueUriInterface|UriInterface $base_uri
     *
     * @return LeagueUriInterface|UriInterface
     */
    public function resolve($uri, $base_uri)
    {
        $this->filterUri($uri);
        $this->filterUri($base_uri);

        if ('' !== $uri->getScheme()) {
            return $uri
                ->withPath($this->removeDotSegments($uri->getPath()));
        }

        if ('' !== $uri->getAuthority()) {
            return $uri
                ->withScheme($base_uri->getScheme())
                ->withPath($this->removeDotSegments($uri->getPath()));
        }

        list($base_uri_user, $base_uri_pass) = explode(':', $base_uri->getUserInfo(), 2) + [1 => null];
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
     * Filter the URI object.
     *
     * @param mixed $uri
     *
     * @throws TypeError if the URI object does not implements the supported interfaces.
     */
    private function filterUri($uri)
    {
        if (!$uri instanceof LeagueUriInterface && !$uri instanceof UriInterface) {
            throw new TypeError(sprintf('The uri must be a valid URI object received `%s`', gettype($uri)));
        }
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
    private function removeDotSegments(string $path): string
    {
        if (false === strpos($path, '.')) {
            return $path;
        }

        $old_segments = explode('/', $path);
        $new_path = implode('/', array_reduce($old_segments, [$this, 'reducer'], []));
        if (isset(self::DOT_SEGMENTS[end($old_segments)])) {
            $new_path .= '/';
        }

        // @codeCoverageIgnoreStart
        // added because some PSR-7 implementations do not respect RFC3986
        if (strpos($path, '/') === 0 && strpos($new_path, '/') !== 0) {
            return '/'.$new_path;
        }
        // @codeCoverageIgnoreEnd

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
    private function reducer(array $carry, string $segment): array
    {
        if ('..' === $segment) {
            array_pop($carry);

            return $carry;
        }

        if (!isset(self::DOT_SEGMENTS[$segment])) {
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
    private function resolvePathAndQuery($uri, $base_uri): array
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
