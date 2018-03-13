<?php
/**
 * League.Uri (http://uri.thephpleague.com)
 *
 * @package    League.uri
 * @subpackage League\Uri\Modifiers
 * @author     Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @copyright  2017 Ignace Nyamagana Butera
 * @license    https://github.com/thephpleague/uri-manipulations/blob/master/LICENSE (MIT License)
 * @version    1.2.0
 * @link       https://github.com/thephpleague/uri-manipulations
 */
declare(strict_types=1);

namespace League\Uri;

use League\Uri\Interfaces\Uri as LeagueUriInterface;
use Psr\Http\Message\UriInterface;

/**
 * Create a new URI optionally according to
 * a base URI object
 *
 * @see Uri\Factory::__construct
 * @see Uri\Factory::create
 *
 * @param string $uri
 * @param mixed  $base_uri
 *
 * @return LeagueUriInterface|UriInterface
 */
function create(string $uri, $base_uri = null)
{
    static $factory;

    $factory = $factory ?? new Factory();

    return $factory->create($uri, $base_uri);
}
