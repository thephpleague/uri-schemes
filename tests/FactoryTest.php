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

use InvalidArgumentException;
use League\Uri;
use League\Uri\Exception\CreatingUriFailed;
use League\Uri\Exception\MappingUriFailed;
use League\Uri\Factory;
use League\Uri\Ftp;
use League\Uri\Http;
use PHPUnit\Framework\TestCase;
use TypeError;

/**
 * @group factory
 */
class FactoryTest extends TestCase
{
    /**
     * @covers \League\Uri\Factory
     */
    public function testFactoryThrowTypeErrorOnConstruction()
    {
        $this->expectException(TypeError::class);
        new Factory(date_create());
    }

    /**
     * @dataProvider invalidMapperData
     *
     * @covers \League\Uri\Factory
     *
     * @param mixed $data
     */
    public function testFactoryThrowExceptionOnConstruction($data)
    {
        $this->expectException(MappingUriFailed::class);
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
                    'telnet' => MappingUriFailed::class,
                ],
            ],
        ];
    }

    public function testFactoryAddMapper()
    {
        $factory = new Uri\Factory(['http' => Uri\Uri::class]);
        $this->assertInstanceOf(Uri\Uri::class, $factory->create('http://example.com'));
    }

    /**
     * @covers \League\Uri\create
     * @covers \League\Uri\Factory
     */
    public function testCreateThrowExceptionWithBaseUriNotAbsolute()
    {
        $this->expectException(CreatingUriFailed::class);
        Uri\create('/path/to/you', Uri\Http::createFromString('//example.com'));
    }

    /**
     * @covers \League\Uri\create
     * @covers \League\Uri\Factory
     */
    public function testCreateThrowExceptionWithUriNotAbsolute()
    {
        $this->expectException(CreatingUriFailed::class);
        Uri\create('/path/to/you');
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
        $this->assertInstanceOf($expected, Uri\create($uri));
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
     * @covers \League\Uri\Resolver
     *
     * @param string $expected_class
     * @param string $expected_uri
     * @param string $uri
     * @param mixed  $base_uri
     */
    public function testCreateWithBaseUri($expected_class, $expected_uri, $uri, $base_uri)
    {
        $obj = Uri\create($uri, $base_uri);
        $this->assertInstanceOf($expected_class, $obj);
        $this->assertSame($expected_uri, (string) $obj);
    }

    public function uriBaseUriProvider()
    {
        $base_uri = 'https://example.com/index.php';

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

    /**
     * @covers \League\Uri\create
     * @covers \League\Uri\Factory
     *
     * @dataProvider resolveProvider
     *
     * @param string $base_uri
     * @param string $uri
     * @param string $expected
     */
    public function testCreateResolve(string $base_uri, string $uri, string $expected)
    {
        $this->assertSame($expected, (string) Uri\create($uri, $base_uri));
    }

    public function resolveProvider()
    {
        $base_uri = 'http://a/b/c/d;p?q';

        return [
            'base uri'                => [$base_uri, '',              $base_uri],
            'scheme'                  => [$base_uri, 'http://d/e/f',  'http://d/e/f'],
            'path 1'                  => [$base_uri, 'g',             'http://a/b/c/g'],
            'path 2'                  => [$base_uri, './g',           'http://a/b/c/g'],
            'path 3'                  => [$base_uri, 'g/',            'http://a/b/c/g/'],
            'path 4'                  => [$base_uri, '/g',            'http://a/g'],
            'authority'               => [$base_uri, '//g',           'http://g'],
            'query'                   => [$base_uri, '?y',            'http://a/b/c/d;p?y'],
            'path + query'            => [$base_uri, 'g?y',           'http://a/b/c/g?y'],
            'fragment'                => [$base_uri, '#s',            'http://a/b/c/d;p?q#s'],
            'path + fragment'         => [$base_uri, 'g#s',           'http://a/b/c/g#s'],
            'path + query + fragment' => [$base_uri, 'g?y#s',         'http://a/b/c/g?y#s'],
            'single dot 1'            => [$base_uri, '.',             'http://a/b/c/'],
            'single dot 2'            => [$base_uri, './',            'http://a/b/c/'],
            'single dot 3'            => [$base_uri, './g/.',         'http://a/b/c/g/'],
            'single dot 4'            => [$base_uri, 'g/./h',         'http://a/b/c/g/h'],
            'double dot 1'            => [$base_uri, '..',            'http://a/b/'],
            'double dot 2'            => [$base_uri, '../',           'http://a/b/'],
            'double dot 3'            => [$base_uri, '../g',          'http://a/b/g'],
            'double dot 4'            => [$base_uri, '../..',         'http://a/'],
            'double dot 5'            => [$base_uri, '../../',        'http://a/'],
            'double dot 6'            => [$base_uri, '../../g',       'http://a/g'],
            'double dot 7'            => [$base_uri, '../../../g',    'http://a/g'],
            'double dot 8'            => [$base_uri, '../../../../g', 'http://a/g'],
            'double dot 9'            => [$base_uri, 'g/../h' ,       'http://a/b/c/h'],
            'mulitple slashes'        => [$base_uri, 'foo////g',      'http://a/b/c/foo////g'],
            'complex path 1'          => [$base_uri, ';x',            'http://a/b/c/;x'],
            'complex path 2'          => [$base_uri, 'g;x',           'http://a/b/c/g;x'],
            'complex path 3'          => [$base_uri, 'g;x?y#s',       'http://a/b/c/g;x?y#s'],
            'complex path 4'          => [$base_uri, 'g;x=1/./y',     'http://a/b/c/g;x=1/y'],
            'complex path 5'          => [$base_uri, 'g;x=1/../y',    'http://a/b/c/y'],
            'dot segments presence 1' => [$base_uri, '/./g',          'http://a/g'],
            'dot segments presence 2' => [$base_uri, '/../g',         'http://a/g'],
            'dot segments presence 3' => [$base_uri, 'g.',            'http://a/b/c/g.'],
            'dot segments presence 4' => [$base_uri, '.g',            'http://a/b/c/.g'],
            'dot segments presence 5' => [$base_uri, 'g..',           'http://a/b/c/g..'],
            'dot segments presence 6' => [$base_uri, '..g',           'http://a/b/c/..g'],
            'origin uri without path' => ['http://h:b@a', 'b/../y',   'http://h:b@a/y'],
            'uri without auhtority'   => ['mailto:f@a.b', 'b@c.d?subject=baz', 'mailto:b@c.d?subject=baz'],
        ];
    }

    /**
     * @covers \League\Uri\resolve
     */
    public function testResolveLetThrowResolvedInvalidUri()
    {
        $this->expectException(InvalidArgumentException::class);
        $http = Http::createFromString('http://example.com/path/to/file');
        $ftp = Ftp::createFromString('ftp//a/b/c/d;p');
        Uri\resolve($ftp, $http);
    }

    /**
     * @covers \League\Uri\resolve
     * @covers \League\Uri\Resolver::filterUri
     */
    public function testResolveThrowExceptionOnConstructor()
    {
        $this->expectException(TypeError::class);
        Uri\resolve('ftp//a/b/c/d;p', 'toto');
    }

    public function testCreateAlwaysResolveUri()
    {
        $this->assertSame(
            (string) Uri\create('../cats', 'http://www.example.com/dogs'),
            (string) Uri\create('http://www.example.com/dogs/../cats')
        );
    }
}
