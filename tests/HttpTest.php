<?php

namespace LeagueTest\Uri;

use League\Uri\Http;
use League\Uri\UriException;
use PHPUnit\Framework\TestCase;

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

    protected function setUp()
    {
        $this->uri = Http::createFromString(
            'http://login:pass@secure.example.com:443/test/query.php?kingkong=toto#doc3'
        );
    }

    protected function tearDown()
    {
        unset($this->uri);
    }

    public function testDefaultConstructor()
    {
        $this->assertSame('', (string) Http::createFromString());
    }

    /**
     * @covers ::withPort
     * @covers ::filterPort
     */
    public function testModificationFailedWithUnsupportedPort()
    {
        $this->expectException(UriException::class);
        Http::createFromString('http://example.com/path')->withPort(12365894);
    }

    /**
     * @covers ::isValidUri
     * @covers ::filterPort
     * @dataProvider validUrlProvider
     *
     * @param string $expected
     * @param string $uri
     */
    public function testCreateFromString($expected, $uri)
    {
        $this->assertSame($expected, (string) Http::createFromString($uri));
    }

    public function validUrlProvider()
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
     * @covers ::isValidUri
     * @dataProvider invalidUrlProvider
     *
     * @param string $uri
     */
    public function testIsValid($uri)
    {
        $this->expectException(UriException::class);
        Http::createFromString($uri);
    }

    public function invalidUrlProvider()
    {
        return [
            ['wss://example.com'],
            ['http:example.com'],
            ['https:/example.com'],
            ['http://user@:80'],
            ['//user@:80'],
            ['http:///path'],
            ['http:path'],
        ];
    }

    /**
     * @dataProvider portProvider
     * @covers ::filterPort
     *
     * @param string   $uri
     * @param int|null $port
     */
    public function testPort($uri, $port)
    {
        $this->assertSame($port, Http::createFromString($uri)->getPort());
    }

    public function portProvider()
    {
        return [
            ['http://www.example.com:443/', 443],
            ['http://www.example.com:80/', null],
            ['http://www.example.com', null],
            ['//www.example.com:80/', 80],
        ];
    }

    /**
     * @covers ::isValidUri
     * @dataProvider invalidPathProvider
     *
     * @param string $path
     */
    public function testPathIsInvalid($path)
    {
        $this->expectException(UriException::class);
        Http::createFromString('')->withPath($path);
    }

    public function invalidPathProvider()
    {
        return [
            ['data:go'],
            ['//data'],
            ['to://to'],
        ];
    }

    /**
     * @covers ::assertValidState
     * @dataProvider invalidURI
     *
     * @param mixed $uri
     */
    public function testCreateFromInvalidUrlKO($uri)
    {
        $this->expectException(UriException::class);
        Http::createFromString($uri);
    }

    public function invalidURI()
    {
        return [
            ['http://user@:80'],
            ['//user@:80'],
        ];
    }

    /**
     * @covers ::createFromServer
     * @covers ::fetchScheme
     * @covers ::fetchUserInfo
     * @covers ::fetchHostname
     * @covers ::fetchRequestUri
     * @covers ::filterPort
     * @dataProvider validServerArray
     *
     * @param string $expected
     * @param array  $input
     */
    public function testCreateFromServer($expected, $input)
    {
        $this->assertSame($expected, (string) Http::createFromServer($input));
    }

    public function validServerArray()
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
    public function testFailCreateFromServerWithoutHost()
    {
        $this->expectException(UriException::class);
        Http::createFromServer([
            'PHP_SELF' => '',
            'REQUEST_URI' => '',
            'HTTPS' => 'on',
            'SERVER_PORT' => 23,
        ]);
    }

    /**
     * @covers ::isValidUri
     */
    public function testModificationFailedWithEmptyAuthority()
    {
        $this->expectException(UriException::class);
        Http::createFromString('http://example.com/path')
            ->withScheme('')
            ->withHost('')
            ->withPath('//toto');
    }
}
