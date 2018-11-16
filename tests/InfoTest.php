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

use League\Uri\Ftp;
use League\Uri\Http;
use League\Uri\Info;
use League\Uri\Uri;
use League\Uri\UriInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface as Psr7UriInterface;
use TypeError;

/**
 * @group uri
 * @group modifier
 * @group uri-modifier
 * @coversDefaultClass League\Uri\Info
 */
class InfoTest extends TestCase
{
    /**
     * @dataProvider uriProvider
     *
     * @param Psr7UriInterface|UriInterface      $uri
     * @param null|Psr7UriInterface|UriInterface $base_uri
     * @param bool[]                             $infos
     */
    public function testInfo($uri, $base_uri, array $infos): void
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
     * @param null|mixed $uri
     * @param null|mixed $base_uri
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
     * @dataProvider sameValueAsProvider
     *
     * @param Psr7UriInterface|UriInterface $uri1
     * @param Psr7UriInterface|UriInterface $uri2
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
