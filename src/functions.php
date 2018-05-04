<?php
/**
 * League.Uri (http://uri.thephpleague.com)
 *
 * @package    League.uri
 * @subpackage League\Uri\Modifiers
 * @author     Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @copyright  2017 Ignace Nyamagana Butera
 * @license    https://github.com/thephpleague/uri-manipulations/blob/master/LICENSE (MIT License)
 * @version    2.0.0
 * @link       https://github.com/thephpleague/uri-manipulations
 */
declare(strict_types=1);

namespace League\Uri;

use League\Uri\UriInterface as LeagueUriInterface;
use Psr\Http\Message\UriInterface;
use TypeError;

/**
 * Create a new URI optionally according to
 * a base URI object
 *
 * @see Uri\Factory::__construct
 * @see Uri\Factory::create
 *
 * @param mixed $uri
 * @param mixed $base_uri
 *
 * @return LeagueUriInterface|UriInterface
 */
function create($uri, $base_uri = null)
{
    static $factory;

    $factory = $factory ?? new Factory();

    return $factory->create($uri, $base_uri);
}

/**
 * Resolve an URI against a base URI.
 *
 * @see Uri\Resolver::resolve()
 *
 * @param LeagueUriInterface|UriInterface $uri
 * @param LeagueUriInterface|UriInterface $base_uri
 *
 * @return LeagueUriInterface|UriInterface
 */
function resolve($uri, $base_uri)
{
    static $resolver;

    $resolver = $resolver ?? new Resolver();

    return $resolver->resolve($uri, $base_uri);
}

/**
 * Relativize an URI against a base URI.
 *
 * @see Relativizer::relativize()
 *
 * @param LeagueUriInterface|UriInterface $uri
 * @param LeagueUriInterface|UriInterface $base_uri
 *
 * @return LeagueUriInterface|UriInterface
 */
function relativize($uri, $base_uri)
{
    static $relativizer;

    $relativizer = $relativizer ?? new Relativizer();

    return $relativizer->relativize($uri, $base_uri);
}

/**
 * Normalize an URI for comparison.
 *
 * @param LeagueUriInterface|UriInterface $uri
 *
 * @return LeagueUriInterface|UriInterface
 */
function normalize($uri)
{
    if (!$uri instanceof LeagueUriInterface && !$uri instanceof UriInterface) {
        throw new TypeError(sprintf('The uri must be a valid URI object received `%s`', gettype($uri)));
    }

    $path = $uri->getPath();
    if ('/' === ($path[0] ?? '') || '' !== $uri->getScheme().$uri->getAuthority()) {
        $path = resolve($uri, $uri->withPath('')->withQuery(''))->getPath();
    }

    $query = $uri->getQuery();
    $pairs = explode('&', $query);
    sort($pairs, SORT_REGULAR);

    static $pattern = ',%(2[D|E]|3[0-9]|4[1-9|A-F]|5[0-9|A|F]|6[1-9|A-F]|7[0-9|E]),i';

    $replace = function (array $matches): string {
        return rawurldecode($matches[0]);
    };

    list($path, $query, $fragment) = preg_replace_callback($pattern, $replace, [$path, implode('&', $pairs), $uri->getFragment()]);

    return $uri
        ->withHost(Uri::createFromComponents(['host' => $uri->getHost()])->getHost())
        ->withPath($path)
        ->withQuery($query)
        ->withFragment($fragment)
    ;
}

/**
 * Tell whether the URI represents an absolute URI.
 *
 * @param LeagueUriInterface|UriInterface $uri
 *
 * @return bool
 */
function is_absolute($uri): bool
{
    if (!$uri instanceof LeagueUriInterface && !$uri instanceof UriInterface) {
        throw new TypeError(sprintf('The uri must be a valid URI object received `%s`', gettype($uri)));
    }

    return '' !== $uri->getScheme();
}

/**
 * Tell whether the URI represents a network path.
 *
 * @param LeagueUriInterface|UriInterface $uri
 *
 * @return bool
 */
function is_network_path($uri): bool
{
    if (!$uri instanceof LeagueUriInterface && !$uri instanceof UriInterface) {
        throw new TypeError(sprintf('The uri must be a valid URI object received `%s`', gettype($uri)));
    }

    return '' === $uri->getScheme()
        && '' !== $uri->getAuthority();
}

/**
 * Tell whether the URI represents an absolute path.
 *
 * @param LeagueUriInterface|UriInterface $uri
 *
 * @return bool
 */
function is_absolute_path($uri): bool
{
    if (!$uri instanceof LeagueUriInterface && !$uri instanceof UriInterface) {
        throw new TypeError(sprintf('The uri must be a valid URI object received `%s`', gettype($uri)));
    }

    return '' === $uri->getScheme().$uri->getAuthority()
        && '/' === substr($uri->getPath(), 0, 1);
}

/**
 * Tell whether the URI represents a relative path.
 *
 * @param LeagueUriInterface|UriInterface $uri
 *
 * @return bool
 */
function is_relative_path($uri): bool
{
    if (!$uri instanceof LeagueUriInterface && !$uri instanceof UriInterface) {
        throw new TypeError(sprintf('The uri must be a valid URI object received `%s`', gettype($uri)));
    }

    return '' === $uri->getScheme().$uri->getAuthority()
        && '/' !== substr($uri->getPath(), 0, 1);
}

/**
 * Tell whether both URI refers to the same document.
 *
 * @param LeagueUriInterface|UriInterface $uri
 * @param LeagueUriInterface|UriInterface $base_uri
 *
 * @return bool
 */
function is_same_document($uri, $base_uri): bool
{
    return (string) normalize($uri)->withFragment('') === (string) normalize($base_uri)->withFragment('');
}
