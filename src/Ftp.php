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

use League\Uri\Interfaces\Uri;

/**
 * Immutable Value object representing a FTP Uri.
 *
 * @package    League\Uri
 * @subpackage League\Uri\Schemes
 * @author     Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @since      1.0.0
 */
class Ftp extends AbstractUri implements Uri
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
     * Tell whether the current Authority is valid
     *
     * @return bool
     */
    protected function isAllowedAuthority()
    {
        if ('' != $this->scheme && null === $this->host) {
            return false;
        }

        return '' !== $this->host;
    }

    /**
     * Tell whether the FTP URI is in valid state.
     *
     * @return bool
     */
    protected function isValidUri()
    {
        return null === $this->fragment
            && null === $this->query
            && $this->isValidGenericUri()
            && $this->isAllowedAuthority();
    }
}
