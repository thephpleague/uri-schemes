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

use InvalidArgumentException;
use League\Uri\Exception\InvalidUri;
use League\Uri\Http;
use League\Uri\Uri;
use PHPUnit\Framework\TestCase;
use TypeError;

/**
 * @group http
 * @coversDefaultClass League\Uri\Http
 */
class HttpTest extends TestCase
{
    /**
     * @var Http
     */
    private $uri;

    protected function setUp(): void
    {
        $this->uri = Http::createFromString(
            'http://login:pass@secure.example.com:443/test/query.php?kingkong=toto#doc3'
        );
    }

    protected function tearDown(): void
    {
        unset($this->uri);
    }

    public function testDefaultConstructor(): void
    {
        self::assertSame('', (string) Http::createFromString());
    }

    /**
     * @covers ::jsonSerialize
     */
    public function testJson(): void
    {
        self::assertSame(
            '"http:\/\/example.com"',
            json_encode(Http::createFromString('http://example.com'))
        );
    }

    /**
     * @covers ::__debugInfo
     */
    public function testDebugInfo(): void
    {
        $uri = Uri::createFromString('http://example.com');
        self::assertSame($uri->__debugInfo(), Http::createFromString('http://example.com')->__debugInfo());
    }

    /**
     * @covers ::__construct
     */
    public function testInvalidPort(): void
    {
        self::expectException(InvalidArgumentException::class);
        Http::createFromString('https://example.com:0');
    }

    /**
     * @covers ::filterString
     */
    public function testThrowTypeErrorOnWrongType(): void
    {
        self::expectException(TypeError::class);
        Http::createFromString('https://example.com')->withFragment([]);
    }

    /**
     * @covers ::filterString
     */
    public function testThrowInvalidArgumentExceptionOnIllegalCharacters(): void
    {
        self::expectException(InvalidArgumentException::class);
        Http::createFromString('https://example.com')->withFragment("\0");
    }

    /**
     * @covers ::getPort
     * @covers ::withPort
     */
    public function testPortModification(): void
    {
        $uri = Http::createFromString('http://login:pass@secure.example.com:443/test/query.php?kingkong=toto#doc3');
        self::assertSame(443, $uri->getPort());
        self::assertSame($uri, $uri->withPort(443));
        self::assertNotEquals($uri, $uri->withPort(81));
        self::assertSame(
            'http://login:pass@secure.example.com/test/query.php?kingkong=toto#doc3',
            (string) $uri->withPort(null)
        );
    }

    /**
     * @covers ::getUserInfo
     * @covers ::withUserInfo
     */
    public function testUserInfoModification(): void
    {
        $uri = Http::createFromString('http://login:pass@secure.example.com:443/test/query.php?kingkong=toto#doc3');
        self::assertSame('login:pass', $uri->getUserInfo());
        self::assertSame($uri, $uri->withUserInfo('login', 'pass'));
        self::assertNotEquals($uri, $uri->withUserInfo('login', null));
        self::assertSame(
            'http://secure.example.com:443/test/query.php?kingkong=toto#doc3',
            (string) $uri->withUserInfo('')
        );
    }

    /**
     * @covers ::createFromComponents
     */
    public function testCreateFromComponents(): void
    {
        $uri = '//0:0@0/0?0#0';
        self::assertEquals(
            Http::createFromComponents(parse_url($uri)),
            Http::createFromString($uri)
        );
    }

    /**
     * @dataProvider setStateDataProvider
     *
     * @covers ::__set_state
     */
    public function testSetState(Http $uri): void
    {
        self::assertEquals($uri, eval('return '.var_export($uri, true).';'));
    }

    public function setStateDataProvider(): array
    {
        return [
            'all components' => [Http::createFromString('https://a:b@c:442/d?q=r#f')],
            'without scheme' => [Http::createFromString('//a:b@c:442/d?q=r#f')],
            'without userinfo' => [Http::createFromString('https://c:442/d?q=r#f')],
            'without port' => [Http::createFromString('https://a:b@c/d?q=r#f')],
            'without path' => [Http::createFromString('https://a:b@c:442?q=r#f')],
            'without query' => [Http::createFromString('https://a:b@c:442/d#f')],
            'without fragment' => [Http::createFromString('https://a:b@c:442/d?q=r')],
            'without pass' => [Http::createFromString('https://a@c:442/d?q=r#f')],
            'without authority' => [Http::createFromString('/d?q=r#f')],
       ];
    }

