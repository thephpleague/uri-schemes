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

use League\Uri\HostValidation;
use League\Uri\Parser;
use League\Uri\Schemes\Exceptions\Exception;

/**
 * common URI Object properties and methods
 *
 * @package    League\Uri
 * @subpackage League\Uri\Schemes
 * @author     Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @since      1.0.0
 */
abstract class AbstractUri
{
    use HostValidation;

    /**
     * Invalid Characters
     *
     * @see http://tools.ietf.org/html/rfc3986#section-2
     *
     * @var string
     */
    const INVALID_CHARS = "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0A\x0B\x0C\x0D\x0E\x0F\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F\x7F";

    /**
     * RFC3986 Sub delimiter characters regular expression pattern
     *
     * @see http://tools.ietf.org/html/rfc3986#section-2.2
     *
     * @var string
     */
    const REGEXP_CHARS_SUBDELIM = "\!\$&'\(\)\*\+,;\=%";

    /**
     * RFC3986 unreserved characters regular expression pattern
     *
     * @see http://tools.ietf.org/html/rfc3986#section-2.3
     *
     * @var string
     */
    const REGEXP_CHARS_UNRESERVED = 'A-Za-z0-9_\-\.~';

    /**
     * URI scheme component
     *
     * @var string|null
     */
    protected $scheme;

    /**
     * URI user info part
     *
     * @var string|null
     */
    protected $user_info;

    /**
     * URI host component
     *
     * @var string|null
     */
    protected $host;

    /**
     * URI port component
     *
     * @var int|null
     */
    protected $port;

    /**
     * URI authority string representation
     *
     * @var string|null
     */
    protected $authority;

    /**
     * URI path component
     *
     * @var string
     */
    protected $path = '';

    /**
     * URI query component
     *
     * @var string|null
     */
    protected $query;

    /**
     * URI fragment component
     *
     * @var string|null
     */
    protected $fragment;

    /**
     * URI string representation
     *
     * @var string
     */
    protected $uri;

    /**
     * Supported schemes and corresponding default port
     *
     * @var array
     */
    protected static $supported_schemes;

    /**
     * Static method called by PHP's var export
     *
     * @return static
     */
    public static function __set_state(array $components)
    {
        $user_info = explode(':', $components['user_info'], 2);
        $components['user'] = array_shift($user_info);
        $components['pass'] = array_shift($user_info);

        return new static(
            $components['scheme'],
            $components['user'],
            $components['pass'],
            $components['host'],
            $components['port'],
            $components['path'],
            $components['query'],
            $components['fragment']
        );
    }

    /**
     * Create a new instance from a string
     *
     * @param string $uri
     *
     * @return static
     */
    public static function createFromString($uri = '')
    {
        static $parser;
        if (!$parser instanceof Parser) {
            $parser = new Parser();
        }

        $components = $parser(self::filterString($uri));

        return new static(
            $components['scheme'],
            $components['user'],
            $components['pass'],
            $components['host'],
            $components['port'],
            $components['path'],
            $components['query'],
            $components['fragment']
        );
    }

    /**
     * Filter a string.
     *
     * @param string $str the value to evaluate as a string
     *
     * @throws Exception if the submitted data can not be converted to string
     *
     * @return string
     */
    protected static function filterString($str)
    {
        if (!is_string($str)) {
            throw new Exception(sprintf(
                'Expected data to be a string; received "%s"',
                (is_object($str) ? get_class($str) : gettype($str))
            ));
        }

        if (strlen($str) !== strcspn($str, self::INVALID_CHARS)) {
            throw Exception::createFromInvalidCharacters($str);
        }

        return $str;
    }

