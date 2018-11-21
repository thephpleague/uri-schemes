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

namespace LeagueTest\Uri;

use League\Uri\Exception\InvalidUri;
use League\Uri\Factory;
use League\Uri\Http;
use PHPUnit\Framework\TestCase;

/**
 * @group factory
 * @coversDefaultClass League\Uri\Factory
 */
class FactoryTest extends TestCase
{
    /**
     * @covers ::createFromDataPath
     *
     * @dataProvider invalidDataPath
     *
     * @param string $path
     */
    public function testCreateFromPathFailed($path): void
    {
        self::expectException(InvalidUri::class);
        Factory::createFromDataPath($path);
    }

    public function invalidDataPath(): array
    {
        return [
            'invalid format' => ['/usr/bin/yeah'],
        ];
    }

    /**
     * @covers ::createFromDataPath
     *
     * @dataProvider validFilePath
     */
    public function testCreateFromPath(string $path, string $expected): void
    {
        $context = stream_context_create([
            'http'=> [
                'method' => 'GET',
                'header' => "Accept-language: en\r\nCookie: foo=bar\r\n",
            ],
        ]);

        $uri = Factory::createFromDataPath(__DIR__.'/data/'.$path, $context);
        self::assertContains($expected, $uri->getPath());
    }

    public function validFilePath(): array
    {
        return [
            'text file' => ['hello-world.txt', 'text/plain'],
            'img file' => ['red-nose.gif', 'image/gif'],
        ];
    }

    /**
     * @covers ::createFromUnixPath
     *
     * @dataProvider unixpathProvider
     */
    public function testCreateFromUnixPath(string $uri, string $expected): void
    {
        self::assertSame($expected, (string) Factory::createFromUnixPath($uri));
    }

    public function unixpathProvider(): array
    {
        return [
            'relative path' => [
                'input' => 'path',
                'expected' => 'path',
            ],
            'absolute path' => [
                'input' => '/path',
                'expected' => 'file:///path',
            ],
            'path with empty char' => [
                'input' => '/path empty/bar',
                'expected' => 'file:///path%20empty/bar',
            ],
            'relative path with dot segments' => [
                'input' => 'path/./relative',
                'expected' => 'path/./relative',
            ],
            'absolute path with dot segments' => [
                'input' => '/path/./../relative',
                'expected' => 'file:///path/./../relative',
            ],
        ];
    }

    /**
     * @covers ::createFromWindowsPath
     *
     * @dataProvider windowLocalPathProvider
     */
    public function testCreateFromWindowsLocalPath(string $uri, string $expected): void
    {
        self::assertSame($expected, (string) Factory::createFromWindowsPath($uri));
    }

    public function windowLocalPathProvider(): array
    {
        return [
            'relative path' => [
                'input' => 'path',
                'expected' => 'path',
            ],
            'relative path with dot segments' => [
                'input' => 'path\.\relative',
                'expected' => 'path/./relative',
            ],
            'absolute path' => [
                'input' => 'c:\windows\My Documents 100%20\foo.txt',
                'expected' => 'file:///c:/windows/My%20Documents%20100%2520/foo.txt',
            ],
            'windows relative path' => [
                'input' => 'c:My Documents 100%20\foo.txt',
                'expected' => 'file:///c:My%20Documents%20100%2520/foo.txt',
            ],
            'absolute path with `|`' => [
                'input' => 'c|\windows\My Documents 100%20\foo.txt',
                'expected' => 'file:///c:/windows/My%20Documents%20100%2520/foo.txt',
            ],
            'windows relative path with `|`' => [
                'input' => 'c:My Documents 100%20\foo.txt',
                'expected' => 'file:///c:My%20Documents%20100%2520/foo.txt',
            ],
            'absolute path with dot segments' => [
                'input' => '\path\.\..\relative',
                'expected' => '/path/./../relative',
            ],
            'absolute UNC path' => [
                'input' => '\\\\server\share\My Documents 100%20\foo.txt',
                'expected' => 'file://server/share/My%20Documents%20100%2520/foo.txt',
            ],
        ];
    }

    public function testCreateUri(): void
    {
        $expected = 'http://login:pass@secure.example.com:443/test/query.php?kingkong=toto#doc3';
        $psr7 = Http::createFromString($expected);

        $uri = Factory::createFromPsr7($psr7);
        self::assertSame((string) $psr7, (string) $uri);
    }

    /**
     * @covers ::createFromEnvironment
     * @covers ::fetchScheme
     * @covers ::fetchUserInfo
     * @covers ::fetchHostname
     * @covers ::fetchRequestUri
     *
     * @dataProvider validServerArray
     */
    public function testCreateFromServer(string $expected, array $input): void
    {
        self::assertSame($expected, (string) Factory::createFromEnvironment($input));
    }

