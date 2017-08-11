<?php

namespace LeagueTest\Uri;

use League\Uri\Ftp;
use League\Uri\UriException;
use PHPUnit\Framework\TestCase;

/**
 * @group ftp
 * @coversDefaultClass League\Uri\Ftp
 */
class FtpTest extends TestCase
{
    /**
     * @covers ::getParser
     * @covers ::isValidUri
     * @dataProvider validUrlProvider
     *
     * @param string $uri
     * @param string $expected
     */
    public function testCreateFromString($uri, $expected)
    {
        $this->assertSame($expected, (string) Ftp::createFromString($uri));
    }

    public function validUrlProvider()
    {
        return [
            'with default port' => [
                'FtP://ExAmpLe.CoM:21/foo/bar',
                'ftp://example.com/foo/bar',
            ],
            'with user info' => [
                'ftp://login:pass@example.com/',
                'ftp://login:pass@example.com/',
            ],
            'with network path' => [
                '//ExAmpLe.CoM:80',
                '//example.com:80',
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
     *
     * @param string $uri
     */
    public function testConstructorThrowInvalidArgumentException($uri)
    {
        $this->expectException(UriException::class);
        Ftp::createFromString($uri);
    }

    public function invalidUrlProvider()
    {
        return [
            ['http://example.com'],
            ['ftp:/example.com'],
            ['ftp:example.com'],
            ['ftp://example.com?query#fragment'],
        ];
    }

    /**
     * @covers ::isValidUri
     */
    public function testModificationFailedWithEmptyAuthority()
    {
        $this->expectException(UriException::class);
        Ftp::createFromString('ftp://example.com/path')
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
        $this->assertSame($port, Ftp::createFromString($uri)->getPort());
    }

    public function portProvider()
    {
        return [
            ['ftp://www.example.com:443/', 443],
            ['ftp://www.example.com:21/', null],
            ['ftp://www.example.com', null],
            ['//www.example.com:21/', 21],
        ];
    }
}
