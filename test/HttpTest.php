<?php

namespace LeagueTest\Uri\Schemes;

use InvalidArgumentException;
use League\Uri\Schemes\Http;

/**
 * @group http
 */
class HttpTest extends AbstractTestCase
{
    /**
     * @var Uri
     */
    private $uri;

    protected function setUp()
    {
        $this->uri = new Http(
            'http://login:pass@secure.example.com:443/test/query.php?kingkong=toto#doc3'
        );
    }

    protected function tearDown()
    {
        $this->uri = null;
    }

    public function testDefaultConstructor()
    {
        $this->assertSame('', (new Http())->__toString());
    }

    /**
     * @dataProvider validUriArray
     * @param $expected
     * @param $input
     */
    public function testCreateFromString($expected, $input)
    {
        $this->assertSame($expected, (string) (new Http($input)));
    }

    public function validUriArray()
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
     * @dataProvider isValidProvider
     * @expectedException InvalidArgumentException
     * @param $input
     */
    public function testIsValid($input)
    {
        new Http($input);
    }

    public function isValidProvider()
    {
        return [
            ['ftp:example.com'],
            ['wss:/example.com'],
        ];
    }

    public function testGetterAccess()
    {
        $this->assertSame('http', $this->uri->getScheme());
        $this->assertSame('login:pass', $this->uri->getUserInfo());
        $this->assertSame('secure.example.com', $this->uri->getHost());
        $this->assertSame(443, $this->uri->getPort());
        $this->assertSame('login:pass@secure.example.com:443', $this->uri->getAuthority());
        $this->assertSame('/test/query.php', $this->uri->getPath());
        $this->assertSame('kingkong=toto', $this->uri->getQuery());
        $this->assertSame('doc3', $this->uri->getFragment());
    }

    public function testKeepSameInstanceIfPropertyDoesNotChange()
    {
        $this->assertSame($this->uri, $this->uri->withScheme('http'));
        $this->assertSame($this->uri, $this->uri->withUserInfo('login', 'pass'));
        $this->assertSame($this->uri, $this->uri->withHost('secure.example.com'));
        $this->assertSame($this->uri, $this->uri->withPort(443));
        $this->assertSame($this->uri, $this->uri->withPath('/test/query.php'));
        $this->assertSame($this->uri, $this->uri->withQuery('kingkong=toto'));
        $this->assertSame($this->uri, $this->uri->withFragment('doc3'));
    }

    public function testCreateANewInstanceWhenPropertyChanges()
    {
        $this->assertNotEquals($this->uri, $this->uri->withScheme('https'));
        $this->assertNotEquals($this->uri, $this->uri->withUserInfo('login', null));
        $this->assertNotEquals($this->uri, $this->uri->withHost('shop.example.com'));
        $this->assertNotEquals($this->uri, $this->uri->withPort(81));
        $this->assertNotEquals($this->uri, $this->uri->withPath('/test/file.php'));
        $this->assertNotEquals($this->uri, $this->uri->withQuery('kingkong=tata'));
        $this->assertNotEquals($this->uri, $this->uri->withFragment('doc2'));
    }

    public function testAutomaticUrlNormalization()
    {
        $raw = 'HtTpS://MaStEr.eXaMpLe.CoM:443/%7ejohndoe/%a1/in+dex.php?foo.bar=value#fragment';
        $normalized = 'https://master.example.com/%7Ejohndoe/%A1/in+dex.php?foo.bar=value#fragment';
        $this->assertSame($normalized, (string) (new Http($raw)));
    }

    /**
     * @param $uri
     * @param $port
     * @dataProvider portProvider
     */
    public function testPort($uri, $port)
    {
        $this->assertSame($port, (new Http($uri))->getPort());
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
     * @expectedException InvalidArgumentException
     */
    public function testWithSchemeFailedScheme()
    {
        (new Http('http://example.com'))->withScheme('0scheme');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testWithSchemeFailedWithUnsupportedScheme()
    {
        Http::createFromString('http://example.com')->withScheme('telnet');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testWithPortFailedWithUnsupportedPort()
    {
        Http::createFromString('http://example.com')->withPort(-23);
    }

    /**
     * @dataProvider invalidURI
     * @expectedException InvalidArgumentException
     * @param $input
     */
    public function testCreateFromInvalidUrlKO($input)
    {
        Http::createFromString($input);
    }

    public function invalidURI()
    {
        return [
            ['http://user@:80'],
            ['//user@:80'],
            ['http:///path'],
            ['http:path'],
        ];
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testModificationFailedWithUnsupportedType()
    {
        Http::createFromString('http://example.com/path')->withQuery(null);
    }

    /**
     * @expectedException InvalidArgumentException
     * @dataProvider invalidPathProvider
     */
    public function testPathIsInvalid($path)
    {
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

    public function testEmptyValueDetection()
    {
        $expected = '//0:0@0/0?0#0';
        $this->assertSame($expected, Http::createFromString($expected)->__toString());
    }

    /**
     * @param $expected
     * @param $input
     * @dataProvider validServerArray
     */
    public function testCreateFromServer($expected, $input)
    {
        $this->assertSame($expected, Http::createFromServer($input)->__toString());
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
                    'SERVER_PORT' => 23,
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
     * @expectedException InvalidArgumentException
     */
    public function testFailCreateFromServerWithoutHost()
    {
        $server = [
            'PHP_SELF' => '',
            'REQUEST_URI' => '',
            'HTTPS' => 'on',
            'SERVER_PORT' => 23,
        ];

        Http::createFromServer($server);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testModificationFailedWithEmptyAuthority()
    {
        Http::createFromString('http://example.com/path')
            ->withScheme('')
            ->withHost('')
            ->withPath('//toto');
    }
}
