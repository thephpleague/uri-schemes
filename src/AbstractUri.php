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
use League\Uri\Components\Traits\ImmutableComponent;
use League\Uri\Parser;

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
    use ImmutableComponent;

    /**
     * Uri Parser
     *
     * @var Parser
     */
    protected static $parser;

    /**
     * URI scheme component
     *
     * @var string|null
     */
    protected $scheme;

    /**
     * URI authority string representation
     *
     * @var string|null
     */
    protected $authority;

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
     * URI path component
     *
     * @var string
     */
    protected $path;

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
     * Tell whether the query delimiter should be preserved
     * when generating the URI string representation
     *
     * @var bool
     */
    protected $use_query_delimiter;

    /**
     * Tell whether the fragment delimiter should be preserved
     * when generating the URI string representation
     *
     * @var bool
     */
    protected $use_fragment_delimiter;

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
    public static function __set_state(array $properties)
    {
        return new static($properties['uri']);
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
        return new static($uri);
    }

    /**
     * Create a new instance
     *
     * @param string $uri
     */
    public function __construct($uri = '')
    {
        $components = $this->getParser()->__invoke(static::validateString($uri));
        $this->scheme = $this->filterScheme($components['scheme']);
        $this->user_info = $this->filterUserInfo($components['user'], $components['pass']);
        $this->host = $this->formatHost($components['host']);
        $this->port = $this->filterPort($components['port']);
        $this->authority = $this->setAuthority();
        $this->path = $this->formatPath($this->filterPath($components['path']));
        $this->query = $this->filterQuery($components['query']);
        $this->fragment = $this->filterFragment($components['fragment']);
        $this->assertValidUri();
        $this->uri = $this->getUriString();
    }

    /**
     * Return an instance of a Parser
     *
     * @return Parser
     */
    protected static function getParser()
    {
        if (null === static::$parser) {
            static::$parser = new Parser();
        }

        return static::$parser;
    }

    /**
     * Filter the URI scheme component
     *
     * @param string|null $scheme the URI scheme component
     *
     * @throws InvalidArgumentException If the scheme produces an invalid URI
     *
     * @return string|null
     */
    protected function filterScheme($scheme)
    {
        if (in_array($scheme, ['', null], true)) {
            return null;
        }

        $scheme = strtolower($this->validateString($scheme));
        if (array_key_exists($scheme, static::$supported_schemes)) {
            return $scheme;
        }

        throw new InvalidArgumentException(sprintf(
            'The submitted scheme `%s` is not supported',
            $scheme
        ));
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
        if (in_array($user, ['', null], true)) {
            return null;
        }

        $user_info = $this->validateString($user);
        if (preg_match(',[:@?#],', $user_info)) {
            throw new InvalidArgumentException(sprintf(
                'The encoded user `%s` contains invalid characters',
                $user_info
            ));
        }

        if (in_array($password, ['', null], true)) {
            return $user_info;
        }

        $password = $this->validateString($password);
        if (preg_match(',[@?#],', $password)) {
            throw new InvalidArgumentException(sprintf(
                'The encoded password `%s` contains invalid characters',
                $password
            ));
        }

        return $user_info.':'.$password;
    }

    /**
     * Filter the URI host component
     *
     * @param string|null $host the URI host component
     *
     * @return string|null
     */
    protected function filterHost($host)
    {
        if (in_array($host, ['', null], true)) {
            return $host;
        }

        $components = static::getParser()->__invoke('//u:p@'.$host.':1');

        return $this->formatHost($components['host']);
    }

    /**
     * Format the Host component
     *
     * @param string $host
     *
     * @return string
     */
    protected function formatHost($host)
    {
        if (null === $host) {
            return $host;
        }

        return strtolower($host);
    }

    /**
     * Filter the URI path component
     *
     * @param string $host the URI host component
     *
     * @return string
     */
    protected function filterPath($path)
    {
        if (!preg_match(',[?#],', $path)) {
            return $path;
        }

        throw new InvalidArgumentException(sprintf('The encoded path `%s` contains invalid characters', $path));
    }

    /**
     * Format the Path component
     *
     * @param string $path
     *
     * @return string
     */
    protected function formatPath($path)
    {
        static $regexp;
        if (null === $regexp) {
            $regexp = '/(?:[^'
            .self::$unreservedChars
            .self::$subdelimChars
            .'\:\/@]+|%(?!'
            .self::$encodedChars.'))/x';
        }

        return $this->encode($this->decodePath($path), $regexp);
    }

    /**
     * Filter the URI query component
     *
     * @param string|null $query the URI query component
     *
     * @return string|null
     */
    protected function filterQuery($query)
    {
        if (null === $query) {
            $this->use_query_delimiter = false;
            return $query;
        }

        if (!preg_match(',[#],', $query)) {
            $this->use_query_delimiter = true;
            return $this->formatQueryAndFragment($query);
        }

        throw new InvalidArgumentException(sprintf('The encoded query `%s` contains invalid characters', $query));
    }

    /**
     * Format the Query or Fragment component
     *
     * @param string $component
     *
     * @return string
     */
    protected function formatQueryAndFragment($component)
    {
        static $regexp;
        if (null === $regexp) {
            $regexp = '/(?:[^'.self::$unreservedChars.self::$subdelimChars.'\:\/@\?]+
                |%(?!'.self::$encodedChars.'))/x';
        }

        return $this->encode($this->decodeComponent($component), $regexp);
    }

    /**
     * Filter the URI fragment component
     *
     * @param string|null $fragment the URI fragment component
     *
     * @return string|null
     */
    protected function filterFragment($fragment)
    {
        if (null === $fragment) {
            $this->use_fragment_delimiter = false;
            return $fragment;
        }

        $this->use_fragment_delimiter = true;
        return $this->formatQueryAndFragment($fragment);
    }

    /**
     * Filter the URI port component
     *
     * @param int|null $port the URI port component
     *
     * @throws InvalidArgumentException If the submitted port is invalid
     *
     * @return int|null
     */
    protected function filterPort($port)
    {
        if (null === $port) {
            return $port;
        }

        $port = filter_var($port, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]);
        if (!$port) {
            throw new InvalidArgumentException('The submitted port is invalid');
        }

        if (array_key_exists($this->scheme, static::$supported_schemes)
            && static::$supported_schemes[$this->scheme] === $port
        ) {
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
        if (!in_array($this->user_info, ['', null], true)) {
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
     * generate the URI string representation
     *
     * @return string
     */
    protected function getUriString()
    {
        $scheme = $this->scheme;
        if ('' != $scheme) {
            $scheme = $scheme.':';
        }

        $authority = '';
        if (null !== $this->authority) {
            $authority = '//'.$this->authority;
        }

        $query = $this->query;
        if ($this->use_query_delimiter) {
            $query = '?'.$query;
        }

        $fragment = $this->fragment;
        if ($this->use_fragment_delimiter) {
            $fragment = '#'.$fragment;
        }

        return $scheme.$authority.$this->path.$query.$fragment;
    }

    /**
     * assert the URI internal state is valid
     *
     * @throws InvalidArgumentException if the URI is in an invalid state
     */
    protected function assertValidUri()
    {
        if (!$this->isValidUri()) {
            throw new InvalidArgumentException(sprintf(
                'The URI components will produce an `%s` object in invalid state',
                get_class($this)
            ));
        }
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
     * Tell whether the current URI is a valid generic URI
     *
     * @return bool
     */
    protected function isValidGenericUri()
    {
        if ('' !== $this->getAuthority()) {
            return '' === $this->path || strpos($this->path, '/') === 0;
        }

        if (0 === strpos($this->path, '//')) {
            return false;
        }

        if ('' !== (string) $this->scheme || false === ($pos = strpos($this->path, ':'))) {
            return true;
        }

        return false !== strpos(substr($this->path, 0, $pos), '/');
    }

    /**
     * Tell whether the current Authority is valid
     *
     * @return bool
     */
    protected function isAllowedAuthority()
    {
        $pos = 0;
        if (null === $this->host) {
            $pos = false;
        }

        if ('' != $this->scheme && 0 !== $pos) {
            return false;
        }

        return !('' === $this->host && 0 === $pos);
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
        return (string) $this->path;
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
     * @inheritdoc
     */
    public function __debugInfo()
    {
        return ['uri' => $this->uri];
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
        return $this->uri;
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
     * @throws InvalidArgumentException for transformations that would result in
     *                                  a state that cannot be represented as a
     *                                  valid URI reference.
     * @return static                   A new instance with the specified scheme.
     *
     */
    public function withScheme($scheme)
    {
        $scheme = $this->filterScheme($this->validateString($scheme));
        if ($scheme === $this->scheme) {
            return $this;
        }

        $clone = clone $this;
        $clone->scheme = $scheme;
        if (null !== $scheme && static::$supported_schemes[$scheme] === $clone->port) {
            $clone->port = null;
            $clone->authority = $clone->setAuthority();
        }
        $clone->assertValidUri();
        $clone->uri = $clone->getUriString();

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
     * @throws InvalidArgumentException for transformations that would result in
     *                                  a state that cannot be represented as a
     *                                  valid URI reference.
     * @return static                   A new instance with the specified user information.
     *
     */
    public function withUserInfo($user, $password = null)
    {
        $user_info = $this->filterUserInfo($user, $password);
        if ($user_info === $this->user_info) {
            return $this;
        }

        $clone = clone $this;
        $clone->user_info = $user_info;
        $clone->authority = $clone->setAuthority();
        $clone->assertValidUri();
        $clone->uri = $clone->getUriString();

        return $clone;
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
     * @throws InvalidArgumentException for transformations that would result in
     *                                  a state that cannot be represented as a
     *                                  valid URI reference.
     * @return static                   A new instance with the specified host.
     *
     */
    public function withHost($host)
    {
        $host = $this->filterHost($this->validateString($host));
        if ('' === $host) {
            $host = null;
        }

        if ($host === $this->host) {
            return $this;
        }

        $clone = clone $this;
        $clone->host = $host;
        $clone->authority = $clone->setAuthority();
        $clone->assertValidUri();
        $clone->uri = $clone->getUriString();

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
     * @throws InvalidArgumentException for transformations that would result in
     *                                  a state that cannot be represented as a
     *                                  valid URI reference.
     * @return static                   A new instance with the specified port.
     *
     */
    public function withPort($port)
    {
        $port = $this->filterPort($port);
        if ($port === $this->port) {
            return $this;
        }

        $clone = clone $this;
        $clone->port = $port;
        $clone->authority = $clone->setAuthority();
        $clone->assertValidUri();
        $clone->uri = $clone->getUriString();

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
     * @throws InvalidArgumentException for transformations that would result in
     *                                  a state that cannot be represented as a
     *                                  valid URI reference.
     * @return static                   A new instance with the specified path.
     *
     */
    public function withPath($path)
    {
        $path = $this->filterPath($this->validateString($path));
        $path = $this->formatPath($path);
        if ($path === $this->path) {
            return $this;
        }

        $clone = clone $this;
        $clone->path = $path;
        $clone->assertValidUri();
        $clone->uri = $clone->getUriString();

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
     * @throws InvalidArgumentException for transformations that would result in
     *                                  a state that cannot be represented as a
     *                                  valid URI reference.
     * @return static                   A new instance with the specified query string.
     *
     */
    public function withQuery($query)
    {
        $query = $this->validateString($query);
        if ('' === $query) {
            $query = null;
        }

        if ($query === $this->query) {
            return $this;
        }

        $clone = clone $this;
        $clone->query = $clone->filterQuery($query);
        $clone->assertValidUri();
        $clone->uri = $clone->getUriString();

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
     * @throws InvalidArgumentException for transformations that would result in
     *                                  a state that cannot be represented as a
     *                                  valid URI reference.
     * @return static                   A new instance with the specified fragment.
     *
     */
    public function withFragment($fragment)
    {
        $fragment = $this->validateString($fragment);
        if ('' === $fragment) {
            $fragment = null;
        }

        if ($fragment === $this->fragment) {
            return $this;
        }

        $clone = clone $this;
        $clone->fragment = $clone->filterFragment($fragment);
        $clone->assertValidUri();
        $clone->uri = $clone->getUriString();

        return $clone;
    }
}
