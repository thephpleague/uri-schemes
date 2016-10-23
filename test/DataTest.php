<?php

namespace LeagueTest\Uri\Schemes;

use InvalidArgumentException;
use League\Uri\Schemes\Data;
use RuntimeException;

/**
 * @group data
 */
class DataTest extends AbstractTestCase
{
    public function testDefaultConstructor()
    {
        $this->assertSame('data:text/plain;charset=us-ascii,', (new Data('data:'))->__toString());
    }

    /**
     * @dataProvider validStringUri
     *
     * @param string $uri
     * @param string $path
     */
    public function testCreateFromString($uri, $path)
    {
        $this->assertSame($path, (new Data($uri))->getPath());
    }

    public function validStringUri()
    {
        return [
            'simple string' => [
                'uri' => 'data:text/plain;charset=us-ascii,Bonjour%20le%20monde%21',
                'path' => 'text/plain;charset=us-ascii,Bonjour%20le%20monde!',
            ],
            'string without mimetype' => [
                'uri' => 'data:,Bonjour%20le%20monde%21',
                'path' => 'text/plain;charset=us-ascii,Bonjour%20le%20monde!',
            ],
            'string without parameters' => [
                'uri' => 'data:text/plain,Bonjour%20le%20monde%21',
                'path' => 'text/plain;charset=us-ascii,Bonjour%20le%20monde!',
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
     * @expectedException InvalidArgumentException
     * @param $str
     */
    public function testCreateFromStringFailed($str)
    {
        new Data($str);
    }

    public function invalidDataString()
    {
        return [
            'boolean' => [true],
            'integer' => [23],
            'invalid format' => ['foo:bar'],
            'invalid data' => ['data:image/png;base64,°28'],
            'invalid data 2' => ['data:image/png;base64,zzz28'],
            'invalid mime type' => ['data:image_png;base64,zzz'],
            'invalid parameter' => ['data:image/png;base64;base64,zzz'],
        ];
    }

    /**
     * @dataProvider invalidDataPath
     * @expectedException RuntimeException
     * @param $path
     */
    public function testCreateFromPathFailed($path)
    {
        Data::createFromPath($path);
    }

    public function invalidDataPath()
    {
        return [
            'boolean' => [true],
            'integer' => [23],
            'invalid format' => ['/usr/bin/yeah'],
        ];
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testCreateFromComponentsFailedWithInvalidArgumentException()
    {
        new Data('data:image/png;base64,°28');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testCreateFromComponentsFailedInvalidMediatype()
    {
        new Data('data:image/png;base64,dsqdfqfd#fragment');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testCreateFromComponentsFailedWithRuntimeException()
    {
        new Data('data:text/plain;charset=us-ascii,Bonjour%20le%20monde%21#fragment');
    }

    public function testWithPath()
    {
        $path = 'text/plain;charset=us-ascii,Bonjour%20le%20monde%21';
        $uri = new Data('data:'.$path);
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

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidUri()
    {
        new Data('http:text/plain;charset=us-ascii,Bonjour%20le%20monde%21');
    }

    public function testSetState()
    {
        $uri = Data::createFromPath(__DIR__.'/data/red-nose.gif');
        $generateUri = eval('return '.var_export($uri, true).';');
        $this->assertEquals($uri, $generateUri);
    }
}
