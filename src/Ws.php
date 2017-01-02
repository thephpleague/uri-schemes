<?php
/**
 * League.Uri (http://uri.thephpleague.com)
 *
 * @package    League\Uri
 * @subpackage League\Uri\Schemes
 * @author     Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @copyright  2016 Ignace Nyamagana Butera
 * @license    https://github.com/thephpleague/uri-components/blob/master/LICENSE (MIT License)
 * @version    1.0.0
 * @link       https://github.com/thephpleague/uri-components
 */
declare(strict_types=1);

namespace League\Uri\Schemes;

/**
 * Immutable Value object representing a Ws(s) Uri.
 *
 * @package    League\Uri
 * @subpackage League\Uri\Schemes
 * @author     Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @since      1.0.0
 */
class Ws extends AbstractUri
{
    /**
     * Supported schemes and corresponding default port
     *
     * @var array
     */
    protected static $supported_schemes = [
        'ws' => 80,
        'wss' => 443,
    ];

    /**
     * Tell whether the Ws(s) URI is in valid state according to RFC6455.
     *
     * A valid Ws(s) URI:
     *
     * <ul>
     * <li>can be schemeless or supports only 'ws' and 'wss' schemes
     * <li>can not contain a fragment component
     * <li>has the same validation rules as an HTTP(s) URI
     * </ul>
     *
     * @see https://tools.ietf.org/html/rfc6455#section-3
     *
     * @return bool
     */
    protected function isValidUri(): bool
    {
        return null === $this->fragment
            && '' !== $this->host
            && (null === $this->scheme || isset(static::$supported_schemes[$this->scheme]))
            && !('' != $this->scheme && null === $this->host);
    }
}