    /**
     * @covers \League\Uri\Uri::formatPort
     *
     * @dataProvider validUrlProvider
     */
    public function testCreateFromString(string $expected, string $uri): void
    {
        self::assertSame($expected, (string) Http::createFromString($uri));
    }

    public function validUrlProvider(): array
    {
        return [
            'with default port' => [
                'http://example.com/foo/bar?foo=bar#content',
                'http://example.com:80/foo/bar?foo=bar#content',
            ],
            'without scheme' => [
                '//example.com',
                '//example.com',
            ],
            'without scheme but with port' => [
                '//example.com:80',
                '//example.com:80',
            ],
            'with user info' => [
                'http://login:pass@example.com/',
                'http://login:pass@example.com/',
            ],
            'empty string' => [
                '',
                '',
            ],
        ];
    }

    /**
     * @dataProvider invalidUrlProvider
     */
    public function testIsValid(string $uri): void
    {
        self::expectException(InvalidUri::class);
        Http::createFromString($uri);
    }

    public function invalidUrlProvider(): array
    {
        return [
            //['wss://example.com'],
            ['http:example.com'],
            ['https:/example.com'],
            ['http://user@:80'],
            //['//user@:80'],
            ['http:///path'],
            ['http:path'],
        ];
    }

    /**
     * @dataProvider portProvider
     *
     * @covers \League\Uri\Uri::formatPort
     * @param ?int $port
     */
    public function testPort(string $uri, ?int $port): void
    {
        self::assertSame($port, Http::createFromString($uri)->getPort());
    }

    public function portProvider(): array
    {
        return [
            ['http://www.example.com:443/', 443],
            ['http://www.example.com:80/', null],
            ['http://www.example.com', null],
            ['//www.example.com:80/', 80],
        ];
    }

    /**
     * @dataProvider invalidPathProvider
     */
    public function testPathIsInvalid(string $path): void
    {
        self::expectException(InvalidUri::class);
        Http::createFromString('')->withPath($path);
    }

    public function invalidPathProvider(): array
    {
        return [
            ['data:go'],
            ['//data'],
            ['to://to'],
        ];
    }

    /**
     * @covers \League\Uri\Uri::assertValidState
     *
     * @dataProvider invalidURI
     */
    public function testCreateFromInvalidUrlKO(string $uri): void
    {
        self::expectException(InvalidUri::class);
        Http::createFromString($uri);
    }

    public function invalidURI(): array
    {
        return [
            ['http://user@:80'],
        ];
    }

    /**
     * @covers ::createFromServer
     * @covers \League\Uri\Uri::createFromServer
     * @covers \League\Uri\Uri::fetchScheme
     * @covers \League\Uri\Uri::fetchUserInfo
     * @covers \League\Uri\Uri::fetchHostname
     * @covers \League\Uri\Uri::fetchRequestUri
     * @covers \League\Uri\Uri::formatPort
     *
     * @dataProvider validServerArray
     */
    public function testCreateFromServer(string $expected, array $input): void
    {
        self::assertSame($expected, (string) Http::createFromServer($input));
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
     * @covers \League\Uri\Uri::fetchHostname
     */
    public function testFailCreateFromServerWithoutHost(): void
    {
        self::expectException(InvalidUri::class);
        Http::createFromServer([
            'PHP_SELF' => '',
            'REQUEST_URI' => '',
            'HTTPS' => 'on',
            'SERVER_PORT' => 23,
        ]);
    }

    /**
     * @covers \League\Uri\Uri::fetchUserInfo
     */
    public function testFailCreateFromServerWithoutInvalidUserInfo(): void
    {
        self::expectException(InvalidUri::class);
        Http::createFromServer([
            'PHP_SELF' => '/toto',
            'SERVER_ADDR' => '127.0.0.1',
            'HTTPS' => 'on',
            'SERVER_PORT' => 23,
            'QUERY_STRING' => 'foo=bar',
            'HTTP_AUTHORIZATION' => 'basic foo:bar',
        ]);
    }

    
    public function testModificationFailedWithEmptyAuthority(): void
    {
        self::expectException(InvalidUri::class);
        Http::createFromString('http://example.com/path')
            ->withScheme('')
            ->withHost('')
            ->withPath('//toto');
    }
}