    /**
     * Create a new instance from a hash of parse_url parts
     *
     * @param array $components a hash representation of the URI similar
     *                          to PHP parse_url function result
     *
     * @return static
     */
    public static function createFromComponents(array $components)
    {
        $components = $components + [
            'scheme' => null, 'user' => null, 'pass' => null, 'host' => null,
            'port' => null, 'path' => '', 'query' => null, 'fragment' => null,
        ];

        return new static(
            $components['scheme'],
            $components['user'],
            $components['pass'],
            $components['host'],
            $components['port'],
            $components['path'],
            $components['query'],
            $components['fragment']
        );
    }

    /**
     * Create a new instance
     *
     * @param string|null $scheme   scheme component
     * @param string|null $user     user component
     * @param string|null $pass     pass component
     * @param string|null $host     host component
     * @param int|null    $port     port component
     * @param string      $path     path component
     * @param string|null $query    query component
     * @param string|null $fragment fragment component
     */
    protected function __construct(
        $scheme = null,
        $user = null,
        $pass = null,
        $host = null,
        $port = null,
        $path = '',
        $query = null,
        $fragment = null
    ) {
        $this->scheme = $this->formatScheme($scheme);
        $this->user_info = $this->formatUserInfo($user, $pass);
        $this->host = $this->formatHost($host);
        $this->port = $this->formatPort($port);
        $this->authority = $this->setAuthority();
        $this->path = $this->filterPath($path);
        $this->query = $this->formatQueryAndFragment($query);
        $this->fragment = $this->formatQueryAndFragment($fragment);
        $this->assertValidUri();
    }

    /**
     * Format the Scheme and Host component
     *
     * @param string|null $component
     *
     * @return string|null
     */
    protected function formatScheme($component)
    {
        if ('' == $component) {
            return $component;
        }

        return strtolower($component);
    }

    /**
     * Format the Scheme and Host component
     *
     * @param string|null $component
     *
     * @return string|null
     */
    protected function formatHost($component)
    {
        if ('' == $component) {
            return $component;
        }

        $component = strtolower($component);
        if (false !== strpos($component, ']')) {
            return $component;
        }

        return implode('.', array_map('idn_to_ascii', explode('.', $component)));
    }

    /**
     * Set the UserInfo component
     *
     * @param string|null $user     the URI scheme component
     * @param string|null $password the URI scheme component
     *
     * @return string|null
     */
    protected static function formatUserInfo($user, $password)
    {
        if (null === $user) {
            return $user;
        }

        $user = preg_replace_callback(
            '/(?:[^'.self::REGEXP_CHARS_UNRESERVED.self::REGEXP_CHARS_SUBDELIM.'%]++|%(?![A-Fa-f0-9]{2}))/',
            [AbstractUri::class, 'urlEncodeMatch'],
            $user
        );

        if (null === $password) {
            return $user;
        }

        return $user.':'.preg_replace_callback(
            '/(?:[^'.self::REGEXP_CHARS_UNRESERVED.self::REGEXP_CHARS_SUBDELIM.'%:]++|%(?![A-Fa-f0-9]{2}))/',
            [AbstractUri::class, 'urlEncodeMatch'],
            $password
        );
    }

    /**
     * Returns the RFC3986 encoded string matched
     *
     * @param array $matches
     *
     * @return string
     */
    protected static function urlEncodeMatch(array $matches)
    {
        return rawurlencode($matches[0]);
    }

    /**
     * Format the Port component
     *
     * @param int|null $port
     *
     * @return int|null
     */
    protected function formatPort($port)
    {
        if (isset(static::$supported_schemes[$this->scheme])
            && static::$supported_schemes[$this->scheme] === $port) {
            return null;
        }

        return $port;
    }

    /**
     * Generate the URI authority part
     */
    protected function setAuthority()
    {
        $authority = null;
        if (null !== $this->user_info) {
            $authority = $this->user_info.'@';
        }

        if (null !== $this->host) {
            $authority .= $this->host;
        }

        if (null !== $this->port) {
            $authority .= ':'.$this->port;
        }

        return $authority;
    }

