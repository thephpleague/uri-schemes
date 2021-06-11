<?php

/**
 * League.Uri (http://uri.thephpleague.com)
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
        self::assertSame($expected, (string) Ws::createFromString($input));
    }

    /**
     * @covers ::withPort
     * @covers ::filterPort
     */
    public function testModificationFailedWithUnsupportedPort()
    {
        self::expectException(UriException::class);
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
        self::expectException(UriException::class);
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
        self::expectException(UriException::class);
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
        self::assertSame($port, Ws::createFromString($uri)->getPort());
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
