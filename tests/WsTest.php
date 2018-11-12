<?php

/**
 * League.Uri (http://uri.thephpleague.com).
 *
 * @package    League\Uri
 * @subpackage League\Uri\Schemes
 * @author     Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @license    https://github.com/thephpleague/uri-components/blob/master/LICENSE (MIT License)
 * @version    2.0.0
 * @link       https://github.com/thephpleague/uri-schemes
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LeagueTest\Uri;

use League\Uri\Exception\InvalidUri;
use League\Uri\Exception\MalformedUri;
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
     *
     * @dataProvider validUrlProvider
     */
    public function testCreateFromString(string $input, string $expected): void
    {
        self::assertSame($expected, (string) Ws::createFromString($input));
    }

    /**
     * @covers ::withPort
     * @covers ::formatPort
     */
    public function testModificationFailedWithUnsupportedPort(): void
    {
        self::expectException(MalformedUri::class);
        Ws::createFromString('wss://example.com/path')->withPort(12365894);
    }

    public function validUrlProvider(): array
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
     */
    public function testConstructorThrowInvalidArgumentException(string $uri): void
    {
        self::expectException(InvalidUri::class);
        Ws::createFromString($uri);
    }

    public function invalidUrlProvider(): array
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
    public function testModificationFailedWithEmptyAuthority(): void
    {
        self::expectException(InvalidUri::class);
        Ws::createFromString('wss://example.com/path')
            ->withScheme('')
            ->withHost('')
            ->withPath('//toto');
    }

    /**
     * @dataProvider portProvider
     * @param ?int $port
     */
    public function testPort(string $uri, ?int $port): void
    {
        self::assertSame($port, Ws::createFromString($uri)->getPort());
    }

    public function portProvider(): array
    {
        return [
            ['ws://www.example.com:443/', 443],
            ['ws://www.example.com:80/', null],
            ['ws://www.example.com', null],
            ['//www.example.com:80/', 80],
        ];
    }
}
