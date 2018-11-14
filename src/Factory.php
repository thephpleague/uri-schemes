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
            $uri = self::getUriObject($components['scheme'], $base_uri)::createFromComponents($components);
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
    private static function getUriObject(?string $scheme, $base_uri): string
    {
        if ($base_uri instanceof Psr7UriInterface && null === $scheme) {
            $scheme = 'http';
        }

        return self::SCHEME_TO_URI_LIST[strtolower($scheme ?? '')] ?? Uri::class;
    }
}
