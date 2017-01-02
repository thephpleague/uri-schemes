<?php

namespace LeagueTest\Uri\Schemes;

use League\Uri\Schemes\Data;
use League\Uri\Schemes\UriException;
use PHPUnit\Framework\TestCase;

/**
 * @group data
 */
class DataTest extends TestCase
{
    public function testDefaultConstructor()
    {
        $this->assertSame(
            'data:text/plain;charset=us-ascii,',
            (string) Data::createFromString('data:')
        );
    }

    /**
     * @dataProvider validStringUri
     *
     * @param string $uri
     * @param string $path
     */
    public function testCreateFromString($uri, $path)
    {
        $this->assertSame($path, Data::createFromString($uri)->getPath());
    }

    public function validStringUri()
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
     * @dataProvider invalidDataString
     * @param $uri
     */
    public function testCreateFromStringFailed($uri)
    {
        $this->expectException(UriException::class);
        Data::createFromString($uri);
    }

    public function invalidDataString()
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
     * @dataProvider invalidDataPath
     * @param $path
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

    public function testCreateFromComponentsFailedWithInvalidArgumentException()
    {
        $this->expectException(UriException::class);
        Data::createFromString('data:image/png;base64,°28');
    }

    public function testCreateFromComponentsFailedInvalidMediatype()
    {
        $this->expectException(UriException::class);
        Data::createFromString('data:image/png;base64=toto;base64,dsqdfqfd');
    }

    public function testCreateFromComponentsFailedWithException()
    {
        $this->expectException(UriException::class);
        Data::createFromString('data:text/plain;charset=us-ascii,Bonjour%20le%20monde%21#fragment');
    }

    public function testWithPath()
    {
        $path = 'text/plain;charset=us-ascii,Bonjour%20le%20monde%21';
        $uri = Data::createFromString('data:'.$path);
        $this->assertSame($uri, $uri->withPath($path));
    }

    /**
     * @dataProvider validFilePath
     * @param $path
     * @param $expected
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

    public function testInvalidUri()
    {
        $this->expectException(UriException::class);
        Data::createFromString('http:text/plain;charset=us-ascii,Bonjour%20le%20monde%21');
    }
}
