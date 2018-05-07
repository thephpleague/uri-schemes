<?php

/**
 * League.Uri (http://uri.thephpleague.com)
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

use League\Uri\File;
use League\Uri\UriException;
use PHPUnit\Framework\TestCase;

/**
 * @group file
 * @coversDefaultClass League\Uri\File
 */
class FileTest extends TestCase
{
    /**
     * @covers ::formatHost
     */
    public function testDefaultConstructor()
    {
        $this->assertSame('', (string) File::createFromString());
    }

    /**
     * @covers ::isValidUri
     * @covers ::formatHost
     * @dataProvider validUrlProvider
     *
     * @param string $uri
     * @param string $expected
     */
    public function testCreateFromString($uri, $expected)
    {
        $this->assertSame($expected, (string) File::createFromString($uri));
    }

    public function validUrlProvider()
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
                '//localhost/path',
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
                'file://localhost/path',
            ],
        ];
    }

    /**
     * @covers ::isValidUri
     * @dataProvider invalidUrlProvider
     *
     * @param string $uri
     */
    public function testConstructorThrowsException($uri)
    {
        $this->expectException(UriException::class);
        File::createFromString($uri);
    }

    public function invalidUrlProvider()
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
     * @dataProvider unixpathProvider
     *
     * @param string $uri
     * @param string $expected
     */
    public function testCreateFromUnixPath($uri, $expected)
    {
        $this->assertSame($expected, (string) File::createFromUnixPath($uri));
    }

    public function unixpathProvider()
    {
        return [
            'relative path' => [
                'input' => 'path',
                'expected' => 'path',
            ],
            'absolute path' => [
                'input' => '/path',
                'expected' => 'file://localhost/path',
            ],
            'path with empty char' => [
                'input' => '/path empty/bar',
                'expected' => 'file://localhost/path%20empty/bar',
            ],
            'relative path with dot segments' => [
                'input' => 'path/./relative',
                'expected' => 'path/./relative',
            ],
            'absolute path with dot segments' => [
                'input' => '/path/./../relative',
                'expected' => 'file://localhost/path/./../relative',
            ],
        ];
    }

    /**
     * @covers ::createFromWindowsPath
     * @dataProvider windowLocalPathProvider
     *
     * @param string $uri
     * @param string $expected
     */
    public function testCreateFromWindowsLocalPath($uri, $expected)
    {
        $this->assertSame($expected, (string) File::createFromWindowsPath($uri));
    }

    public function windowLocalPathProvider()
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
                'expected' => 'file://localhost/c:/windows/My%20Documents%20100%2520/foo.txt',
            ],
            'windows relative path' => [
                'input' => 'c:My Documents 100%20\foo.txt',
                'expected' => 'file://localhost/c:My%20Documents%20100%2520/foo.txt',
            ],
            'absolute path with `|`' => [
                'input' => 'c|\windows\My Documents 100%20\foo.txt',
                'expected' => 'file://localhost/c:/windows/My%20Documents%20100%2520/foo.txt',
            ],
            'windows relative path with `|`' => [
                'input' => 'c:My Documents 100%20\foo.txt',
                'expected' => 'file://localhost/c:My%20Documents%20100%2520/foo.txt',
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