    /**
     * Format the Path component
     *
     * @param string $path
     *
     * @return string
     */
    protected static function formatPath($path)
    {
        return preg_replace_callback(
            '/(?:[^'.self::REGEXP_CHARS_UNRESERVED.self::REGEXP_CHARS_SUBDELIM.'%:@\/]++|%(?![A-Fa-f0-9]{2}))/',
            [AbstractUri::class, 'urlEncodeMatch'],
            $path
        );
    }

    /**
     * Filter the Path component
     *
     * @param string $path
     *
     * @return string
     */
    protected function filterPath($path)
    {
        return $this->formatPath($path);
    }

    /**
     * Format the Query or the Fragment component
     *
     * Returns a array containing:
     * <ul>
     * <li> the formatted component (a string or null)</li>
     * <li> a boolean flag telling wether the delimiter is to be added to the component
     * when building the URI string representation</li>
     * </ul>
     *
     * @param string|null $component
     *
     * @return array
     */
    protected function formatQueryAndFragment($component)
    {
        if ('' == $component) {
            return $component;
        }

        return preg_replace_callback(
            '/(?:[^'.self::REGEXP_CHARS_UNRESERVED.self::REGEXP_CHARS_SUBDELIM.'%:@\/\?]++|%(?![A-Fa-f0-9]{2}))/',
            [AbstractUri::class, 'urlEncodeMatch'],
            $component
        );
    }

    /**
     * assert the URI internal state is valid
     *
     * @throws Exception if the URI is in an invalid state
     */
    protected function assertValidUri()
    {
        $this->uri = null;
        if (!$this->isValidGenericUri() || !$this->isValidUri()) {
            throw new Exception(sprintf(
                'The submitted uri `%s` is in invalid state',
                $this->getUriString(
                    $this->scheme,
                    $this->authority,
                    $this->path,
                    $this->query,
                    $this->fragment
            )));
        }
    }

    /**
     * Generate the URI string representation from its components
     *
     * @see https://tools.ietf.org/html/rfc3986#section-5.3
     *
     * @param string|null $scheme
     * @param string|null $authority
     * @param string      $path
     * @param string|null $query
     * @param string|null $fragment
     *
     * @return string
     */
    protected static function getUriString($scheme, $authority, $path, $query, $fragment)
    {
        if ('' != $scheme) {
            $scheme = $scheme.':';
        }

        if (null !== $authority) {
            $authority = '//'.$authority;
        }

        if (null !== $query) {
            $query = '?'.$query;
        }

        if (null !== $fragment) {
            $fragment = '#'.$fragment;
        }

        return $scheme.$authority.$path.$query.$fragment;
    }

    /**
     * Tell whether the current URI is a valid generic URI
     *
     * @return bool
     */
    protected function isValidGenericUri()
    {
        //if an authority is present the path must be empty or start with a '/'
        if ('' != $this->authority) {
            return '' === $this->path || strpos($this->path, '/') === 0;
        }

        //if there's no authority the path can not start with a '//'
        if (0 === strpos($this->path, '//')) {
            return false;
        }

        if (null !== $this->scheme || false === ($pos = strpos($this->path, ':'))) {
            return true;
        }

        //if there's no authority and no scheme
        //the first '/' must occur before the first ':' in the path component
        return false !== strpos(substr($this->path, 0, $pos), '/');
    }

    /**
     * Tell whether the current URI is in valid state.
     *
     * The URI object validity depends on the scheme. This method
     * MUST be implemented on every URI object
     *
     * @return bool
     */
    abstract protected function isValidUri();

    /**
     * @inheritdoc
     */
    public function __debugInfo()
    {
        return ['uri' => $this->__toString()];
    }

