<?php

namespace LeagueTest\Uri\Schemes;

use InvalidArgumentException;
use League\Uri\Schemes\File as FileUri;

/**
 * @group file
 */
class FileTest extends AbstractTestCase
{
    public function testDefaultConstructor()
    {
        $this->assertSame('', (new FileUri())->__toString());
    }

    /**
     * @dataProvider validUri
     * @param $expected
     * @param $input
     */
    public function testCreateFromString($input, $expected)
    {
        $this->assertSame($expected, (string) (new FileUri($input)));
    }

    public function validUri()
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
        ];
    }

    /**
     * @dataProvider invalidArgumentExceptionProvider
     * @expectedException InvalidArgumentException
     * @param $input
     */
    public function testConstructorThrowInvalidArgumentException($input)
    {
        new FileUri($input);
    }

    public function invalidArgumentExceptionProvider()
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
     * @dataProvider unixpathProvider
     * @param $input
     */
    public function testCreateFromUnixPath($input, $expected)
    {
        $this->assertSame($expected, (string) FileUri::createFromUnixPath($input));
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
     * @dataProvider windowLocalPathProvider
     * @param $input
     */
    public function testCreateFromWindowsLocalPath($input, $expected)
    {
        $this->assertSame($expected, (string) FileUri::createFromWindowsPath($input));
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
