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
use League\Uri\Uri;
use PHPUnit\Framework\TestCase;

/**
 * @group file
 * @coversDefaultClass League\Uri\Uri
 */
class FileTest extends TestCase
{
    /**
     * @covers ::formatHost
     */
    public function testDefaultConstructor(): void
    {
        self::assertSame('', (string) Uri::createFromString());
    }

    /**

     * @covers ::formatHost
     * @covers ::formatFilePath
     * @covers ::assertValidState
     *
     * @dataProvider validUrlProvider
     */
    public function testCreateFromString(string $uri, string $expected): void
    {
        self::assertSame($expected, (string) Uri::createFromString($uri));
    }

    public function validUrlProvider(): array
    {
        return [
            'relative path' => [
                '.././path/../is/./relative',
                '.././path/../is/./relative',
            ],
            'absolute path' => [
                '/path/is/absolute',
                '/path/is/absolute',
            ],
            'empty path' => [
                '',
                '',
            ],
            'with host' => [
                '//example.com/path',
                '//example.com/path',
            ],
            'with normalized host' => [
                '//ExAmpLe.CoM/path',
                '//example.com/path',
            ],
            'with empty host' => [
                '///path',
                '///path',
            ],
            'with scheme' => [
                'file://localhost/path',
                'file://localhost/path',
            ],
            'with normalized scheme' => [
                'FiLe://localhost/path',
                'file://localhost/path',
            ],
            'with empty host and scheme' => [
                'FiLe:///path',
                'file:///path',
            ],
            'with windows path' => [
                'file:///C|/demo',
                'file:///C:/demo',
            ],
        ];
    }

    /**

     *
     * @dataProvider invalidUrlProvider
     * @covers ::assertValidState
     */
    public function testConstructorThrowsException(string $uri): void
    {
        self::expectException(InvalidUri::class);
        Uri::createFromString($uri);
    }

    public function invalidUrlProvider(): array
    {
        return [
            'no authority 1' => ['file:example.com'],
            'no authority 2' => ['file:/example.com'],
            'query string' => ['file://example.com?'],
            'fragment' => ['file://example.com#'],
            'user info' => ['file://user:pass@example.com'],
            'port' => ['file://example.com:42'],
        ];
    }

    /**
     * @covers ::createFromUnixPath
     * @covers ::assertValidState
     *
     * @dataProvider unixpathProvider
     */
    public function testCreateFromUnixPath(string $uri, string $expected): void
    {
        self::assertSame($expected, (string) Uri::createFromUnixPath($uri));
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
     * @covers ::assertValidState
     *
     * @dataProvider windowLocalPathProvider
     */
    public function testCreateFromWindowsLocalPath(string $uri, string $expected): void
    {
        self::assertSame($expected, (string) Uri::createFromWindowsPath($uri));
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
}
