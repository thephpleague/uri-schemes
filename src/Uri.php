<?php

/**
 * League.Uri (http://uri.thephpleague.com).
 *
 * @package    League\Uri
 * @subpackage League\Uri\Schemes
 * @author     Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @license    https://github.com/thephpleague/uri-schemes/blob/master/LICENSE (MIT License)
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
use League\Uri\Exception\MalformedUri;
use League\Uri\Parser\RFC3986;
use TypeError;
use UnexpectedValueException;
use function array_filter;
use function base64_decode;
use function base64_encode;
use function count;
use function defined;
use function explode;
use function function_exists;
use function idn_to_ascii;
use function implode;
use function in_array;
use function inet_pton;
use function is_scalar;
use function mb_detect_encoding;
use function method_exists;
use function preg_match;
use function preg_replace;
use function preg_replace_callback;
use function rawurlencode;
use function sprintf;
use function str_replace;
use function strlen;
use function strpos;
use function strtolower;
use function substr;
use const FILTER_FLAG_IPV6;
use const FILTER_VALIDATE_IP;
use const IDNA_ERROR_BIDI;
use const IDNA_ERROR_CONTEXTJ;
use const IDNA_ERROR_DISALLOWED;
use const IDNA_ERROR_DOMAIN_NAME_TOO_LONG;
use const IDNA_ERROR_EMPTY_LABEL;
use const IDNA_ERROR_HYPHEN_3_4;
use const IDNA_ERROR_INVALID_ACE_LABEL;
use const IDNA_ERROR_LABEL_HAS_DOT;
use const IDNA_ERROR_LABEL_TOO_LONG;
use const IDNA_ERROR_LEADING_COMBINING_MARK;
use const IDNA_ERROR_LEADING_HYPHEN;
use const IDNA_ERROR_PUNYCODE;
use const IDNA_ERROR_TRAILING_HYPHEN;
use const INTL_IDNA_VARIANT_UTS46;

final class Uri implements JsonSerializable
{
    /**
     * RFC3986 invalid characters.
     *
     * @see http://tools.ietf.org/html/rfc3986#section-2.2
     *
     * @var string
     */
    private const REGEXP_INVALID_CHARS = '/[\x00-\x1f\x7f]/';

    /**
     * RFC3986 Sub delimiter characters regular expression pattern.
     *
     * @see http://tools.ietf.org/html/rfc3986#section-2.2
     *
     * @var string
     */
    private const REGEXP_CHARS_SUBDELIM = "\!\$&'\(\)\*\+,;\=%";

    /**
     * RFC3986 unreserved characters regular expression pattern.
     *
     * @see http://tools.ietf.org/html/rfc3986#section-2.3
     *
     * @var string
     */
    private const REGEXP_CHARS_UNRESERVED = 'A-Za-z0-9_\-\.~';


    private const REGEXP_SCHEME = '/^[a-z][a-z\+\.\-]*$/';

    private const REGEXP_HOST_REGNAME = '/^(
        (?<unreserved>[a-z0-9_~\-\.])|
        (?<sub_delims>[!$&\'()*+,;=])|
        (?<encoded>%[A-F0-9]{2})
    )+$/x';

    private const REGEXP_HOST_GEN_DELIMS = '/[:\/?#\[\]@ ]/'; // Also includes space.

    private const REGEXP_HOST_IPFUTURE = '/^
        v(?<version>[A-F0-9])+\.
        (?:
            (?<unreserved>[a-z0-9_~\-\.])|
            (?<sub_delims>[!$&\'()*+,;=:])  # also include the : character
        )+
    $/ix';

    private const HOST_ADDRESS_BLOCK = "\xfe\x80";

    private const REGEXP_FILE_PATH = ',^(?<delim>/)?(?<root>[a-zA-Z][:|\|])(?<rest>.*)?,';

    private const REGEXP_MIMETYPE = ',^\w+/[-.\w]+(?:\+[-.\w]+)?$,';

    private const REGEXP_BINARY = ',(;|^)base64$,';

    /**
     * IDNA errors.
     */
    private const IDNA_ERRORS = [
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

    /**
     * Supported schemes and corresponding default port.
     *
     * @var array
     */
    private const SCHEME_DEFAULT_PORT = [
        'data' => null,
        'file' => null,
        'ftp' => 21,
        'gopher' => 70,
        'http' => 80,
        'https' => 443,
        'ws' => 80,
        'wss' => 443,
    ];

    /**
     * URI validation methods per scheme.
     *
     * @var array
     */
    private const SCHEME_VALIDATION_METHOD = [
        'data' => 'isUriWithSchemeAndPathOnly',
        'file' => 'isUriWithSchemeHostAndPathOnly',
        'ftp' => 'isNonEmptyHostUriWithoutFragmentAndQuery',
        'gopher' => 'isNonEmptyHostUriWithoutFragmentAndQuery',
        'http' => 'isNonEmptyHostUri',
        'https' => 'isNonEmptyHostUri',
        'ws' => 'isNonEmptyHostUriWithoutFragment',
        'wss' => 'isNonEmptyHostUriWithoutFragment',
    ];

    /**
     * URI scheme component.
     *
     * @var string|null
     */
    private $scheme;

    /**
     * URI user info part.
     *
     * @var string|null
     */
    private $user_info;

    /**
     * URI host component.
     *
     * @var string|null
     */
    private $host;

    /**
     * URI port component.
     *
     * @var int|null
     */
    private $port;

    /**
     * URI authority string representation.
     *
     * @var string|null
     */
    private $authority;

    /**
     * URI path component.
     *
     * @var string
     */
    private $path = '';

    /**
     * URI query component.
     *
     * @var string|null
     */
    private $query;

    /**
     * URI fragment component.
     *
     * @var string|null
     */
    private $fragment;

    /**
     * URI string representation.
     *
     * @var string|null
     */
    private $uri;

    /**
     * {@inheritdoc}
     */
    public static function __set_state(array $components): self
    {
        $components['user'] = null;
        $components['pass'] = null;
        if (null !== $components['user_info']) {
            [$components['user'], $components['pass']] = explode(':', $components['user_info'], 2) + [1 => null];
        }

        return new self(
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
     * Create a new instance from a string.
     *
     * @param string|mixed $uri
     */
    public static function createFromString($uri = ''): self
    {
        $components = RFC3986::parse($uri);

        return new self(
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
     * Create a new instance from a hash of parse_url parts.
     *
     * Create an new instance from a hash representation of the URI similar
     * to PHP parse_url function result
     */
    public static function createFromComponents(array $components = []): self
    {
        $components += [
            'scheme' => null, 'user' => null, 'pass' => null, 'host' => null,
            'port' => null, 'path' => '', 'query' => null, 'fragment' => null,
        ];

        return new self(
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
     * Create a new instance.
     *
     * @param ?string $scheme
     * @param ?string $user
     * @param ?string $pass
     * @param ?string $host
     * @param ?int    $port
     * @param ?string $query
     * @param ?string $fragment
     */
    private function __construct(
        ?string $scheme,
        ?string $user,
        ?string $pass,
        ?string $host,
        ?int $port,
        string $path,
        ?string $query,
        ?string $fragment
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
     * Format the Scheme and Host component.
     *
     * @param ?string $scheme
     *
     * @throws MalformedUri if the scheme is invalid
     */
    private function formatScheme(?string $scheme): ?string
    {
        if ('' === $scheme || null === $scheme) {
            return $scheme;
        }

        $formatted_scheme = strtolower($scheme);
        if (1 === preg_match(self::REGEXP_SCHEME, $formatted_scheme)) {
            return $formatted_scheme;
        }

        throw new MalformedUri(sprintf('The scheme `%s` is invalid', $scheme));
    }

    /**
     * Set the UserInfo component.
     *
     * @param ?string $user
     * @param ?string $password
     */
    private function formatUserInfo(?string $user, ?string $password): ?string
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
     * Returns the RFC3986 encoded string matched.
     */
    private static function urlEncodeMatch(array $matches): string
    {
        return rawurlencode($matches[0]);
    }

    /**
     * Validate and Format the Host component.
     *
     * @param ?string $host
     */
    private function formatHost(?string $host): ?string
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
     * @throws MalformedUri if the submitted host is not a valid registered name
     */
    private function formatRegisteredName(string $host): string
    {
        // @codeCoverageIgnoreStart
        // added because it is not possible in travis to disabled the ext/intl extension
        // see travis issue https://github.com/travis-ci/travis-ci/issues/4701
        static $idn_support = null;
        $idn_support = $idn_support ?? function_exists('idn_to_ascii') && defined('INTL_IDNA_VARIANT_UTS46');
        // @codeCoverageIgnoreEnd

        $formatted_host = rawurldecode(strtolower($host));
        if (1 === preg_match(self::REGEXP_HOST_REGNAME, $formatted_host)) {
            if (false === strpos($formatted_host, 'xn--')) {
                return $formatted_host;
            }

            // @codeCoverageIgnoreStart
            if (!$idn_support) {
                throw new InvalidUri(sprintf('the host `%s` could not be processed for IDN. Verify that ext/intl is installed for IDN support and that ICU is at least version 4.6.', $host));
            }
            // @codeCoverageIgnoreEnd

            $unicode = idn_to_utf8($host, 0, INTL_IDNA_VARIANT_UTS46, $arr);
            if (0 !== $arr['errors']) {
                throw new MalformedUri(sprintf('The host `%s` is invalid : %s', $host, $this->getIDNAErrors($arr['errors'])));
            }

            // @codeCoverageIgnoreStart
            if (false === $unicode) {
                throw new UnexpectedValueException(sprintf('The Intl extension is misconfigured for %s, please correct this issue before proceeding.', PHP_OS));
            }
            // @codeCoverageIgnoreEnd

            return $formatted_host;
        }

        if (1 === preg_match(self::REGEXP_HOST_GEN_DELIMS, $formatted_host)) {
            throw new MalformedUri(sprintf('The host `%s` is invalid : a registered name can not contain URI delimiters or spaces', $host));
        }

        // @codeCoverageIgnoreStart
        if (!$idn_support) {
            throw new InvalidUri(sprintf('the host `%s` could not be processed for IDN. Verify that ext/intl is installed for IDN support and that ICU is at least version 4.6.', $host));
        }
        // @codeCoverageIgnoreEnd

        $formatted_host = idn_to_ascii($formatted_host, 0, INTL_IDNA_VARIANT_UTS46, $arr);
        if (0 !== $arr['errors']) {
            throw new MalformedUri(sprintf('The host `%s` is invalid : %s', $host, $this->getIDNAErrors($arr['errors'])));
        }

        // @codeCoverageIgnoreStart
        if (false === $formatted_host) {
            throw new UnexpectedValueException(sprintf('The Intl extension is misconfigured for %s, please correct this issue before proceeding.', PHP_OS));
        }
        // @codeCoverageIgnoreEnd

        return $formatted_host;
    }

    /**
     * Retrieves and format IDNA conversion error message.
     *
     * @see http://icu-project.org/apiref/icu4j/com/ibm/icu/text/IDNA.Error.html
     */
    private function getIDNAErrors(int $error_byte): string
    {
        $res = [];
        foreach (self::IDNA_ERRORS as $error => $reason) {
            if (1 === ($error_byte & $error)) {
                $res[] = $reason;
            }
        }

        return [] === $res ? 'Unknown IDNA conversion error.' : implode(', ', $res).'.';
    }

    /**
     * Validate and Format the IPv6/IPvfuture host.
     *
     * @throws MalformedUri if the submitted host is not a valid IP host
     */
    private function formatIp(string $host): string
    {
        $ip = substr($host, 1, -1);
        if (false !== filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $host;
        }

        if (1 === preg_match(self::REGEXP_HOST_IPFUTURE, $ip, $matches) && !in_array($matches['version'], ['4', '6'], true)) {
            return $host;
        }

        $pos = strpos($ip, '%');
        if (false === $pos) {
            throw new MalformedUri(sprintf('The host `%s` is invalid : the IP host is malformed', $host));
        }

        if (1 === preg_match(self::REGEXP_HOST_GEN_DELIMS, rawurldecode(substr($ip, $pos)))) {
            throw new MalformedUri(sprintf('The host `%s` is invalid : the IP host is malformed', $host));
        }

        $ip = substr($ip, 0, $pos);
        if (false === filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            throw new MalformedUri(sprintf('The host `%s` is invalid : the IP host is malformed', $host));
        }

        //Only the address block fe80::/10 can have a Zone ID attach to
        //let's detect the link local significant 10 bits
        if (0 === strpos((string) inet_pton($ip), self::HOST_ADDRESS_BLOCK)) {
            return $host;
        }

        throw new MalformedUri(sprintf('The host `%s` is invalid : the IP host is malformed', $host));
    }

    /**
     * Format the Port component.
     *
     * @param null|mixed $port
     */
    private function formatPort($port = null): ?int
    {
        if (null === $port || '' === $port) {
            return null;
        }

        if (!is_int($port) && !(is_string($port) && 1 === preg_match('/^\d*$/', $port))) {
            throw new MalformedUri(sprintf('The port `%s` is invalid', $port));
        }

        $port = (int) $port;
        if (0 > $port) {
            throw new MalformedUri(sprintf('The port `%s` is invalid', $port));
        }

        $defaultPort = self::SCHEME_DEFAULT_PORT[$this->scheme] ?? null;
        if ($defaultPort === $port) {
            return null;
        }

        return $port;
    }

    /**
     * Generate the URI authority part.
     *
     */
    private function setAuthority(): ?string
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
     * Format the Path component.
     */
    private function formatPath(string $path): string
    {
        $path = $this->formatDataPath($path);

        static $pattern = '/(?:[^'.self::REGEXP_CHARS_UNRESERVED.self::REGEXP_CHARS_SUBDELIM.'%:@\/}{]++\|%(?![A-Fa-f0-9]{2}))/';

        $path = (string) preg_replace_callback($pattern, [Uri::class, 'urlEncodeMatch'], $path);

        return $this->formatFilePath($path);
    }

    /**
     * Filter the Path component.
     *
     * @see https://tools.ietf.org/html/rfc2397
     *
     * @throws InvalidUri If the path is not compliant with RFC2397
     */
    private function formatDataPath(string $path): string
    {
        if ('data' !== $this->scheme) {
            return $path;
        }

        if ('' == $path) {
            return 'text/plain;charset=us-ascii,';
        }

        if (false === mb_detect_encoding($path, 'US-ASCII', true) || false === strpos($path, ',')) {
            throw new MalformedUri(sprintf('The path `%s` is invalid according to RFC2937', $path));
        }

        $parts = explode(',', $path, 2) + [1 => null];
        $mediatype = explode(';', (string) $parts[0], 2) + [1 => null];
        $data = (string) $parts[1];
        $mimetype = $mediatype[0];
        if (null === $mimetype || '' === $mimetype) {
            $mimetype = 'text/plain';
        }

        $parameters = $mediatype[1];
        if (null === $parameters || '' === $parameters) {
            $parameters = 'charset=us-ascii';
        }

        $this->assertValidPath($mimetype, $parameters, $data);

        return $mimetype.';'.$parameters.','.$data;
    }

    /**
     * Assert the path is a compliant with RFC2397.
     *
     * @see https://tools.ietf.org/html/rfc2397
     *
     * @throws MalformedUri If the mediatype or the data are not compliant with the RFC2397
     */
    private function assertValidPath(string $mimetype, string $parameters, string $data): void
    {
        if (1 !== preg_match(self::REGEXP_MIMETYPE, $mimetype)) {
            throw new MalformedUri(sprintf('The path mimetype `%s` is invalid', $mimetype));
        }

        $is_binary = 1 === preg_match(self::REGEXP_BINARY, $parameters, $matches);
        if ($is_binary) {
            $parameters = substr($parameters, 0, - strlen($matches[0]));
        }

        $res = array_filter(array_filter(explode(';', $parameters), [$this, 'validateParameter']));
        if ([] !== $res) {
            throw new MalformedUri(sprintf('The path paremeters `%s` is invalid', $parameters));
        }

        if (!$is_binary) {
            return;
        }

        $res = base64_decode($data, true);
        if (false === $res || $data !== base64_encode($res)) {
            throw new MalformedUri(sprintf('The path data `%s` is invalid', $data));
        }
    }

    /**
     * Validate mediatype parameter.
     */
    private function validateParameter(string $parameter): bool
    {
        $properties = explode('=', $parameter);

        return 2 != count($properties) || strtolower($properties[0]) === 'base64';
    }

    private function formatFilePath(string $path): string
    {
        if ('file' !== $this->scheme) {
            return $path;
        }

        $replace = static function (array $matches): string {
            return $matches['delim'].str_replace('|', ':', $matches['root']).$matches['rest'];
        };

        return (string) preg_replace_callback(self::REGEXP_FILE_PATH, $replace, $path);
    }

    /**
     * Format the Query or the Fragment component.
     *
     * Returns a array containing:
     * <ul>
     * <li> the formatted component (a string or null)</li>
     * <li> a boolean flag telling wether the delimiter is to be added to the component
     * when building the URI string representation</li>
     * </ul>
     *
     * @param ?string $component
     */
    private function formatQueryAndFragment(?string $component): ?string
    {
        if (null === $component || '' === $component) {
            return $component;
        }

        static $pattern = '/(?:[^'.self::REGEXP_CHARS_UNRESERVED.self::REGEXP_CHARS_SUBDELIM.'%:@\/\?]++|%(?![A-Fa-f0-9]{2}))/';
        return preg_replace_callback($pattern, [Uri::class, 'urlEncodeMatch'], $component);
    }

    /**
     * assert the URI internal state is valid.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3
     * @see https://tools.ietf.org/html/rfc3986#section-3.3
     *
     * @throws MalformedUri if the URI is in an invalid state according to RFC3986
     * @throws MalformedUri if the URI is in an invalid state according to scheme specific rules
     */
    private function assertValidState(): void
    {
        if (null !== $this->authority && ('' !== $this->path && '/' !== $this->path[0])) {
            throw new MalformedUri('If an authority is present the path must be empty or start with a `/`');
        }

        if (null === $this->authority && 0 === strpos($this->path, '//')) {
            throw new MalformedUri(sprintf('If there is no authority the path `%s` can not start with a `//`', $this->path));
        }

        $pos = strpos($this->path, ':');
        if (null === $this->authority
            && null === $this->scheme
            && false !== $pos
            && false === strpos(substr($this->path, 0, $pos), '/')
        ) {
            throw new MalformedUri('In absence of a scheme and an authority the first path segment cannot contain a colon (":") character.');
        }

        $validationMethod = self::SCHEME_VALIDATION_METHOD[$this->scheme] ?? null;
        if (null === $validationMethod || true === $this->$validationMethod()) {
            $this->uri = null;

            return;
        }

        throw new MalformedUri(sprintf('The uri `%s` is invalid for the data scheme', (string) $this));
    }

    /**
     * URI validation for URI schemes which allows only scheme and path components.
     */
    private function isUriWithSchemeAndPathOnly()
    {
        return null === $this->authority
            && null === $this->query
            && null === $this->fragment;
    }

    /**
     * URI validation for URI schemes which allows only scheme, host and path components.
     */
    private function isUriWithSchemeHostAndPathOnly()
    {
        return null === $this->user_info
            && null === $this->port
            && null === $this->query
            && null === $this->fragment
            && !('' != $this->scheme && null === $this->host);
    }

    /**
     * URI validation for URI schemes which disallow the empty '' host.
     */
    private function isNonEmptyHostUri()
    {
        return '' !== $this->host
            && !(null !== $this->scheme && null === $this->host);
    }

    /**
     * URI validation for URIs schemes which disallow the empty '' host
     * and forbids the fragment component.
     */
    private function isNonEmptyHostUriWithoutFragment()
    {
        return $this->isNonEmptyHostUri() && null === $this->fragment;
    }

    /**
     * URI validation for URIs schemes which disallow the empty '' host
     * and forbids fragment and query components.
     */
    private function isNonEmptyHostUriWithoutFragmentAndQuery()
    {
        return $this->isNonEmptyHostUri() && null === $this->fragment && null === $this->query;
    }

    /**
     * Generate the URI string representation from its components.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-5.3
     * @param ?string $scheme
     * @param ?string $authority
     * @param ?string $query
     * @param ?string $fragment
     */
    private function getUriString(
        ?string $scheme,
        ?string $authority,
        string $path,
        ?string $query,
        ?string $fragment
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
     * Returns the string representation as a URI reference.
     *
     * @see http://tools.ietf.org/html/rfc3986#section-4.1
     *
     * @return string
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
    public function jsonSerialize(): string
    {
        return $this->__toString();
    }

    /**
     * {@inheritdoc}
     */
    public function __debugInfo(): array
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
     * Retrieve the scheme component of the URI.
     *
     * If no scheme is present, this method MUST return a null value.
     *
     * The value returned MUST be normalized to lowercase, per RFC 3986
     * Section 3.1.
     *
     * The trailing ":" character is not part of the scheme and MUST NOT be
     * added.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.1
     *
     * @return ?string
     */
    public function getScheme(): ?string
    {
        return $this->scheme;
    }

    /**
     * Retrieve the authority component of the URI.
     *
     * If no scheme is present, this method MUST return a null value.
     *
     * If the port component is not set or is the standard port for the current
     * scheme, it SHOULD NOT be included.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.2
     *
     * @return ?string
     */
    public function getAuthority(): ?string
    {
        return $this->authority;
    }

    /**
     * Retrieve the user information component of the URI.
     *
     * If no scheme is present, this method MUST return a null value.
     *
     * If a user is present in the URI, this will return that value;
     * additionally, if the password is also present, it will be appended to the
     * user value, with a colon (":") separating the values.
     *
     * The trailing "@" character is not part of the user information and MUST
     * NOT be added.
     *
     * @return ?string
     */
    public function getUserInfo(): ?string
    {
        return $this->user_info;
    }

    /**
     * Retrieve the user component of the URI.
     *
     * If no user is present, this method MUST return a null value.
     *
     * The trailing "@" and/or ":" characters are not part of the user and MUST NOT be added.
     *
     * @return ?string
     */
    public function getUser(): ?string
    {
        if (null === $this->user_info) {
            return null;
        }

        [$user, ] = explode(':', $this->user_info, 2);

        return $user;
    }

    /**
     * Retrieve the pass component of the URI.
     *
     * If no user is present, or no pass is present this method MUST return a null value.
     *
     * The trailing "@" characters are not part of the user and MUST NOT be added.
     *
     * @return ?string
     */
    public function getPass(): ?string
    {
        if (null === $this->user_info) {
            return null;
        }

        [$user, $pass] = explode(':', $this->user_info, 2) + [1 => null];

        return $pass;
    }

    /**
     * Retrieve the host component of the URI.
     *
     * If no host is present this method MUST return a null value.
     *
     * The value returned MUST be normalized to lowercase, per RFC 3986
     * Section 3.2.2.
     *
     * @see http://tools.ietf.org/html/rfc3986#section-3.2.2
     *
     * @return ?string
     */
    public function getHost(): ?string
    {
        return $this->host;
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
     * @return ?int
     */
    public function getPort(): ?int
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
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Retrieve the query string of the URI.
     *
     * If no host is present this method MUST return a null value.
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
     * @return ?string
     */
    public function getQuery(): ?string
    {
        return $this->query;
    }

    /**
     * Retrieve the fragment component of the URI.
     *
     * If no host is present this method MUST return a null value.
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
     * @return ?string
     */
    public function getFragment(): ?string
    {
        return $this->fragment;
    }

    /**
     * Return an instance with the specified scheme.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified scheme.
     *
     * A null value provided for the scheme is equivalent to removing the scheme
     * information.
     *
     * @param ?string $scheme
     *
     * @throws InvalidUri for invalid component or transformations
     *                    that would result in a object in invalid state.
     */
    public function withScheme($scheme): self
    {
        $scheme = $this->formatScheme($this->filterString($scheme));
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
     * @throws MalformedUri if the submitted data can not be converted to string
     */
    private function filterString($str): ?string
    {
        if (null === $str) {
            return $str;
        }

        if (!is_scalar($str) && !method_exists($str, '__toString')) {
            throw new TypeError(sprintf('The component must be a string, a scalar or a stringable object %s given', gettype($str)));
        }

        $str = (string) $str;
        if (1 !== preg_match(self::REGEXP_INVALID_CHARS, $str)) {
            return $str;
        }

        throw new MalformedUri(sprintf('The component `%s` contains invalid characters', $str));
    }

    /**
     * Return an instance with the specified user information.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified user information.
     *
     * Password is optional, but the user information MUST include the
     * user; a null value for the user is equivalent to removing user
     * information.
     *
     * @param ?string $user     The user name to use for authority.
     * @param ?string $password The password associated with $user.
     *
     * @throws InvalidUri for invalid component or transformations
     *                    that would result in a object in invalid state.
     */
    public function withUserInfo($user, $password = null): self
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
     * Return an instance with the specified host.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified host.
     *
     * A null value provided for the host is equivalent to removing the host
     * information.
     *
     * @param ?string $host The hostname to use with the new instance.
     *
     * @throws InvalidUri for invalid component or transformations
     *                    that would result in a object in invalid state.
     */
    public function withHost($host): self
    {
        $host = $this->formatHost($this->filterString($host));
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
     * @param ?int $port
     *
     * @throws InvalidUri for invalid component or transformations
     *                    that would result in a object in invalid state.
     */
    public function withPort($port): self
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
     * Return an instance with the specified path.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified path.
     *
     * The path can either be empty or absolute (starting with a slash) or
     * rootless (not starting with a slash). Implementations MUST support all
     * three syntaxes.
     *
     * Users can provide both encoded and decoded path characters.
     * Implementations ensure the correct encoding as outlined in getPath().
     *
     * @param string $path
     *
     * @throws InvalidUri for invalid component or transformations
     *                    that would result in a object in invalid state.
     */
    public function withPath($path): self
    {
        $path = $this->filterString($path);
        if (null === $path) {
            throw new TypeError('A path must be a string NULL given');
        }

        $path = $this->formatPath($path);
        if ($path === $this->path) {
            return $this;
        }

        $clone = clone $this;
        $clone->path = $path;
        $clone->assertValidState();

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
     * A null value provided for the query is equivalent to removing the query
     * information.
     *
     * @param ?string $query The query string to use with the new instance.
     *
     * @throws InvalidUri for invalid component or transformations
     *                    that would result in a object in invalid state.
     */
    public function withQuery($query): self
    {
        $query = $this->formatQueryAndFragment($this->filterString($query));
        if ($query === $this->query) {
            return $this;
        }

        $clone = clone $this;
        $clone->query = $query;
        $clone->assertValidState();

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
     * A null value provided for the fragment is equivalent to removing the fragment
     * information.
     *
     * @param ?string $fragment
     *
     * @throws InvalidUri for invalid component or transformations
     *                    that would result in a object in invalid state.
     */
    public function withFragment($fragment): self
    {
        $fragment = $this->formatQueryAndFragment($this->filterString($fragment));
        if ($fragment === $this->fragment) {
            return $this;
        }

        $clone = clone $this;
        $clone->fragment = $fragment;
        $clone->assertValidState();

        return $clone;
    }
}
