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
use TypeError;
use function get_class;
use function gettype;
use function is_object;
use function ltrim;
use function preg_match;
use function sprintf;
use function strtolower;

final class Factory
{
    private const REGEXP_FORMAT_URI = [
        '/[\r\t\n]/',
        '/[\x00-\x1f\s]$/',
        '/^[\x00-\x1f\s]/',
    ];

    private const SCHEME_TO_URI_LIST = [
        'http' => Http::class,
        'https' => Http::class,
        'ftp' => Ftp::class,
        'file' => File::class,
        'data' => Data::class,
        'ws' => Ws::class,
        'wss' => Ws::class,
        '' => Uri::class,
    ];

    private const SPECIAL_SCHEMES_LIST = [
        'ftp' => 21,
        'file' => null,
        'gopher' => 70,
        'http' => 80,
        'https' => 443,
        'ws' => 80,
        'wss' => 443,
    ];

    private const REGEXP_WINDOW_PATH = ',^(?<driver>/[a-zA-Z]:/),';

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
     * @throws MalformedUri if there's no base URI and the submitted URI is not absolute
     *
     * @return Psr7UriInterface|UriInterface
     */
    public static function create($uri, $base_uri = null)
    {
        if (null !== $base_uri) {
            $base_uri = self::create($base_uri);
        }

        if (!$uri instanceof UriInterface && !$uri instanceof Psr7UriInterface) {
            $components = RFC3986::parse(self::filterUri($uri));
            $components = self::sanitizeComponents($components, $base_uri);
            $className = self::getClassName($components['scheme'], $base_uri);
            $uri = $className::createFromComponents($components);
        }

        if (null !== $base_uri && '' !== $base_uri->getAuthority() && '' !== $base_uri->getScheme()) {
            return self::formatUri(Resolver::resolve($uri, $base_uri), $base_uri);
        }

        if ('' === $uri->getScheme()) {
            throw new MalformedUri(sprintf('the URI `%s` must be absolute', (string) $uri));
        }

        if ('' === $uri->getAuthority()) {
            return self::formatUri($uri, $uri);
        }

        $base_uri = $uri->withFragment('')->withQuery('')->withPath('');

        return self::formatUri(Resolver::resolve($uri, $base_uri), $base_uri);
    }

    /**
     * Remove the default Port for the Gopher scheme.
     *
     * @param Psr7UriInterface|UriInterface $uri
     * @param Psr7UriInterface|UriInterface $originalUri
     *
     * @return Psr7UriInterface|UriInterface
     */
    private static function formatUri($uri, $originalUri)
    {
        if ('gopher' === $uri->getScheme() && 70 === $uri->getPort()) {
            return $uri->withPort(null);
        }

        if ('file' === $uri->getScheme()
            && 1 === preg_match(self::REGEXP_WINDOW_PATH, $originalUri->getPath(), $matches)
            && 0 === preg_match(self::REGEXP_WINDOW_PATH, $uri->getPath())
        ) {
            return $uri->withPath($matches['driver'].ltrim('/', $uri->getPath()));
        }

        return $uri;
    }

    /**
     * Format returned components according to the living standard rules.
     *
     * @see https://url.spec.whatwg.org/#urls
     */
    private static function sanitizeComponents(array $components, $base_uri): array
    {
        if (null !== $components['host']) {
            $components['host'] = str_replace('\\', '/', $components['host']);
        }

        $components['path'] = str_replace(['\\', ' '], ['/', '%20'], $components['path']);
        if (null === $components['host'] && ':' === ($components['path'][0] ?? '')) {
            $components['path'] = './'.$components['path'];
        }

        $scheme = strtolower($components['scheme'] ?? '');
        if (!isset(self::SPECIAL_SCHEMES_LIST[$scheme])) {
            if (null === $base_uri) {
                return $components;
            }

            return $components;
        }

        if (in_array($components['host'], [null, ''], true) && '/' === ($components['path'][0] ?? '')) {
            $path = ltrim($components['path'], '/');
            [$host, $path] = explode('/', $path, 2) + [1 => ''];
            $components['host'] = $host;
            $components['path'] = '/'.$path;

            return $components;
        }

        if (null === $components['host'] && '' !== $components['path']) {
            if (null !== $base_uri) {
                $components['scheme'] = null;

                return $components;
            }

            [$host, $path] = explode('/', $components['path'], 2) + [1 => ''];
            $components['host'] = $host;
            $components['path'] = $path;

            return $components;
        }

        return $components;
    }

    /**
     * Filter Uri string.
     *
     * - Remove any leading and trailing C0 control or space from input.
     * - Remove all ASCII tab or newline from input.
     *
     * @see https://url.spec.whatwg.org/#url-parsing
     *
     * @param null|mixed $uri
     *
     * @throws TypeError if the URI can not be converted to a string
     */
    private static function filterUri($uri): string
    {
        if (is_scalar($uri) || method_exists($uri, '__toString')) {
            return (string) preg_replace(self::REGEXP_FORMAT_URI, '', (string) $uri);
        }

        throw new TypeError(sprintf('The uri must be a scalar or a stringable object `%s` given', is_object($uri) ? get_class($uri) : gettype($uri)));
    }

    /**
     * Returns the className to use to instantiate the URI object.
     *
     * @param ?string $scheme
     */
    private static function getClassName(?string $scheme, $base_uri): string
    {
        if (null !== $base_uri && null === $scheme) {
            $scheme = $base_uri->getScheme();
        }

        return self::SCHEME_TO_URI_LIST[strtolower($scheme ?? '')] ?? Uri::class;
    }
}
