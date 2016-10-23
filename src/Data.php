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

use League\Uri\Components\DataPath;
use League\Uri\Interfaces\Uri;

/**
 * Immutable Value object representing a Data Uri.
 *
 * @package    League\Uri
 * @subpackage League\Uri\Schemes
 * @author     Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @since      1.0.0
 */
class Data extends AbstractUri implements Uri
{
    /**
     * Supported schemes and corresponding default port
     *
     * @var array
     */
    protected static $supported_schemes = [
        'data' => null,
    ];

    /**
     * Tell whether the Data URI is in valid state.
     *
     * @return bool
     */
    protected function isValidUri()
    {
        $filter =  function ($value) {
            return $value !== null;
        };

        $res = array_filter([$this->authority, $this->query, $this->fragment], $filter);

        return empty($res) && $this->isValidGenericUri() && 'data' === $this->scheme;
    }

    /**
     * Filter the URI path component
     *
     * @param string|null $path the URI path component
     *
     * @return string|null
     */
    protected function filterPath($path)
    {
        return (new DataPath($path))->getContent();
    }

    /**
     * Create a new instance from a file path
     *
     * @param string $path
     *
     * @return static
     */
    public static function createFromPath($path)
    {
        return new static('data:'.DataPath::createFromPath($path));
    }
}
