<?php
/**
 * League.Uri (http://uri.thephpleague.com)
 *
 * @package    League\Uri
 * @subpackage League\Uri\Schemes
 * @author     Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @copyright  2016 Ignace Nyamagana Butera
 * @license    https://github.com/thephpleague/uri-parser/blob/master/LICENSE (MIT License)
 * @version    0.2.0
 * @link       https://github.com/thephpleague/uri-parser/
 */
namespace League\Uri\Schemes\Exceptions;

/**
 * a Trait to validate a Hostname
 *
 * @see     https://tools.ietf.org/html/rfc3986
 * @package League\Uri
 * @author  Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @since   0.2.0
 */
class DataUriException extends UriException
{
    /**
     * Returns a new Instance from an error in file path
     *
     * @return self
     */
    public static function createFromInvalidFilePath($path)
    {
        return new self(sprintf('The specified file `%s` does not exist or is not readable', $path));
    }

    /**
     * Returns a new Instance from an error in path validation
     *
     * @param string $path
     *
     * @return self
     */
    public static function createFromInvalidPath($path)
    {
        return new self(sprintf('The submitted path `%s` is invalid according to RFC2937', $path));
    }

    /**
     * Returns a new Instance from an error in path validation
     *
     * @param string $parameters
     *
     * @return self
     */
    public static function createFromInvalidParameters($parameters)
    {
        return new self(sprintf('The path paremeters `%s` contains is invalid', $parameters));
    }

    /**
     * Returns a new Instance from an error in path validation
     *
     * @param string $path
     *
     * @return self
     */
    public static function createFromInvalidMimetype($mimetype)
    {
        return new self(sprintf('The path mimetype `%s` is invalid', $mimetype));
    }

    /**
     * Returns a new Instance from an error in path validation
     *
     * @param string $path
     *
     * @return self
     */
    public static function createFromInvalidData($data)
    {
        return new self(sprintf('The submitted path data `%s` is invalid', $data));
    }
}
