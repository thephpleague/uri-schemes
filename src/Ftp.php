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
     * Tell whether the FTP URI is in valid state.
     *
     * @return bool
     */
    protected function isValidUri()
    {
        $filter = function ($value) {
            return $value !== null;
        };

        $res = array_filter([$this->query, $this->fragment], $filter);

        return empty($res) && $this->isValidGenericUri() && $this->isAllowedAuthority();
    }
}
