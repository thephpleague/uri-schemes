<?php

/**
 * League.Uri (http://uri.thephpleague.com).
 *
 * @package    League\Uri
 * @subpackage League\Uri\Schemes
 * @author     Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @license    https://github.com/thephpleague/uri-schemes/blob/master/LICENSE (MIT License)
 * @version    1.2.1
 * @link       https://github.com/thephpleague/uri-schemes
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
     * @covers ::isValidUri
     * @dataProvider validUrlProvider
     *
     * @param string $uri
     * @param string $expected
     */
    public function testCreateFromString($uri, $expected)
    {
        self::assertSame($expected, (string) Ftp::createFromString($uri));
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
        self::expectException(UriException::class);
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
        self::expectException(UriException::class);
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
        self::assertSame($port, Ftp::createFromString($uri)->getPort());
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
