<?php

namespace LeagueTest\Uri;

use League\Uri\Data;
use League\Uri\UriException;
use PHPUnit\Framework\TestCase;

/**
 * @group data
 * @coversDefaultClass League\Uri\Data
 */
class DataTest extends TestCase
{
    /**
     * @covers ::isValidUri
     * @covers ::filterPath
     */
    public function testDefaultConstructor()
    {
        $this->assertSame(
            'data:text/plain;charset=us-ascii,',
            (string) Data::createFromString('data:')
        );
    }

    /**
     * @covers ::isValidUri
     * @covers ::filterPath
     * @covers ::assertValidPath
     * @dataProvider validUrlProvider
     *
     * @param string $uri
     * @param string $path
     */
    public function testCreateFromString($uri, $path)
    {
        $this->assertSame($path, Data::createFromString($uri)->getPath());
    }

    public function validUrlProvider()
    {
        return [
            'simple string' => [
                'uri' => 'data:text/plain;charset=us-ascii,Bonjour%20le%20monde%21',
                'path' => 'text/plain;charset=us-ascii,Bonjour%20le%20monde%21',
            ],
            'string without mimetype' => [
                'uri' => 'data:,Bonjour%20le%20monde%21',
                'path' => 'text/plain;charset=us-ascii,Bonjour%20le%20monde%21',
            ],
            'string without parameters' => [
                'uri' => 'data:text/plain,Bonjour%20le%20monde%21',
                'path' => 'text/plain;charset=us-ascii,Bonjour%20le%20monde%21',
            ],
            'empty string' => [
                'uri' => 'data:,',
                'path' => 'text/plain;charset=us-ascii,',
            ],
            'binary data' => [
                'uri' => 'data:image/gif;charset=binary;base64,R0lGODlhIAAgAIABAP8AAP///yH+EUNyZWF0ZWQgd2l0aCBHSU1QACH5BAEKAAEALAAAAAAgACAAAAI5jI+py+0Po5y02ouzfqD7DwJUSHpjSZ4oqK7m5LJw/Ep0Hd1dG/OuvwKihCVianbbKJfMpvMJjWYKADs=',
                'path' => 'image/gif;charset=binary;base64,R0lGODlhIAAgAIABAP8AAP///yH+EUNyZWF0ZWQgd2l0aCBHSU1QACH5BAEKAAEALAAAAAAgACAAAAI5jI+py+0Po5y02ouzfqD7DwJUSHpjSZ4oqK7m5LJw/Ep0Hd1dG/OuvwKihCVianbbKJfMpvMJjWYKADs=',
            ],
        ];
    }

    /**
     * @covers ::assertValidPath
     * @covers ::isValidUri
     * @dataProvider invalidUrlProvider
     * @param string $uri
     */
    public function testCreateFromStringFailed($uri)
    {
        $this->expectException(UriException::class);
        Data::createFromString($uri);
    }

    public function invalidUrlProvider()
    {
        return [
            'invalid format' => ['foo:bar'],
            'invalid data' => ['data:image/png;base64,°28'],
            'invalid data 2' => ['data:image/png;base64,zzz28'],
            'invalid mime type' => ['data:image_png;base64,zzz'],
            'invalid parameter' => ['data:image/png;base64;base64,zzz'],
        ];
    }

    /**
     * @covers ::createFromPath
     * @dataProvider invalidDataPath
     * @param string $path
     */
    public function testCreateFromPathFailed($path)
    {
        $this->expectException(UriException::class);
        Data::createFromPath($path);
    }

    public function invalidDataPath()
    {
        return [
            'invalid format' => ['/usr/bin/yeah'],
        ];
    }

    /**
     * @covers ::assertValidPath
     * @covers ::filterPath
     */
    public function testCreateFromComponentsFailedWithInvalidArgumentException()
    {
        $this->expectException(UriException::class);
        Data::createFromString('data:image/png;base64,°28');
    }

    /**
     * @covers ::assertValidPath
     * @covers ::validateParameter
     * @covers ::filterPath
     */
    public function testCreateFromComponentsFailedInvalidMediatype()
    {
        $this->expectException(UriException::class);
        Data::createFromString('data:image/png;base64=toto;base64,dsqdfqfd');
    }

    /**
     * @covers ::isValidUri
     */
    public function testCreateFromComponentsFailedWithException()
    {
        $this->expectException(UriException::class);
        Data::createFromString('data:text/plain;charset=us-ascii,Bonjour%20le%20monde%21#fragment');
    }

    /**
     * @covers ::assertValidPath
     * @covers ::filterPath
     */
    public function testWithPath()
    {
        $path = 'text/plain;charset=us-ascii,Bonjour%20le%20monde%21';
        $uri = Data::createFromString('data:'.$path);
        $this->assertSame($uri, $uri->withPath($path));
    }

    /**
     * @covers ::createFromPath
     * @dataProvider validFilePath
     *
     * @param string $path
     * @param string $expected
     */
    public function testCreateFromPath($path, $expected)
    {
        $uri = Data::createFromPath(__DIR__.'/data/'.$path);
        $this->assertContains($expected, $uri->getPath());
    }

    public function validFilePath()
    {
        return [
            'text file' => ['hello-world.txt', 'text/plain'],
            'img file' => ['red-nose.gif', 'image/gif'],
        ];
    }

    /**
     * @covers ::isValidUri
     */
    public function testInvalidUri()
    {
        $this->expectException(UriException::class);
        Data::createFromString('http:text/plain;charset=us-ascii,Bonjour%20le%20monde%21');
    }
}
