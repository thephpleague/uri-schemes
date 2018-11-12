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
use League\Uri\Ftp;
use League\Uri\Http;
use League\Uri\Info;
use League\Uri\Relativizer;
use League\Uri\Resolver;
use League\Uri\UriInterface;
use PHPUnit\Framework\TestCase;
use TypeError;

/**
 * @group uri
 * @group modifier
 * @group uri-modifier
 */
class FunctionsTest extends TestCase
{
    const BASE_URI = 'http://a/b/c/d;p?q';

    /**
     * @covers \League\Uri\Relativizer
     *
     * @dataProvider relativizeProvider
     *
     * @param string $uri
     * @param string $resolved
     * @param string $expected
     */
    public function testRelativize(string $uri, string $resolved, string $expected): void
    {
        $uri   = Http::createFromString($uri);
        $resolved = Http::createFromString($resolved);
        self::assertSame($expected, (string) Relativizer::relativize($resolved, $uri));
    }

    public function relativizeProvider(): array
    {
        return [
            'different scheme'        => [self::BASE_URI,       'https://a/b/c/d;p?q',   'https://a/b/c/d;p?q'],
            'different authority'     => [self::BASE_URI,       'https://g/b/c/d;p?q',   'https://g/b/c/d;p?q'],
            'empty uri'               => [self::BASE_URI,       '',                      ''],
            'same uri'                => [self::BASE_URI,       self::BASE_URI,          ''],
            'same path'               => [self::BASE_URI,       'http://a/b/c/d;p',      'd;p'],
            'parent path 1'           => [self::BASE_URI,       'http://a/b/c/',         './'],
            'parent path 2'           => [self::BASE_URI,       'http://a/b/',           '../'],
            'parent path 3'           => [self::BASE_URI,       'http://a/',             '../../'],
            'parent path 4'           => [self::BASE_URI,       'http://a',              '../../'],
            'sibling path 1'          => [self::BASE_URI,       'http://a/b/c/g',        'g'],
            'sibling path 2'          => [self::BASE_URI,       'http://a/b/c/g/h',      'g/h'],
            'sibling path 3'          => [self::BASE_URI,       'http://a/b/g',          '../g'],
            'sibling path 4'          => [self::BASE_URI,       'http://a/g',            '../../g'],
            'query'                   => [self::BASE_URI,       'http://a/b/c/d;p?y',    '?y'],
            'fragment'                => [self::BASE_URI,       'http://a/b/c/d;p?q#s',  '#s'],
            'path + query'            => [self::BASE_URI,       'http://a/b/c/g?y',      'g?y'],
            'path + fragment'         => [self::BASE_URI,       'http://a/b/c/g#s',      'g#s'],
            'path + query + fragment' => [self::BASE_URI,       'http://a/b/c/g?y#s',    'g?y#s'],
            'empty segments'          => [self::BASE_URI,       'http://a/b/c/foo////g', 'foo////g'],
            'empty segments 1'        => [self::BASE_URI,       'http://a/b////c/foo/g', '..////c/foo/g'],
            'relative single dot 1'   => [self::BASE_URI,       '.',                     '.'],
            'relative single dot 2'   => [self::BASE_URI,       './',                    './'],
            'relative double dot 1'   => [self::BASE_URI,       '..',                    '..'],
            'relative double dot 2'   => [self::BASE_URI,       '../',                   '../'],
            'path with colon 1'       => ['http://a/',          'http://a/d:p',          './d:p'],
            'path with colon 2'       => [self::BASE_URI,       'http://a/b/c/g/d:p',    'g/d:p'],
            'scheme + auth 1'         => ['http://a',           'http://a?q#s',          '?q#s'],
            'scheme + auth 2'         => ['http://a/',          'http://a?q#s',          '/?q#s'],
            '2 relative paths 1'      => ['a/b',                '../..',                 '../..'],
            '2 relative paths 2'      => ['a/b',                './.',                   './.'],
            '2 relative paths 3'      => ['a/b',                '../c',                  '../c'],
            '2 relative paths 4'      => ['a/b',                'c/..',                  'c/..'],
            '2 relative paths 5'      => ['a/b',                'c/.',                   'c/.'],
            'baseUri with query'      => ['/a/b/?q',            '/a/b/#h',               './#h'],
            'targetUri with fragment' => ['/',                  '/#h',                   '#h'],
            'same document'           => ['/',                  '/',                     ''],
            'same URI normalized'     => ['http://a',           'http://a/',             ''],
        ];
    }