    /**
     * Return the string representation as a URI reference.
     *
     * Depending on which components of the URI are present, the resulting
     * string is either a full URI or relative reference according to RFC 3986,
     * Section 4.1. The method concatenates the various components of the URI,
     * using the appropriate delimiters:
     *
     * - If a scheme is present, it MUST be suffixed by ":".
     * - If an authority is present, it MUST be prefixed by "//".
     * - The path can be concatenated without delimiters.
     * - If a query is present, it MUST be prefixed by "?".
     * - If a fragment is present, it MUST be prefixed by "#".
     *
     * @see http://tools.ietf.org/html/rfc3986#section-4.1
     *
     * @return string
     */
    public function __toString()
    {
        if (null === $this->uri) {
            $this->uri = $this->getUriString(
                $this->scheme,
                $this->authority,
                $this->path,
                $this->query,
                $this->fragment
            );
        }

        return $this->uri;
    }

    /**
     * Retrieve the scheme component of the URI.
     *
     * If no scheme is present, this method MUST return an empty string.
     *
     * The value returned MUST be normalized to lowercase, per RFC 3986
     * Section 3.1.
     *
     * The trailing ":" character is not part of the scheme and MUST NOT be
     * added.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.1
     *
     * @return string The URI scheme.
     */
    public function getScheme()
    {
        return (string) $this->scheme;
    }

    /**
     * Retrieve the authority component of the URI.
     *
     * If no authority information is present, this method MUST return an empty
     * string.
     *
     * The authority syntax of the URI is:
     *
     * <pre>
     * [user-info@]host[:port]
     * </pre>
     *
     * If the port component is not set or is the standard port for the current
     * scheme, it SHOULD NOT be included.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.2
     *
     * @return string The URI authority, in "[user-info@]host[:port]" format.
     */
    public function getAuthority()
    {
        return (string) $this->authority;
    }

    /**
     * Retrieve the user information component of the URI.
     *
     * If no user information is present, this method MUST return an empty
     * string.
     *
     * If a user is present in the URI, this will return that value;
     * additionally, if the password is also present, it will be appended to the
     * user value, with a colon (":") separating the values.
     *
     * The trailing "@" character is not part of the user information and MUST
     * NOT be added.
     *
     * @return string The URI user information, in "username[:password]" format.
     */
    public function getUserInfo()
    {
        return (string) $this->user_info;
    }

    /**
     * Retrieve the host component of the URI.
     *
     * If no host is present, this method MUST return an empty string.
     *
     * The value returned MUST be normalized to lowercase, per RFC 3986
     * Section 3.2.2.
     *
     * @see http://tools.ietf.org/html/rfc3986#section-3.2.2
     *
     * @return string The URI host.
     */
    public function getHost()
    {
        return (string) $this->host;
    }

    /**
     * Retrieve the port component of the URI.
     *
     * If a port is present, and it is non-standard for the current scheme,
     * this method MUST return it as an integer. If the port is the standard port
     * used with the current scheme, this method SHOULD return null.
     *
     * If no port is present, and no scheme is present, this method MUST return
     * a null value.
     *
     * If no port is present, but a scheme is present, this method MAY return
     * the standard port for that scheme, but SHOULD return null.
     *
     * @return null|int The URI port.
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Retrieve the path component of the URI.
     *
     * The path can either be empty or absolute (starting with a slash) or
     * rootless (not starting with a slash). Implementations MUST support all
     * three syntaxes.
     *
     * Normally, the empty path "" and absolute path "/" are considered equal as
     * defined in RFC 7230 Section 2.7.3. But this method MUST NOT automatically
     * do this normalization because in contexts with a trimmed base path, e.g.
     * the front controller, this difference becomes significant. It's the task
     * of the user to handle both "" and "/".
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.3.
     *
     * As an example, if the value should include a slash ("/") not intended as
     * delimiter between path segments, that value MUST be passed in encoded
     * form (e.g., "%2F") to the instance.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.3
     *
     * @return string The URI path.
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Retrieve the query string of the URI.
     *
     * If no query string is present, this method MUST return an empty string.
     *
     * The leading "?" character is not part of the query and MUST NOT be
     * added.
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.4.
     *
     * As an example, if a value in a key/value pair of the query string should
     * include an ampersand ("&") not intended as a delimiter between values,
     * that value MUST be passed in encoded form (e.g., "%26") to the instance.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.4
     *
     * @return string The URI query string.
     */
    public function getQuery()
    {
        return (string) $this->query;
    }

