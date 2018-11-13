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
use function array_pop;
use function array_reduce;
use function end;
use function explode;
use function gettype;
use function implode;
use function sprintf;
use function strpos;

final class Resolver
{
    /**
     * @var array
     */
    const DOT_SEGMENTS = ['.' => 1, '..' => 1];

    /**
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    /**
     * Resolve an URI against a base URI using RFC3986 rules.
     *
     * @param Psr7UriInterface|UriInterface $uri
     * @param Psr7UriInterface|UriInterface $base_uri
     *
     * @return Psr7UriInterface|UriInterface
     */
    public static function resolve($uri, $base_uri)
    {
        self::filterUri($uri);
        self::filterUri($base_uri);

        if ('' !== $uri->getScheme()) {
            return $uri
                ->withPath(self::removeDotSegments($uri->getPath()));
        }

        if ('' !== $uri->getAuthority()) {
            return $uri
                ->withScheme($base_uri->getScheme())
                ->withPath(self::removeDotSegments($uri->getPath()));
        }

        [$user, $pass] = explode(':', $base_uri->getUserInfo(), 2) + [1 => null];
        [$uri_path, $uri_query] = self::resolvePathAndQuery($uri, $base_uri);

        return $uri
            ->withPath(self::removeDotSegments($uri_path))
            ->withQuery($uri_query)
            ->withHost($base_uri->getHost())
            ->withPort($base_uri->getPort())
            ->withUserInfo((string) $user, $pass)
            ->withScheme($base_uri->getScheme())
        ;
    }

    /**
     * Filter the URI object.
     *
     * @param null|mixed $uri
     *
     * @throws TypeError if the URI object does not implements the supported interfaces.
     */
    private static function filterUri($uri): void
    {
        if (!$uri instanceof Psr7UriInterface && !$uri instanceof UriInterface) {
            throw new TypeError(sprintf('The uri must be a valid URI object received `%s`', gettype($uri)));
        }
    }

    /**
     * Remove dot segments from the URI path.
     */
    private static function removeDotSegments(string $path): string
    {
        if (false === strpos($path, '.')) {
            return $path;
        }

        $old_segments = explode('/', $path);
        $new_path = implode('/', array_reduce($old_segments, [Resolver::class, 'reducer'], []));
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
     */
    private static function reducer(array $carry, string $segment): array
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
     * @param Psr7UriInterface|UriInterface $uri
     * @param Psr7UriInterface|UriInterface $base_uri
     *
     * @return string[]
     */
    private static function resolvePathAndQuery($uri, $base_uri): array
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
            if ([] !== $segments) {
                $target_path = implode('/', $segments).'/'.$target_path;
            }
        }

        return [$target_path, $target_query];
    }
}
