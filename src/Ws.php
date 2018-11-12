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

final class Ws extends Uri
{
    /**
     * {@inheritdoc}
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
            && !('' != $this->scheme && null === $this->host)
            && (null === $this->port || (0 < $this->port && 65536 > $this->port));
    }
}