    /**
     * Retrieve the fragment component of the URI.
     *
     * If no fragment is present, this method MUST return an empty string.
     *
     * The leading "#" character is not part of the fragment and MUST NOT be
     * added.
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.5.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.5
     *
     * @return string The URI fragment.
     */
    public function getFragment()
    {
        return (string) $this->fragment;
    }

    /**
     * Return an instance with the specified scheme.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified scheme.
     *
     * An empty scheme is equivalent to removing the scheme.
     *
     * @param string $scheme The scheme to use with the new instance.
     *
     * @throws Exception for transformations that would result in
     *                   a state that cannot be represented as a
     *                   valid URI reference.
     * @return static    A new instance with the specified scheme.
     *
     */
    public function withScheme($scheme)
    {
        $scheme = $this->filterString($scheme);
        $scheme = $this->formatScheme($scheme);
        if ('' == $scheme) {
            $scheme = null;
        }

        if ($scheme === $this->scheme) {
            return $this;
        }

        $clone = clone $this;
        $clone->scheme = $scheme;
        $clone->port = $clone->formatPort($clone->port);
        $clone->authority = $clone->setAuthority();
        $clone->assertValidUri();

        return $clone;
    }

    /**
     * Return an instance with the specified user information.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified user information.
     *
     * Password is optional, but the user information MUST include the
     * user; an empty string for the user is equivalent to removing user
     * information.
     *
     * @param string      $user     The user name to use for authority.
     * @param null|string $password The password associated with $user.
     *
     * @throws Exception for transformations that would result in
     *                   a state that cannot be represented as a
     *                   valid URI reference.
     * @return static    A new instance with the specified user information.
     *
     */
    public function withUserInfo($user, $password = null)
    {
        $user_info = null;
        if ('' != $user) {
            list($user, $password) = $this->filterUserInfo($user, $password);
            $user_info = $this->formatUserInfo($user, $password);
        }

        if ($user_info === $this->user_info) {
            return $this;
        }

        $clone = clone $this;
        $clone->user_info = $user_info;
        $clone->authority = $clone->setAuthority();
        $clone->assertValidUri();

        return $clone;
    }

     /**
     * Filter the URI user info component
     *
     * @param string|null $user     the URI user component
     * @param string|null $password the URI password component
     *
     * @return string|null
     */
    protected function filterUserInfo($user, $password)
    {
        $user = $this->filterString($user);
        if (strlen($user) !== strcspn($user, ':@/?#')) {
            throw new Exception(sprintf('The encoded user `%s` contains invalid characters', $user));
        }

        if ('' == $password) {
            return [$user, null];
        }

        $password = $this->filterString($password);
        if (strlen($password) !== strcspn($password, '@/?#')) {
            throw new Exception(sprintf('The encoded password `%s` contains invalid characters', $password));
        }

        return [$user, $password];
    }

   /**
     * Return an instance with the specified host.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified host.
     *
     * An empty host value is equivalent to removing the host.
     *
     * @param string $host The hostname to use with the new instance.
     *
     * @throws Exception for transformations that would result in
     *                   a state that cannot be represented as a
     *                   valid URI reference.
     * @return static    A new instance with the specified host.
     *
     */
    public function withHost($host)
    {
        $host = $this->filterString($host);
        $host = '' !== $host ? $this->filterHost($host) : null;
        $host = $this->formatHost($host);
        if ($host === $this->host) {
            return $this;
        }

        $clone = clone $this;
        $clone->host = $host;
        $clone->authority = $clone->setAuthority();
        $clone->assertValidUri();

        return $clone;
    }

