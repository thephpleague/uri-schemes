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

use League\Uri\ParserException;

/**
 * Base Exception class for League Uri Schemes
 *
 * @package League\Uri
 * @author  Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @since   0.3.0
 */
class UriException extends ParserException
{
    /**
     * Returns a new Instance from an error in URI characters
     *
     * @param string $uri
     *
     * @return self
     */
    public static function createFromInvalidType($uri)
    {
        return new self(sprintf(
            'Expected data to be a string; received "%s"',
            (is_object($uri) ? get_class($uri) : gettype($uri))
        ));
    }

    /**
     * Returns a new Instance from an error in URI characters
     *
     * @param string $uri
     *
     * @return self
     */
    public static function createFromInvalidCharacters($uri)
    {
        return new self(sprintf('The submitted uri `%s` contains invalid characters', $uri));
    }

    /**
     * Returns a new Instance from an error in Uri components inter-validity
     *
     * @param string $uri
     *
     * @return self
     */
    public static function createFromInvalidState($uri)
    {
        return new self(sprintf('The submitted uri `%s` contains invalid URI parts', $uri));
    }

    /**
     * Returns a new Instance from an error in user validation
     *
     * @param string $user
     *
     * @return self
     */
    public static function createFromInvalidUser($user)
    {
        return new self(sprintf('The encoded user `%s` contains invalid characters', $user));
    }

    /**
     * Returns a new Instance from an error in password validation
     *
     * @param string $password
     *
     * @return self
     */
    public static function createFromInvalidPassword($password)
    {
        return new self(sprintf('The encoded password `%s` contains invalid characters', $password));
    }

    /**
     * Returns a new Instance from an error in port validation
     *
     * @param string $port
     *
     * @return self
     */
    public static function createFromInvalidPort($port)
    {
        return new self(sprintf('The submitted port `%s` is invalid', $port));
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
        return new self(sprintf('The encoded path `%s` contains invalid characters', $path));
    }

    /**
     * Returns a new Instance from an error in port validation
     *
     * @param string $query
     *
     * @return self
     */
    public static function createFromInvalidQuery($query)
    {
        return new self(sprintf('The submitted query `%s` is invalid', $query));
    }
}