    /**
     * @covers \League\Uri\Resolver
     */
    public function testResolveLetThrowResolvedInvalidUri(): void
    {
        self::expectException(InvalidArgumentException::class);
        $http = Http::createFromString('http://example.com/path/to/file');
        $ftp = Ftp::createFromString('ftp//a/b/c/d;p');
        Resolver::resolve($ftp, $http);
    }

    /**
     * @covers \League\Uri\Resolver
     */
    public function testResolveThrowExceptionOnConstructor(): void
    {
        self::expectException(TypeError::class);
        Resolver::resolve('ftp//a/b/c/d;p', 'toto');
    }

    /**
     * @covers \League\Uri\Relativizer
     */
    public function testRelativizerThrowExceptionOnConstructor(): void
    {
        self::expectException(TypeError::class);
        Relativizer::relativize('ftp//a/b/c/d;p', 'toto');
    }

    /**
     * @covers \League\Uri\Relativizer
     * @covers \League\Uri\Resolver
     * @covers \League\Uri\Factory
     *
     * @dataProvider relativizeAndResolveProvider
     *
     * @param string $baseUri
     * @param string $uri
     * @param string $expectedRelativize
     * @param string $expectedResolved
     */
    public function testRelativizeAndResolve(
        string $baseUri,
        string $uri,
        string $expectedRelativize,
        string $expectedResolved
    ): void {
        $baseUri = Http::createFromString($baseUri);
        $uri = Http::createFromString($uri);

        $relativeUri = Relativizer::relativize($uri, $baseUri);
        self::assertSame($expectedRelativize, (string) $relativeUri);
    }

    public function relativizeAndResolveProvider(): array
    {
        return [
            'empty path'            => [self::BASE_URI, 'http://a/', '../../',   'http://a/'],
            'absolute empty path'   => [self::BASE_URI, 'http://a',  '../../',   'http://a/'],
            'relative single dot 1' => [self::BASE_URI, '.',         '.',        'http://a/b/c/'],
            'relative single dot 2' => [self::BASE_URI, './',        './',       'http://a/b/c/'],
            'relative double dot 1' => [self::BASE_URI, '..',        '..',       'http://a/b/'],
            'relative double dot 2' => [self::BASE_URI, '../',       '../',      'http://a/b/'],
            '2 relative paths 1'    => ['a/b',          '../..',     '../..',    '/'],
            '2 relative paths 2'    => ['a/b',          './.',       './.',      'a/'],
            '2 relative paths 3'    => ['a/b',          '../c',      '../c',     'c'],
            '2 relative paths 4'    => ['a/b',          'c/..',      'c/..',     'a/'],
            '2 relative paths 5'    => ['a/b',          'c/.',       'c/.',      'a/c/'],
            'path with colon'       => ['http://a/',    'http://a/d:p', './d:p', 'http://a/d:p'],
        ];
    }

    /**
     * @dataProvider uriProvider
     *
     * @covers \League\Uri\Info
     *
     * @param mixed  $uri
     * @param mixed  $base_uri
     * @param bool[] $infos
     */
    public function testStat($uri, $base_uri, array $infos): void
    {
        if (null !== $base_uri) {
            self::assertSame($infos['same_document'], Info::isSameDocument($uri, $base_uri));
        }
        self::assertSame($infos['relative_path'], Info::isRelativePath($uri));
        self::assertSame($infos['absolute_path'], Info::isAbsolutePath($uri));
        self::assertSame($infos['absolute_uri'], Info::isAbsolute($uri));
        self::assertSame($infos['network_path'], Info::isNetworkPath($uri));
    }