    /**
     * Return an instance with the specified port.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified port.
     *
     * Implementations MUST raise an exception for ports outside the
     * established TCP and UDP port ranges.
     *
     * A null value provided for the port is equivalent to removing the port
     * information.
     *
     * @param null|int $port The port to use with the new instance; a null value
     *                       removes the port information.
     *
     * @throws Exception for transformations that would result in
     *                   a state that cannot be represented as a
     *                   valid URI reference.
     * @return static    A new instance with the specified port.
     *
     */
    public function withPort($port)
    {
        if (null !== $port && (!is_int($port) || $port < 1 || $port > 65535)) {
            throw Exception::createFromInvalidPort($port);
        }

        $port = $this->formatPort($port);
        if ($port === $this->port) {
            return $this;
        }

        $clone = clone $this;
        $clone->port = $port;
        $clone->authority = $clone->setAuthority();
        $clone->assertValidUri();

        return $clone;
    }

    /**
     * Return an instance with the specified path.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified path.
     *
     * The path can either be empty or absolute (starting with a slash) or
     * rootless (not starting with a slash). Implementations MUST support all
     * three syntaxes.
     *
     * If the path is intended to be domain-relative rather than path relative then
     * it must begin with a slash ("/"). Paths not starting with a slash ("/")
     * are assumed to be relative to some base path known to the application or
     * consumer.
     *
     * Users can provide both encoded and decoded path characters.
     * Implementations ensure the correct encoding as outlined in getPath().
     *
     * @param string $path The path to use with the new instance.
     *
     * @throws Exception for transformations that would result in
     *                   a state that cannot be represented as a
     *                   valid URI reference.
     * @return static    A new instance with the specified path.
     *
     */
    public function withPath($path)
    {
        $path = $this->filterString($path);
        if (strlen($path) != strcspn($path, '?#')) {
            throw new Exception(sprintf('The encoded path `%s` contains invalid characters', $path));
        }

        $path = $this->filterPath($path);
        if ($path === $this->path) {
            return $this;
        }

        $clone = clone $this;
        $clone->path = $path;
        $clone->assertValidUri();

        return $clone;
    }

    /**
     * Return an instance with the specified query string.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified query string.
     *
     * Users can provide both encoded and decoded query characters.
     * Implementations ensure the correct encoding as outlined in getQuery().
     *
     * An empty query string value is equivalent to removing the query string.
     *
     * @param string $query The query string to use with the new instance.
     *
     * @throws Exception for transformations that would result in
     *                   a state that cannot be represented as a
     *                   valid URI reference.
     * @return static    A new instance with the specified query string.
     *
     */
    public function withQuery($query)
    {
        $query = $this->filterString($query);
        if (strlen($query) !== strcspn($query, '#')) {
            throw new Exception(sprintf('The submitted query `%s` contains invalid characters', $query));
        }

        $query = '' == $query ? null : $this->formatQueryAndFragment($query);
        if ($query === $this->query) {
            return $this;
        }

        $clone = clone $this;
        $clone->query = $query;
        $clone->assertValidUri();

        return $clone;
    }

    /**
     * Return an instance with the specified URI fragment.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified URI fragment.
     *
     * Users can provide both encoded and decoded fragment characters.
     * Implementations ensure the correct encoding as outlined in getFragment().
     *
     * An empty fragment value is equivalent to removing the fragment.
     *
     * @param string $fragment The fragment to use with the new instance.
     *
     * @throws Exception for transformations that would result in
     *                   a state that cannot be represented as a
     *                   valid URI reference.
     * @return static    A new instance with the specified fragment.
     *
     */
    public function withFragment($fragment)
    {
        $fragment = $this->filterString($fragment);
        $fragment = '' == $fragment ? null : $this->formatQueryAndFragment($fragment);
        if ($fragment === $this->fragment) {
            return $this;
        }

        $clone = clone $this;
        $clone->fragment = $fragment;
        $clone->assertValidUri();

        return $clone;
    }
}
