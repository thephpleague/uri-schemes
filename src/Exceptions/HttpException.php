<?php
/**
 * League.Uri (http://uri.thephpleague.com)
 *
 * @package    League\Uri
 * @subpackage League\Uri\Schemes
 * @author     Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @copyright  2016 Ignace Nyamagana Butera
 * @license    https://github.com/thephpleague/uri-parser/blob/master/LICENSE (MIT License)
 * @version    0.3.0
 * @link       https://github.com/thephpleague/uri-parser/
 */
namespace League\Uri\Schemes\Exceptions;

/**
 * Exception class for Http(s) Uri
 *
 * @package League\Uri
 * @author  Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @since   0.3.0
 */
class HttpException extends UriException
{
    /**
     * Returns a new Instance from an error in server configuration
     *
     * @return self
     */
    public static function createFromInvalidServer()
    {
        return new self('Host could not be detected');
    }
}
