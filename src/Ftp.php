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
namespace League\Uri\Schemes;

/**
 * Immutable Value object representing a FTP Uri.
 *
 * @package    League\Uri
 * @subpackage League\Uri\Schemes
 * @author     Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @since      1.0.0
 */
class Ftp extends AbstractUri
{
    /**
     * Supported schemes and corresponding default port
     *
     * @var array
     */
    protected static $supported_schemes = [
        'ftp' => 21,
    ];

    /**
     * Tell whether the FTP URI is in valid state.
     *
     * A valid FTP URI:
     *
     * <ul>
     * <li>can be schemeless or supports only 'ftp' scheme
     * <li>can not contain a fragment component
     * <li>can not contain a query component
     * <li>has the same validation rules as an HTTP(s) URI
     * </ul>
     *
     * @see https://tools.ietf.org/html/rfc1738#section-3.2
     *
     * @return bool
     */
    protected function isValidUri(): bool
    {
        return null === $this->query
            && null === $this->fragment
            && '' !== $this->host
            && (null === $this->scheme || isset(static::$supported_schemes[$this->scheme]))
            && !('' != $this->scheme && null === $this->host);
    }
}
