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

use InvalidArgumentException;
use Psr\Http\Message\UriInterface;

/**
 * Immutable Value object representing a HTTP(s) Uri.
 *
 * @package    League\Uri
 * @subpackage League\Uri\Schemes
 * @author     Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @since      1.0.0
 */
class Http extends AbstractUri implements UriInterface
{
    /**
     * Supported schemes and corresponding default port
     *
     * @var array
     */
    protected static $supported_schemes = [
        'http' => 80,
        'https' => 443,
    ];

    /**
     * Tell whether the Http(s) URI is in valid state.
     *
     * @return bool
     */
    protected function isValidUri()
    {
        return $this->isValidGenericUri() && $this->isAllowedAuthority();
    }

    /**
     * Create a new instance from the environment
     *
     * @param array $server the server and execution environment information array typically ($_SERVER)
     *
     * @return static
     */
    public static function createFromServer(array $server)
    {
        return new static(
            static::fetchServerScheme($server).'://'
            .static::fetchServerUserInfo($server)
            .static::fetchServerHost($server)
            .static::fetchServerPort($server)
            .static::fetchServerRequestUri($server)
        );
    }

    /**
     * Returns the environment scheme
     *
     * @param array $server the environment server typically $_SERVER
     *
     * @return string
     */
    protected static function fetchServerScheme(array $server)
    {
        $server += ['HTTPS' => ''];
        $res = filter_var($server['HTTPS'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return ($res !== false) ? 'https' : 'http';
    }

    /**
     * Returns the environment user info
     *
     * @param array $server the environment server typically $_SERVER
     *
     * @return string
     */
    protected static function fetchServerUserInfo(array $server)
    {
        $server += ['PHP_AUTH_USER' => null, 'PHP_AUTH_PW' => null, 'HTTP_AUTHORIZATION' => null];
        $login = $server['PHP_AUTH_USER'];
        $pass = $server['PHP_AUTH_PW'];
        if ('' !== $server['HTTP_AUTHORIZATION']
            && 0 === strpos(strtolower($server['HTTP_AUTHORIZATION']), 'basic')
        ) {
            $res = explode(':', base64_decode(substr($server['HTTP_AUTHORIZATION'], 6)), 2);
            $login = array_shift($res);
            $pass = array_shift($res);
        }

        if (null === $login) {
            return null;
        }

        $userInfo = $login;
        if (null !== $pass) {
            $userInfo .= ':'.$pass;
        }

        return $userInfo.'@';
    }

    /**
     * Returns the environment host
     *
     * @param array $server the environment server typically $_SERVER
     *
     * @throws InvalidArgumentException If the host can not be detected
     *
     * @return string
     */
    protected static function fetchServerHost(array $server)
    {
        if (isset($server['HTTP_HOST'])) {
            return static::fetchServerHostname($server['HTTP_HOST']);
        }

        if (isset($server['SERVER_ADDR'])) {
            if (!filter_var($server['SERVER_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $server['SERVER_ADDR'] = '['.$server['SERVER_ADDR'].']';
            }

            return $server['SERVER_ADDR'];
        }

        throw new InvalidArgumentException('Host could not be detected');
    }

    /**
     * Returns the environment hostname
     *
     * @param string $host the environment server hostname
     *                     the port info can sometimes be
     *                     associated with the hostname
     *
     * @return string
     */
    protected static function fetchServerHostname($host)
    {
        preg_match(",^(([^(\[\])]*):)?(?<host>.*)?$,", strrev($host), $matches);

        return strrev($matches['host']);
    }

    /**
     * Returns the environment port
     *
     * @param array $server the environment server typically $_SERVER
     *
     * @return string
     */
    protected static function fetchServerPort(array $server)
    {
        $server += ['HTTP_HOST' => '', 'SERVER_PORT' => ''];
        if (preg_match(',^(?<port>([^(\[\])]*):),', strrev($server['HTTP_HOST']), $matches)) {
            return strrev($matches['port']);
        }

        return ':'.$server['SERVER_PORT'];
    }

    /**
     * Returns the environment path
     *
     * @param array $server the environment server typically $_SERVER
     *
     * @return string
     */
    protected static function fetchServerRequestUri(array $server)
    {
        if (isset($server['REQUEST_URI'])) {
            return $server['REQUEST_URI'];
        }

        $server += ['PHP_SELF' => '', 'QUERY_STRING' => ''];
        if ('' !== $server['QUERY_STRING']) {
            $server['QUERY_STRING'] = '?'.$server['QUERY_STRING'];
        }

        return $server['PHP_SELF'].$server['QUERY_STRING'];
    }
}
