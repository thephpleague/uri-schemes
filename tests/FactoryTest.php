<?php

namespace LeagueTest\UriSchemes;

use InvalidArgumentException;
use League\Uri;
use PHPUnit\Framework\TestCase;

/**
 * @group factory
 */
class FactoryTest extends TestCase
{
    /**
     * @dataProvider invalidMapperData
     *
     * @covers \League\Uri\Factory
     *
     * @param mixed $data
     */
    public function testFactoryThrowExceptionOnConstruction($data)
    {
        $this->expectException(InvalidArgumentException::class);
        new Uri\Factory($data);
    }

    public function invalidMapperData()
    {
        return [
            'invalid scheme' => [
                'data' => [
                    'tété' => Uri\Http::class,
                ],
            ],
            'invalid class' => [
                'data' => [
                    'telnet' => InvalidArgumentException::class,
                ],
            ],
        ];
    }

    public function testFactoryAddMapper()
    {
        $factory = new Uri\Factory(['http' => Uri\Uri::class]);
        $this->assertInstanceof(Uri\Uri::class, $factory->create('http://example.com'));
    }

    /**
     * @covers \League\Uri\create
     * @covers \League\Uri\Factory
     */
    public function testCreateThrowExceptionWithBaseUriNotAbsolute()
    {
        $this->expectException(InvalidArgumentException::class);
        Uri\create('/path/to/you', Uri\Http::createFromString('//example.com'));
    }

    /**
     * @dataProvider uriProvider
     *
     * @covers \League\Uri\create
     * @covers \League\Uri\Factory
     *
     * @param string $expected
     * @param string $uri
     */
    public function testCreate($expected, $uri)
    {
        $this->assertInstanceof($expected, Uri\create($uri));
    }

    public function uriProvider()
    {
        return [
            'http' => [
                'expected' => Uri\Http::class,
                'uri' => 'http://www.example.com',
            ],
            'https' => [
                'expected' => Uri\Http::class,
                'uri' => 'https://www.example.com',
            ],
            'ftp' => [
                'expected' => Uri\Ftp::class,
                'uri' => 'ftp://www.example.com',
            ],
            'generic' => [
                'expected' => Uri\Uri::class,
                'uri' => 'mailto:info@thephpleague.com',
            ],
        ];
    }

    /**
     * @dataProvider uriBaseUriProvider
     *
     * @covers \League\Uri\create
     * @covers \League\Uri\Factory
     *
     * @param string $expected_class
     * @param string $expected_uri
     * @param string $uri
     * @param mixed  $base_uri
     */
    public function testCreateWithBaseUri($expected_class, $expected_uri, $uri, $base_uri)
    {
        $obj = Uri\create($uri, $base_uri);
        $this->assertInstanceof($expected_class, $obj);
        $this->assertSame($expected_uri, (string) $obj);
    }

    public function uriBaseUriProvider()
    {
        $base_uri = Uri\Http::createFromString('https://example.com/index.php');

        return [
            'empty URI' => [
                'expected_class' => Uri\Http::class,
                'expected_uri' => 'https://example.com/index.php',
                'uri' => '',
                'base_uri' => $base_uri,
            ],
            'uri with absolute path' => [
                'expected_class' => Uri\Http::class,
                'expected_uri' => 'https://example.com/path/to/file',
                'uri' => '/path/to/file',
                'base_uri' => $base_uri,
            ],
            'uri with authority' => [
                'expected_class' => Uri\Http::class,
                'expected_uri' => 'https://toto.com/path/to/file',
                'uri' => '//toto.com/path/to/file',
                'base_uri' => $base_uri,
            ],
            'uri with relative path' => [
                'expected_class' => Uri\Http::class,
                'expected_uri' => 'https://example.com/path/here.png',
                'uri' => 'path/here.png',
                'base_uri' => $base_uri,
            ],
            'uri with query' => [
                'expected_class' => Uri\Http::class,
                'expected_uri' => 'https://example.com/index.php?foo=bar',
                'uri' => '?foo=bar',
                'base_uri' => $base_uri,
            ],
            'uri with another scheme' => [
                'expected_class' => Uri\Ftp::class,
                'expected_uri' => 'ftp://example.com/to/file.csv',
                'uri' => 'ftp://example.com/to/file.csv',
                'base_uri' => $base_uri,
            ],
            'uri with dot segments (1)' => [
                'expected_class' => Uri\Http::class,
                'expected_uri' => 'https://example.com/to/the/sky.php',
                'uri' => '/path/../to/the/./sky.php',
                'base_uri' => $base_uri,
            ],
            'uri with dot segments (2)' => [
                'expected_class' => Uri\Http::class,
                'expected_uri' => 'https://example.com/to/the/sky/',
                'uri' => '/path/../to/the/./sky/.',
                'base_uri' => $base_uri,
            ],
            'uri with dot segments (3)' => [
                'expected_class' => Uri\Http::class,
                'expected_uri' => 'http://h:b@a/y',
                'uri' => 'b/../y',
                'base_uri' => Uri\Http::createFromString('http://h:b@a'),
            ],
            'uri with dot segments (4)' => [
                'expected_class' => Uri\Http::class,
                'expected_uri' => 'http://a/b/c/g',
                'uri' => './g',
                'base_uri' => Uri\Http::createFromString('http://a/b/c/d;p?q'),
            ],
            'uri with a base URI as string' => [
                'expected_class' => Uri\Http::class,
                'expected_uri' => 'https://example.com/path/to/file',
                'uri' => 'https://example.com/path/to/file',
                'base_uri' => 'ftp://example.com/index.php',
            ],
            'uri with a base URI as league URI' => [
                'expected_class' => Uri\Http::class,
                'expected_uri' => 'https://example.com/path/to/file',
                'uri' => 'https://example.com/path/to/file',
                'base_uri' => Uri\Ftp::createFromString('ftp://example.com/index.php'),
            ],
        ];
    }
}
