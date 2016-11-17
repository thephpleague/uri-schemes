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
use League\Uri\Schemes\Exceptions\DataUriException;

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
        return 'data' === $this->scheme
            && null === $this->authority
            && null === $this->query
            && null === $this->fragment;
    }

    /**
     * Filter the Path component
     *
     * @param string $path
     *
     * @see https://tools.ietf.org/html/rfc2397
     *
     * @throws DataUriException If the path is not compliant with RFC2397
     *
     * @return string
     */
    protected function filterPath($path)
    {
        if ('' == $path) {
            return 'text/plain;charset=us-ascii,';
        }

        if (!mb_detect_encoding($path, 'US-ASCII', true) || false === strpos($path, ',')) {
            throw DataUriException::createFromInvalidPath($path);
        }

        $parts = explode(',', $path, 2);
        $mediatype = explode(';', array_shift($parts), 2);
        $data = array_shift($parts);
        $mimetype = array_shift($mediatype);
        if ('' == $mimetype) {
            $mimetype = 'text/plain';
        }

        $parameters = array_shift($mediatype);
        if ('' == $parameters) {
            $parameters = 'charset=us-ascii';
        }

        $this->assertValidPath($mimetype, $parameters, $data);

        return $this->formatPath($mimetype.';'.$parameters.','.$data);
    }

    /**
     * Assert the path is a compliant with RFC2397
     *
     * @param string $mimetype   the path mediatype mimetype
     * @param string $parameters the path mediatype parameters
     * @param string $data       the path data
     *
     * @see https://tools.ietf.org/html/rfc2397
     *
     * @throws DataUriException If the mediatype or the data are not compliant
     *                          with the RFC2397
     */
    protected function assertValidPath($mimetype, $parameters, $data)
    {
        if (!preg_match(',^\w+/[-.\w]+(?:\+[-.\w]+)?$,', $mimetype)) {
            throw DataUriException::createFromInvalidMimetype($mimetype);
        }

        $is_binary = preg_match(',(;|^)base64$,', $parameters, $matches);
        if ($is_binary) {
            $parameters = mb_substr($parameters, 0, - strlen($matches[0]));
        }

        $res = array_filter(array_filter(explode(';', $parameters), [$this, 'validateParameter']));
        if (!empty($res)) {
            throw DataUriException::createFromInvalidParameters($parameters);
        }

        if (!$is_binary) {
            return;
        }

        $res = base64_decode($data, true);
        if (false === $res || $data !== base64_encode($res)) {
            throw DataUriException::createFromInvalidData($data);
        }
    }

    /**
     * Validate mediatype parameter
     *
     * @param string $parameter a mediatype parameter
     *
     * @return bool
     */
    protected function validateParameter($parameter)
    {
        $properties = explode('=', $parameter);

        return 2 != count($properties)
            || mb_strtolower($properties[0], 'UTF-8') === 'base64';
    }

    /**
     * Create a new instance from a file path
     *
     * @param string $path the file path
     *
     * @return static
     */
    public static function createFromPath($path)
    {
        if (!file_exists($path) || !is_readable($path)) {
            throw DataUriException::createFromInvalidFilePath($path);
        }

        $mimetype = str_replace(' ', '', (new \finfo(FILEINFO_MIME))->file($path));

        return new static('data:'.$mimetype.';base64,'.base64_encode(file_get_contents($path)));
    }
}
