<?php

/**
 * League.Uri (http://uri.thephpleague.com)
 *
 * @package    League\Uri
 * @subpackage League\Uri\Schemes
 * @author     Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @license    https://github.com/thephpleague/uri-components/blob/master/LICENSE (MIT License)
 * @version    2.0.0
 * @link       https://github.com/thephpleague/uri-schemes
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace League\Uri;

use JsonSerializable;
use League\Uri\Exception\InvalidUri;
use League\Uri\Exception\MissingIdnSupport;
use TypeError;

class Uri implements UriInterface, JsonSerializable
{
    /**
     * @internal RFC3986 Sub delimiter characters regular expression pattern
     *
     * @see http://tools.ietf.org/html/rfc3986#section-2.2
     *
     * @var string
     */
    const REGEXP_CHARS_SUBDELIM = "\!\$&'\(\)\*\+,;\=%";

    /**
     * @internal RFC3986 unreserved characters regular expression pattern
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
     * @var string|null
     */
    protected $uri;

    /**
     * Supported schemes and corresponding default port
     *
     * @var array
     */
    protected static $supported_schemes = [];

    /**
     * Static method called by PHP's var export
     *
     * @param array $components
     *
     * @return static
     */
    public static function __set_state(array $components)
    {
        list($components['user'], $components['pass']) = explode(':', $components['user_info'], 2) + [1 => null];

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
     * @param mixed $uri
     *
     * @return static
     */
    public static function createFromString($uri = '')
    {
        $components = parse($uri);

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
     * Create a new instance from a hash of parse_url parts
     *
     * @param array $components a hash representation of the URI similar
     *                          to PHP parse_url function result
     *
     * @return static
     */
    public static function createFromComponents(array $components = [])
    {
        $components += [
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
        string $scheme = null,
        string $user = null,
        string $pass = null,
        string $host = null,
        int $port = null,
        string $path,
        string $query = null,
        string $fragment = null
    ) {
        $this->scheme = $this->formatScheme($scheme);
        $this->user_info = $this->formatUserInfo($user, $pass);
        $this->host = $this->formatHost($host);
        $this->port = $this->formatPort($port);
        $this->authority = $this->setAuthority();
        $this->path = $this->formatPath($path);
        $this->query = $this->formatQueryAndFragment($query);
        $this->fragment = $this->formatQueryAndFragment($fragment);
        $this->assertValidState();
    }

    /**
     * Format the Scheme and Host component
     *
     * @param string|null $scheme
     *
     * @return string|null
     */
    protected function formatScheme(string $scheme = null)
    {
        if ('' === $scheme || null === $scheme) {
            return $scheme;
        }

        $formatted_scheme = strtolower($scheme);
        static $pattern = '/^[a-z][a-z\+\.\-]*$/';
        if (preg_match($pattern, $formatted_scheme)) {
            return $formatted_scheme;
        }

        throw new InvalidUri(sprintf('The scheme `%s` is invalid', $scheme));
    }

    /**
     * Set the UserInfo component
     *
     * @param string|null $user     the URI scheme component
     * @param string|null $password the URI scheme component
     *
     * @return string|null
     */
    protected function formatUserInfo(string $user = null, string $password = null)
    {
        if (null === $user) {
            return $user;
        }

        static $user_pattern = '/(?:[^%'.self::REGEXP_CHARS_UNRESERVED.self::REGEXP_CHARS_SUBDELIM.']++|%(?![A-Fa-f0-9]{2}))/';
        $user = preg_replace_callback($user_pattern, [Uri::class, 'urlEncodeMatch'], $user);
        if (null === $password) {
            return $user;
        }

        static $password_pattern = '/(?:[^%:'.self::REGEXP_CHARS_UNRESERVED.self::REGEXP_CHARS_SUBDELIM.']++|%(?![A-Fa-f0-9]{2}))/';

        return $user.':'.preg_replace_callback($password_pattern, [Uri::class, 'urlEncodeMatch'], $password);
    }

    /**
     * Returns the RFC3986 encoded string matched
     *
     * @param array $matches
     *
     * @return string
     */
    private static function urlEncodeMatch(array $matches): string
    {
        return rawurlencode($matches[0]);
    }

    /**
     * Validate and Format the Host component
     *
     * @param string|null $host
     *
     * @return string|null
     */
    protected function formatHost(string $host = null)
    {
        if (null === $host || '' === $host) {
            return $host;
        }

        if ('[' !== $host[0]) {
            return $this->formatRegisteredName($host);
        }

        return $this->formatIp($host);
    }

    /**
     * Validate and format a registered name.
     *
     * The host is converted to its ascii representation if needed
     *
     * @param string $host
     *
     * @throws InvalidUri if the submitted host is not a valid registered name
     *
     * @return string
     */
    private function formatRegisteredName(string $host): string
    {
        $formatted_host = rawurldecode(strtolower($host));

        static $reg_name = '/^(
            (?<unreserved>[a-z0-9_~\-\.])|
            (?<sub_delims>[!$&\'()*+,;=])|
            (?<encoded>%[A-F0-9]{2})
        )+$/x';
        if (preg_match($reg_name, $formatted_host)) {
            return $formatted_host;
        }

        static $gen_delims = '/[:\/?#\[\]@ ]/'; // Also includes space.
        if (preg_match($gen_delims, $formatted_host)) {
            throw new InvalidUri(sprintf('The host `%s` is invalid : a registered name can not contain URI delimiters or spaces', $host));
        }

        // @codeCoverageIgnoreStart
        // added because it is not possible in travis to disabled the ext/intl extension
        // see travis issue https://github.com/travis-ci/travis-ci/issues/4701
        static $idn_support = null;
        $idn_support = $idn_support ?? function_exists('idn_to_ascii') && defined('INTL_IDNA_VARIANT_UTS46');
        if (!$idn_support) {
            throw new MissingIdnSupport(sprintf('the host `%s` could not be processed for IDN. Verify that ext/intl is installed for IDN support and that ICU is at least version 4.6.', $host));
        }
        // @codeCoverageIgnoreEnd

        $formatted_host = idn_to_ascii($formatted_host, 0, INTL_IDNA_VARIANT_UTS46, $arr);
        if (!$arr['errors']) {
            return $formatted_host;
        }

        throw new InvalidUri(sprintf('The host `%s` is invalid : %s', $host, $this->getIDNAErrors($arr['errors'])));
    }

    /**
     * Retrieves and format IDNA conversion error message
     *
     * @see http://icu-project.org/apiref/icu4j/com/ibm/icu/text/IDNA.Error.html
     *
     * @param int $error_byte
     *
     * @return string
     */
    private function getIDNAErrors(int $error_byte): string
    {
        /**
         * IDNA errors
         */
        static $idn_errors = [
            IDNA_ERROR_EMPTY_LABEL => 'a non-final domain name label (or the whole domain name) is empty',
            IDNA_ERROR_LABEL_TOO_LONG => 'a domain name label is longer than 63 bytes',
            IDNA_ERROR_DOMAIN_NAME_TOO_LONG => 'a domain name is longer than 255 bytes in its storage form',
            IDNA_ERROR_LEADING_HYPHEN => 'a label starts with a hyphen-minus ("-")',
            IDNA_ERROR_TRAILING_HYPHEN => 'a label ends with a hyphen-minus ("-")',
            IDNA_ERROR_HYPHEN_3_4 => 'a label contains hyphen-minus ("-") in the third and fourth positions',
            IDNA_ERROR_LEADING_COMBINING_MARK => 'a label starts with a combining mark',
            IDNA_ERROR_DISALLOWED => 'a label or domain name contains disallowed characters',
            IDNA_ERROR_PUNYCODE => 'a label starts with "xn--" but does not contain valid Punycode',
            IDNA_ERROR_LABEL_HAS_DOT => 'a label contains a dot=full stop',
            IDNA_ERROR_INVALID_ACE_LABEL => 'An ACE label does not contain a valid label string',
            IDNA_ERROR_BIDI => 'a label does not meet the IDNA BiDi requirements (for right-to-left characters)',
            IDNA_ERROR_CONTEXTJ => 'a label does not meet the IDNA CONTEXTJ requirements',
        ];

        $res = [];
        foreach ($idn_errors as $error => $reason) {
            if ($error_byte & $error) {
                $res[] = $reason;
            }
        }

        return empty($res) ? 'Unknown IDNA conversion error.' : implode(', ', $res).'.';
    }

    /**
     * Validate and Format the IPv6/IPvfuture host
     *
     * @param string $host
     *
     * @throws InvalidUri if the submitted host is not a valid IPv6
     *
     * @return string
     */
    private function formatIp(string $host): string
    {
        $ip = substr($host, 1, -1);
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $host;
        }

        static $ip_future = '/^
            v(?<version>[A-F0-9])+\.
            (?:
                (?<unreserved>[a-z0-9_~\-\.])|
                (?<sub_delims>[!$&\'()*+,;=:])  # also include the : character
            )+
        $/ix';
        if (preg_match($ip_future, $ip, $matches) && !in_array($matches['version'], ['4', '6'], true)) {
            return $host;
        }

        if (false === ($pos = strpos($ip, '%'))) {
            throw new InvalidUri(sprintf('The host `%s` is invalid : the IP host is malformed', $host));
        }

        static $gen_delims = '/[:\/?#\[\]@ ]/'; // Also includes space.
        if (preg_match($gen_delims, rawurldecode(substr($ip, $pos)))) {
            throw new InvalidUri(sprintf('The host `%s` is invalid : the IP host is malformed', $host));
        }

        $ip = substr($ip, 0, $pos);
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            throw new InvalidUri(sprintf('The host `%s` is invalid : the IP host is malformed', $host));
        }

        //Only the address block fe80::/10 can have a Zone ID attach to
        //let's detect the link local significant 10 bits
        static $address_block = "\xfe\x80";

        if (substr(inet_pton($ip) & $address_block, 0, 2) === $address_block) {
            return $host;
        }

        throw new InvalidUri(sprintf('The host `%s` is invalid : the IP host is malformed', $host));
    }

    /**
     * Format the Port component
     *
     * @param int|null $port
     *
     * @return int|null
     */
    protected function formatPort(int $port = null)
    {
        $port = $this->filterPort($port);

        if (isset(static::$supported_schemes[$this->scheme])
            && static::$supported_schemes[$this->scheme] === $port) {
            return null;
        }

        return $port;
    }

    /**
     * Filter the Port component
     *
     * @param int|null $port
     *
     * @throws InvalidUri if the port is invalid
     *
     * @return int|null
     */
    protected function filterPort(int $port = null)
    {
        if (null === $port) {
            return $port;
        }

        if ($port < 0) {
            throw new InvalidUri(sprintf('The port `%s` is invalid', $port));
        }

        return $port;
    }

    /**
     * Generate the URI authority part
     *
     * @return string|null
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
    protected function formatPath(string $path): string
    {
        static $pattern = '/(?:[^'.self::REGEXP_CHARS_UNRESERVED.self::REGEXP_CHARS_SUBDELIM.'%:@\/}{]++|%(?![A-Fa-f0-9]{2}))/';
        return preg_replace_callback($pattern, [Uri::class, 'urlEncodeMatch'], $path);
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
     * @return string|null
     */
    protected function formatQueryAndFragment(string $component = null)
    {
        if (null === $component || '' === $component) {
            return $component;
        }

        static $pattern = '/(?:[^'.self::REGEXP_CHARS_UNRESERVED.self::REGEXP_CHARS_SUBDELIM.'%:@\/\?]++|%(?![A-Fa-f0-9]{2}))/';
        return preg_replace_callback($pattern, [Uri::class, 'urlEncodeMatch'], $component);
    }

    /**
     * assert the URI internal state is valid
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3
     * @see https://tools.ietf.org/html/rfc3986#section-3.3
     *
     * @throws InvalidUri if the URI is in an invalid state according to RFC3986
     * @throws InvalidUri if the URI is in an invalid state according to scheme specific rules
     */
    protected function assertValidState()
    {
        $this->uri = null;

        if (null !== $this->authority && ('' !== $this->path && '/' !== $this->path[0])) {
            throw new InvalidUri(
                'If an authority is present the path must be empty or start with a `/`'
            );
        }

        if (null === $this->authority && 0 === strpos($this->path, '//')) {
            throw new InvalidUri(sprintf(
                'If there is no authority the path `%s` can not start with a `//`',
                $this->path
            ));
        }

        if (null === $this->authority
            && null === $this->scheme
            && false !== ($pos = strpos($this->path, ':'))
            && false === strpos(substr($this->path, 0, $pos), '/')
        ) {
            throw new InvalidUri(
                'In absence of a scheme and an authority the first path segment cannot contain a colon (":") character.'
            );
        }

        if (!$this->isValidUri()) {
            throw new InvalidUri(sprintf(
                'The uri `%s` is invalid for the following scheme(s): `%s`',
                $this->getUriString($this->scheme, $this->authority, $this->path, $this->query, $this->fragment),
                implode(', ', array_keys(static::$supported_schemes))
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
    protected function isValidUri(): bool
    {
        return true;
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
    protected function getUriString(
        string $scheme = null,
        string $authority = null,
        string $path,
        string $query = null,
        string $fragment = null
    ): string {
        if (null !== $scheme) {
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
     * {@inheritdoc}
     */
    public function __toString()
    {
        $this->uri = $this->uri ?? $this->getUriString(
            $this->scheme,
            $this->authority,
            $this->path,
            $this->query,
            $this->fragment
        );

        return $this->uri;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return $this->__toString();
    }

    /**
     * {@inheritdoc}
     */
    public function __debugInfo()
    {
        return [
            'scheme' => $this->scheme,
            'user_info' => isset($this->user_info) ? preg_replace(',\:(.*).?$,', ':***', $this->user_info) : null,
            'host' => $this->host,
            'port' => $this->port,
            'path' => $this->path,
            'query' => $this->query,
            'fragment' => $this->fragment,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getScheme(): string
    {
        return (string) $this->scheme;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthority(): string
    {
        return (string) $this->authority;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserInfo(): string
    {
        return (string) $this->user_info;
    }

    /**
     * {@inheritdoc}
     */
    public function getHost(): string
    {
        return (string) $this->host;
    }

    /**
     * {@inheritdoc}
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * {@inheritdoc}
     */
    public function getQuery(): string
    {
        return (string) $this->query;
    }

    /**
     * {@inheritdoc}
     */
    public function getFragment(): string
    {
        return (string) $this->fragment;
    }

    /**
     * {@inheritdoc}
     */
    public function withScheme($scheme)
    {
        $scheme = $this->formatScheme($this->filterString($scheme));
        if ('' === $scheme) {
            $scheme = null;
        }

        if ($scheme === $this->scheme) {
            return $this;
        }

        $clone = clone $this;
        $clone->scheme = $scheme;
        $clone->port = $clone->formatPort($clone->port);
        $clone->authority = $clone->setAuthority();
        $clone->assertValidState();

        return $clone;
    }

    /**
     * Filter a string.
     *
     * @param mixed $str the value to evaluate as a string
     *
     * @throws InvalidUri if the submitted data can not be converted to string
     *
     * @return string
     */
    private function filterString($str): string
    {
        if (!is_scalar($str) && !method_exists($str, '__toString')) {
            throw new TypeError(sprintf('the component must be a string, a scalar or a stringable object %s given', gettype($str)));
        }

        $str = (string) $str;
        static $pattern = '/[\x00-\x1f\x7f]/';
        if (!preg_match($pattern, $str)) {
            return $str;
        }

        throw new InvalidUri(sprintf('the submitted URI `%s` contains invalid characters', $str));
    }

    /**
     * {@inheritdoc}
     */
    public function withUserInfo($user, $password = null)
    {
        $user_info = null;
        $user = $this->filterString($user);
        if (null !== $password) {
            $password = $this->filterString($password);
        }

        if ('' !== $user) {
            $user_info = $this->formatUserInfo($user, $password);
        }

        if ($user_info === $this->user_info) {
            return $this;
        }

        $clone = clone $this;
        $clone->user_info = $user_info;
        $clone->authority = $clone->setAuthority();
        $clone->assertValidState();

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withHost($host)
    {
        $host = $this->formatHost($this->filterString($host));
        if ('' === $host) {
            $host = null;
        }

        if ($host === $this->host) {
            return $this;
        }

        $clone = clone $this;
        $clone->host = $host;
        $clone->authority = $clone->setAuthority();
        $clone->assertValidState();

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withPort($port)
    {
        $port = $this->formatPort($port);
        if ($port === $this->port) {
            return $this;
        }

        $clone = clone $this;
        $clone->port = $port;
        $clone->authority = $clone->setAuthority();
        $clone->assertValidState();

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withPath($path)
    {
        $path = $this->formatPath($this->filterString($path));
        if ($path === $this->path) {
            return $this;
        }

        $clone = clone $this;
        $clone->path = $path;
        $clone->assertValidState();

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withQuery($query)
    {
        $query = $this->formatQueryAndFragment($this->filterString($query));
        if ('' === $query) {
            $query = null;
        }

        if ($query === $this->query) {
            return $this;
        }

        $clone = clone $this;
        $clone->query = $query;
        $clone->assertValidState();

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withFragment($fragment)
    {
        $fragment = $this->formatQueryAndFragment($this->filterString($fragment));
        if ('' === $fragment) {
            $fragment = null;
        }

        if ($fragment === $this->fragment) {
            return $this;
        }

        $clone = clone $this;
        $clone->fragment = $fragment;
        $clone->assertValidState();

        return $clone;
    }
}
