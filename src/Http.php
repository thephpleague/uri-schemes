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
use League\Uri\Exception\MalformedUri;
use Psr\Http\Message\UriInterface as Psr7UriInterface;
use TypeError;
use function gettype;
use function in_array;
use function is_scalar;
use function method_exists;
use function preg_match;
use function sprintf;

final class Http implements Psr7UriInterface, JsonSerializable
{
    /**
     * @var Uri
     */
    private $uri;

    /**
     * Static method called by PHP's var export.
     *
     * @return static
     */
    public static function __set_state(array $components): self
    {
        return new self($components['uri']);
    }
    /**
     * Create a new instance from a hash of parse_url parts.
     *
     * @param array $components a hash representation of the URI similar
     *                          to PHP parse_url function result
     */
    public static function createFromComponents(array $components): self
    {
        return new self(Uri::createFromComponents($components));
    }

    /**
     * Create a new instance from the environment.
     */
    public static function createFromServer(array $server): self
    {
        return new self(Uri::createFromServer($server));
    }

    /**
     * Create a new instance from a string.
     *
     * @param string|mixed $uri
     */
    public static function createFromString($uri = ''): self
    {
        return new self(Uri::createFromString($uri));
    }

    /**
     * New instance.
     */
    public function __construct(Uri $uri)
    {
        $scheme = $uri->getScheme();
        $port = $uri->getPort();
        if (in_array($scheme, ['http', 'https'], true)
            && (null !== $port && ($port < 1 || $port > 65536))) {
            throw new MalformedUri('Invalid HTTPS URI according to PSR-7');
        }

        $this->uri = $uri;
    }

    /**
     * {@inheritdoc}
     */
    public function getScheme(): string
    {
        return (string) $this->uri->getScheme();
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthority(): string
    {
        return (string) $this->uri->getAuthority();
    }

    /**
     * {@inheritdoc}
     */
    public function getUserInfo(): string
    {
        return (string) $this->uri->getUserInfo();
    }

    /**
     * {@inheritdoc}
     */
    public function getHost(): string
    {
        return (string) $this->uri->getHost();
    }

    /**
     * {@inheritdoc}
     */
    public function getPort(): ?int
    {
        return $this->uri->getPort();
    }

    /**
     * {@inheritdoc}
     */
    public function getPath(): string
    {
        return $this->uri->getPath();
    }

    /**
     * {@inheritdoc}
     */
    public function getQuery(): string
    {
        return (string) $this->uri->getQuery();
    }

    /**
     * {@inheritdoc}
     */
    public function getFragment(): string
    {
        return (string) $this->uri->getFragment();
    }

    /**
     * {@inheritdoc}
     */
    public function withScheme($scheme): self
    {
        $scheme = $this->filterString($scheme);
        if ('' === $scheme) {
            $scheme = null;
        }

        $uri = $this->uri->withScheme($scheme);
        if ((string) $uri === (string) $this->uri) {
            return $this;
        }

        return new self($uri);
    }

    /**
     * Filter a string.
     *
     * @param mixed $str the value to evaluate as a string
     *
     * @throws MalformedUri if the submitted data can not be converted to string
     *
     */
    private function filterString($str): ?string
    {
        if (!is_scalar($str) && !method_exists($str, '__toString')) {
            throw new TypeError(sprintf('The component must be a string, a scalar or a stringable object %s given', gettype($str)));
        }

        $str = (string) $str;
        static $pattern = '/[\x00-\x1f\x7f]/';
        if (1 !== preg_match($pattern, $str)) {
            return $str;
        }

        throw new MalformedUri(sprintf('The component `%s` contains invalid characters', $str));
    }

    /**
     * {@inheritdoc}
     */
    public function withUserInfo($user, $password = null): self
    {
        $user = $this->filterString($user);
        if ('' === $user) {
            $user = null;
        }

        $uri = $this->uri->withUserInfo($user, $password);
        if ((string) $uri === (string) $this->uri) {
            return $this;
        }

        return new self($uri);
    }

    /**
     * {@inheritdoc}
     */
    public function withHost($host): self
    {
        $host = $this->filterString($host);
        if ('' === $host) {
            $host = null;
        }

        $uri = $this->uri->withHost($host);
        if ((string) $uri === (string) $this->uri) {
            return $this;
        }

        return new self($uri);
    }

    /**
     * {@inheritdoc}
     */
    public function withPort($port): self
    {
        $uri = $this->uri->withPort($port);
        if ((string) $uri === (string) $this->uri) {
            return $this;
        }

        return new self($uri);
    }

    /**
     * {@inheritdoc}
     */
    public function withPath($path): self
    {
        $uri = $this->uri->withPath($path);
        if ((string) $uri === (string) $this->uri) {
            return $this;
        }

        return new self($uri);
    }

    /**
     * {@inheritdoc}
     */
    public function withQuery($query): self
    {
        $query = $this->filterString($query);
        if ('' === $query) {
            $query = null;
        }

        $uri = $this->uri->withQuery($query);
        if ((string) $uri === (string) $this->uri) {
            return $this;
        }

        return new self($uri);
    }

    /**
     * {@inheritdoc}
     */
    public function withFragment($fragment): self
    {
        $fragment = $this->filterString($fragment);
        if ('' === $fragment) {
            $fragment = null;
        }

        $uri = $this->uri->withFragment($fragment);
        if ((string) $uri === (string) $this->uri) {
            return $this;
        }

        return new self($uri);
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return $this->uri->__toString();
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): string
    {
        return $this->uri->__toString();
    }

    /**
     * {@inheritdoc}
     */
    public function __debugInfo(): array
    {
        return $this->uri->__debugInfo();
    }
}
