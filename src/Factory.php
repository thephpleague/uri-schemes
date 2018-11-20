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

use finfo;
use League\Uri\Exception\InvalidUri;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface as Psr7UriInterface;
use function array_map;
use function base64_decode;
use function base64_encode;
use function explode;
use function file_get_contents;
use function filter_var;
use function implode;
use function preg_match;
use function rawurlencode;
use function sprintf;
use function str_replace;
use function strlen;
use function strpos;
use function strtolower;
use function substr;
use const FILEINFO_MIME;
use const FILTER_FLAG_IPV4;
use const FILTER_NULL_ON_FAILURE;
use const FILTER_VALIDATE_BOOLEAN;
use const FILTER_VALIDATE_IP;

final class Factory implements UriFactoryInterface
{
    private const REGEXP_WINDOW_PATH = ',^(?<root>[a-zA-Z][:|\|]),';

    /**
     * Create a new instance from a data file path.
     *
     * @param resource|null $context
     *
     * @throws InvalidUri If the file does not exist or is not readable
     */
    public function createUriFromDataPath(string $path, $context = null): Uri
    {
        $file_args = [$path, false];
        $mime_args = [$path, FILEINFO_MIME];
        if (null !== $context) {
            $file_args[] = $context;
            $mime_args[] = $context;
        }

        $raw = @file_get_contents(...$file_args);
        if (false === $raw) {
            throw new InvalidUri(sprintf('The file `%s` does not exist or is not readable', $path));
        }

        return Uri::createFromComponents([
            'scheme' => 'data',
            'path' => str_replace(' ', '', (new finfo(FILEINFO_MIME))->file(...$mime_args)).';base64,'.base64_encode($raw),
        ]);
    }

    /**
     * Create a new instance from a Unix path string.
     */
    public function createUriFromUnixPath(string $uri = ''): Uri
    {
        $uri = implode('/', array_map('rawurlencode', explode('/', $uri)));
        if ('/' === ($uri[0] ?? '')) {
            return Uri::createFromComponents([
                'scheme' => 'file',
                'host' => '',
                'path' => $uri,
            ]);
        }

        return Uri::createFromComponents(['path' => $uri]);
    }

    /**
     * Create a new instance from a local Windows path string.
     */
    public function createUriFromWindowsPath(string $uri = ''): Uri
    {
        $root = '';
        if (1 === preg_match(self::REGEXP_WINDOW_PATH, $uri, $matches)) {
            $root = substr($matches['root'], 0, -1).':';
            $uri = substr($uri, strlen($root));
        }
        $uri = str_replace('\\', '/', $uri);
        $uri = implode('/', array_map('rawurlencode', explode('/', $uri)));

        //Local Windows absolute path
        if ('' !== $root) {
            return Uri::createFromComponents([
                'scheme' => 'file',
                'host' => '',
                'path' => '/'.$root.$uri,
            ]);
        }

        //UNC Windows Path
        if ('//' === substr($uri, 0, 2)) {
            $parts = explode('/', substr($uri, 2), 2) + [1 => null];

            return Uri::createFromComponents([
                'scheme' => 'file',
                'host' => $parts[0],
                'path' => '/'.$parts[1],
            ]);
        }

        return Uri::createFromComponents(['path' => $uri]);
    }

    /**
     * {@inheritdoc}
     */
    public function createUri(string $uri = ''): Psr7UriInterface
    {
        return Http::createFromString($uri);
    }

    /**
     * Create a new instance from the environment.
     */
    public function createUriFromEnvironment(array $server): Psr7UriInterface
    {
        [$user, $pass] = $this->fetchUserInfo($server);
        [$host, $port] = $this->fetchHostname($server);
        [$path, $query] = $this->fetchRequestUri($server);

        return Http::createFromComponents([
            'scheme' => $this->fetchScheme($server),
            'user' => $user,
            'pass' => $pass,
            'host' => $host,
            'port' => $port,
            'path' => $path,
            'query' => $query,
            'fragment' => null,
        ]);
    }

    /**
     * Returns the environment scheme.
     */
    private function fetchScheme(array $server): string
    {
        $server += ['HTTPS' => ''];
        $res = filter_var($server['HTTPS'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return $res !== false ? 'https' : 'http';
    }

    /**
     * Returns the environment user info.
     */
    private function fetchUserInfo(array $server): array
    {
        $server += ['PHP_AUTH_USER' => null, 'PHP_AUTH_PW' => null, 'HTTP_AUTHORIZATION' => ''];
        $user = $server['PHP_AUTH_USER'];
        $pass = $server['PHP_AUTH_PW'];
        if (0 === strpos(strtolower($server['HTTP_AUTHORIZATION']), 'basic')) {
            $userinfo = base64_decode(substr($server['HTTP_AUTHORIZATION'], 6), true);
            if (false === $userinfo) {
                throw new InvalidUri('The user info could not be detected');
            }
            [$user, $pass] = explode(':', $userinfo, 2) + [1 => null];
        }

        if (null !== $user) {
            $user = rawurlencode($user);
        }

        if (null !== $pass) {
            $pass = rawurlencode($pass);
        }

        return [$user, $pass];
    }

    /**
     * Returns the environment host.
     *
     * @throws InvalidUri If the host can not be detected
     */
    private function fetchHostname(array $server): array
    {
        $server += ['SERVER_PORT' => null];
        if (null !== $server['SERVER_PORT']) {
            $server['SERVER_PORT'] = (int) $server['SERVER_PORT'];
        }

        if (isset($server['HTTP_HOST'])) {
            preg_match(',^(?<host>(\[.*\]|[^:])*)(\:(?<port>[^/?\#]*))?$,x', $server['HTTP_HOST'], $matches);

            return [
                $matches['host'],
                isset($matches['port']) ? (int) $matches['port'] : $server['SERVER_PORT'],
            ];
        }

        if (!isset($server['SERVER_ADDR'])) {
            throw new InvalidUri('The host could not be detected');
        }

        if (false === filter_var($server['SERVER_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $server['SERVER_ADDR'] = '['.$server['SERVER_ADDR'].']';
        }

        return [$server['SERVER_ADDR'], $server['SERVER_PORT']];
    }

    /**
     * Returns the environment path.
     */
    private function fetchRequestUri(array $server): array
    {
        $server += ['IIS_WasUrlRewritten' => null, 'UNENCODED_URL' => '', 'PHP_SELF' => '', 'QUERY_STRING' => null];
        if ('1' === $server['IIS_WasUrlRewritten'] && '' !== $server['UNENCODED_URL']) {
            return explode('?', $server['UNENCODED_URL'], 2) + [1 => null];
        }

        if (isset($server['REQUEST_URI'])) {
            [$path, ] = explode('?', $server['REQUEST_URI'], 2);
            $query = ('' !== $server['QUERY_STRING']) ? $server['QUERY_STRING'] : null;

            return [$path, $query];
        }

        return [$server['PHP_SELF'], $server['QUERY_STRING']];
    }
}
