<?php

namespace LeagueTest\Uri;

use League\Uri\UriException;
use League\Uri\Ws;
use PHPUnit\Framework\TestCase;

/**
 * @group ws
 * @coversDefaultClass League\Uri\Ws
 */
class WsTest extends TestCase
{
    /**
     * @covers ::isValidUri
     * @dataProvider validUrlProvider
     *
     * @param string $expected
     * @param string $input
     */
    public function testCreateFromString($input, $expected)
    {
        $this->assertSame($expected, (string) Ws::createFromString($input));
    }

    /**
     * @covers ::withPort
     * @covers ::filterPort
     */
    public function testModificationFailedWithUnsupportedPort()
    {
        $this->expectException(UriException::class);
        Ws::createFromString('wss://example.com/path')->withPort(12365894);
    }

    public function validUrlProvider()
    {
        return [
            'with default port' => [
                'Ws://ExAmpLe.CoM:80/foo/bar?foo=bar',
                'ws://example.com/foo/bar?foo=bar',
            ],
            'with user info' => [
                'wss://login:pass@example.com/',
                'wss://login:pass@example.com/',
            ],
            'network path' => [
                '//ExAmpLe.CoM:21',
                '//example.com:21',
            ],
            'absolute path' => [
                '/path/to/my/file',
                '/path/to/my/file',
            ],
            'relative path' => [
                '.././path/../is/./relative',
                '.././path/../is/./relative',
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
     * @param string $uri
     */
    public function testConstructorThrowInvalidArgumentException($uri)
    {
        $this->expectException(UriException::class);
        Ws::createFromString($uri);
    }

    public function invalidUrlProvider()
    {
        return [
            ['http://example.com'],
            ['wss:example.com'],
            ['wss:/example.com'],
            ['//example.com:80/foo/bar?foo=bar#content'],
        ];
    }

    /**
     * @covers ::isValidUri
     */
    public function testModificationFailedWithEmptyAuthority()
    {
        $this->expectException(UriException::class);
        Ws::createFromString('wss://example.com/path')
            ->withScheme('')
            ->withHost('')
            ->withPath('//toto');
    }

    /**
     * @dataProvider portProvider
     *
     * @param string   $uri
     * @param int|null $port
     */
    public function testPort($uri, $port)
    {
        $this->assertSame($port, Ws::createFromString($uri)->getPort());
    }

    public function portProvider()
    {
        return [
            ['ws://www.example.com:443/', 443],
            ['ws://www.example.com:80/', null],
            ['ws://www.example.com', null],
            ['//www.example.com:80/', 80],
        ];
    }
}
