<?php

namespace LeagueTest\Uri\Schemes;

use League\Uri\Schemes\Ftp;
use League\Uri\Schemes\UriException;
use PHPUnit\Framework\TestCase;

/**
 * @group ftp
 */
class FtpTest extends TestCase
{
    /**
     * @dataProvider validArray
     *
     * @param $uri
     * @param $expected
     */
    public function testCreateFromString($uri, $expected)
    {
        $this->assertSame($expected, (string) Ftp::createFromString($uri));
    }

    public function validArray()
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
     * @dataProvider invalidArgumentExceptionProvider
     */
    public function testConstructorThrowInvalidArgumentException($uri)
    {
        $this->expectException(UriException::class);
        Ftp::createFromString($uri);
    }

    public function invalidArgumentExceptionProvider()
    {
        return [
            ['http://example.com'],
            ['ftp:/example.com'],
            ['ftp:example.com'],
            ['ftp://example.com?query#fragment'],
        ];
    }

    public function testModificationFailedWithEmptyAuthority()
    {
        $this->expectException(UriException::class);
        Ftp::createFromString('ftp://example.com/path')
            ->withScheme('')
            ->withHost('')
            ->withPath('//toto');
    }
}