    public function validServerArray(): array
    {
        return [
            'with host' => [
                'https://example.com:23',
                [
                    'PHP_SELF' => '',
                    'REQUEST_URI' => '',
                    'SERVER_ADDR' => '127.0.0.1',
                    'HTTPS' => 'on',
                    'SERVER_PORT' => '23',
                    'HTTP_HOST' => 'example.com',
                ],
            ],
            'server address IPv4' => [
                'https://127.0.0.1:23',
                [
                    'PHP_SELF' => '',
                    'REQUEST_URI' => '',
                    'SERVER_ADDR' => '127.0.0.1',
                    'HTTPS' => 'on',
                    'SERVER_PORT' => 23,
                ],
            ],
            'server address IPv6' => [
                'https://[::1]:23',
                [
                    'PHP_SELF' => '',
                    'REQUEST_URI' => '',
                    'SERVER_ADDR' => '::1',
                    'HTTPS' => 'on',
                    'SERVER_PORT' => 23,
                ],
            ],
            'with port attached to host' => [
                'https://localhost:23',
                [
                    'PHP_SELF' => '',
                    'REQUEST_URI' => '',
                    'SERVER_ADDR' => '127.0.0.1',
                    'HTTPS' => 'on',
                    'SERVER_PORT' => 80,
                    'HTTP_HOST' => 'localhost:23',
                ],
            ],
            'with standard apache HTTP server' => [
                'http://localhost:23',
                [
                    'PHP_SELF' => '',
                    'REQUEST_URI' => '',
                    'SERVER_ADDR' => '127.0.0.1',
                    'HTTPS' => '',
                    'SERVER_PORT' => 80,
                    'HTTP_HOST' => 'localhost:23',
                ],
            ],
            'with IIS HTTP server' => [
                'http://localhost:23',
                [
                    'PHP_SELF' => '',
                    'REQUEST_URI' => '',
                    'SERVER_ADDR' => '127.0.0.1',
                    'HTTPS' => 'off',
                    'SERVER_PORT' => 80,
                    'HTTP_HOST' => 'localhost:23',
                ],
            ],
            'with IIS Rewritting server' => [
                'http://localhost:23/foo/bar?foo=bar',
                [
                    'PHP_SELF' => '',
                    'IIS_WasUrlRewritten' => '1',
                    'UNENCODED_URL' => '/foo/bar?foo=bar',
                    'REQUEST_URI' => 'toto',
                    'SERVER_PORT' => 23,
                    'HTTP_HOST' => 'localhost',
                ],
            ],
            'with standard port setting' => [
                'https://localhost:23',
                [
                    'PHP_SELF' => '',
                    'REQUEST_URI' => '',
                    'SERVER_ADDR' => '127.0.0.1',
                    'HTTPS' => 'on',
                    'SERVER_PORT' => 23,
                    'HTTP_HOST' => 'localhost',
                ],
            ],
            'without port' => [
                'https://localhost',
                [
                    'PHP_SELF' => '',
                    'REQUEST_URI' => '',
                    'SERVER_ADDR' => '127.0.0.1',
                    'HTTPS' => 'on',
                    'HTTP_HOST' => 'localhost',
                ],
            ],
            'with user info' => [
                'https://foo:bar@localhost:23',
                [
                    'PHP_SELF' => '',
                    'REQUEST_URI' => '',
                    'SERVER_ADDR' => '127.0.0.1',
                    'PHP_AUTH_USER' => 'foo',
                    'PHP_AUTH_PW' => 'bar',
                    'HTTPS' => 'on',
                    'SERVER_PORT' => 23,
                    'HTTP_HOST' => 'localhost:23',
                ],
            ],
            'with user info and HTTP AUTHORIZATION' => [
                'https://foo:bar@localhost:23',
                [
                    'PHP_SELF' => '',
                    'REQUEST_URI' => '',
                    'SERVER_ADDR' => '127.0.0.1',
                    'HTTP_AUTHORIZATION' => 'basic '.base64_encode('foo:bar'),
                    'HTTPS' => 'on',
                    'SERVER_PORT' => 23,
                    'HTTP_HOST' => 'localhost:23',
                ],
            ],
            'without request uri' => [
                'https://127.0.0.1:23/toto?foo=bar',
                [
                    'PHP_SELF' => '/toto',
                    'SERVER_ADDR' => '127.0.0.1',
                    'HTTPS' => 'on',
                    'SERVER_PORT' => 23,
                    'QUERY_STRING' => 'foo=bar',
                ],
            ],
            'without request uri and server host' => [
                'https://127.0.0.1:23',
                [
                    'SERVER_ADDR' => '127.0.0.1',
                    'HTTPS' => 'on',
                    'SERVER_PORT' => 23,
                ],
            ],
        ];
    }

    /**
     * @covers ::fetchHostname
     */
    public function testFailCreateFromServerWithoutHost(): void
    {
        self::expectException(InvalidUri::class);
        Factory::createFromEnvironment([
            'PHP_SELF' => '',
            'REQUEST_URI' => '',
            'HTTPS' => 'on',
            'SERVER_PORT' => 23,
        ]);
    }

    /**
     * @covers ::fetchUserInfo
     */
    public function testFailCreateFromServerWithoutInvalidUserInfo(): void
    {
        self::expectException(InvalidUri::class);
        Factory::createFromEnvironment([
            'PHP_SELF' => '/toto',
            'SERVER_ADDR' => '127.0.0.1',
            'HTTPS' => 'on',
            'SERVER_PORT' => 23,
            'QUERY_STRING' => 'foo=bar',
            'HTTP_AUTHORIZATION' => 'basic foo:bar',
        ]);
    }
}