    public function uriProvider(): array
    {
        return [
            'absolute uri' => [
                'uri' => Http::createFromString('http://a/p?q#f'),
                'base_uri' => null,
                'infos' => [
                    'absolute_uri' => true,
                    'network_path' => false,
                    'absolute_path' => false,
                    'relative_path' => false,
                    'same_document' => false,
                ],
            ],
            'network relative uri' => [
                'uri' => Http::createFromString('//스타벅스코리아.com/p?q#f'),
                'base_uri' => Http::createFromString('//xn--oy2b35ckwhba574atvuzkc.com/p?q#z'),
                'infos' => [
                    'absolute_uri' => false,
                    'network_path' => true,
                    'absolute_path' => false,
                    'relative_path' => false,
                    'same_document' => true,
                ],
            ],
            'path absolute uri' => [
                'uri' => Http::createFromString('/p?q#f'),
                'base_uri' => Http::createFromString('/p?a#f'),
                'infos' => [
                    'absolute_uri' => false,
                    'network_path' => false,
                    'absolute_path' => true,
                    'relative_path' => false,
                    'same_document' => false,
                ],
            ],
            'path relative uri with non empty path' => [
                'uri' => Http::createFromString('p?q#f'),
                'base_uri' => null,
                'infos' => [
                    'absolute_uri' => false,
                    'network_path' => false,
                    'absolute_path' => false,
                    'relative_path' => true,
                    'same_document' => false,
                ],
            ],
            'path relative uri with empty' => [
                'uri' => Http::createFromString('?q#f'),
                'base_uri' => null,
                'infos' => [
                    'absolute_uri' => false,
                    'network_path' => false,
                    'absolute_path' => false,
                    'relative_path' => true,
                    'same_document' => false,
                ],
            ],
        ];
    }

    /**
     * @dataProvider failedUriProvider
     *
     * @covers \League\Uri\Info
     * @param mixed $uri
     * @param mixed $base_uri
     */
    public function testStatThrowsInvalidArgumentException($uri, $base_uri): void
    {
        self::expectException(TypeError::class);
        Info::isSameDocument($uri, $base_uri);
    }

    public function failedUriProvider(): array
    {
        return [
            'invalid uri' => [
                'uri' => Http::createFromString('http://a/p?q#f'),
                'base_uri' => 'http://example.com',
            ],
            'invalid base uri' => [
                'uri' => 'http://example.com',
                'base_uri' => Http::createFromString('//a/p?q#f'),
            ],
        ];
    }

    /**
     * @dataProvider functionProvider
     *
     * @covers \League\Uri\Info
     *
     * @param string $function
     */
    public function testIsFunctionsThrowsTypeError(string $function): void
    {
        self::expectException(TypeError::class);
        Info::$function('http://example.com');
    }

    public function functionProvider(): array
    {
        return [
            ['isAbsolute'],
            ['isNetworkPath'],
            ['isAbsolutePath'],
            ['isRelativePath'],
        ];
    }

    /**
     * @covers \League\Uri\Info
     *
     * @dataProvider sameValueAsProvider
     *
     * @param UriInterface $uri1
     * @param UriInterface $uri2
     * @param bool         $expected
     */
    public function testSameValueAs($uri1, $uri2, bool $expected): void
    {
        self::assertSame($expected, Info::isSameDocument($uri1, $uri2));
    }

    public function sameValueAsProvider(): array
    {
        return [
            '2 disctincts URIs' => [
                Http::createFromString('http://example.com'),
                Ftp::createFromString('ftp://example.com'),
                false,
            ],
            '2 identical URIs' => [
                Http::createFromString('http://example.com'),
                Http::createFromString('http://example.com'),
                true,
            ],
            '2 identical URIs after removing dot segment' => [
                Http::createFromString('http://example.org/~foo/'),
                Http::createFromString('http://example.ORG/bar/./../~foo/'),
                true,
            ],
            '2 distincts relative URIs' => [
                Http::createFromString('~foo/'),
                Http::createFromString('../~foo/'),
                false,
            ],
            '2 identical relative URIs' => [
                Http::createFromString('../%7efoo/'),
                Http::createFromString('../~foo/'),
                true,
            ],
            '2 identical URIs after normalization (1)' => [
                Http::createFromString('HtTp://مثال.إختبار:80/%7efoo/%7efoo/'),
                Http::createFromString('http://xn--mgbh0fb.xn--kgbechtv/%7Efoo/~foo/'),
                true,
            ],
            '2 identical URIs after normalization (2)' => [
                Http::createFromString('http://www.example.com'),
                Http::createFromString('http://www.example.com/'),
                true,
            ],
            '2 identical URIs after normalization (3)' => [
                Http::createFromString('http://www.example.com'),
                Http::createFromString('http://www.example.com:/'),
                true,
            ],
            '2 identical URIs after normalization (4)' => [
                Http::createFromString('http://www.example.com'),
                Http::createFromString('http://www.example.com:80/'),
                true,
            ],
        ];
    }
}
