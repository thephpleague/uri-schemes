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

use League\Uri\Http;
use League\Uri\Relativizer;
use League\Uri\Uri;
use PHPUnit\Framework\TestCase;
use TypeError;

/**
 * @group uri
 * @group modifier
 * @group uri-modifier
 * @coversDefaultClass League\Uri\Info
 */
class RelativizerTest extends TestCase
{
    const BASE_URI = 'http://a/b/c/d;p?q';

    /**
     * @dataProvider relativizeProvider
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

    public function testRelativizerThrowExceptionOnConstructor(): void
    {
        self::expectException(TypeError::class);
        Relativizer::relativize('ftp//a/b/c/d;p', 'toto');
    }

    /**
     * @dataProvider relativizeAndResolveProvider
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
}
