<?php

namespace LeagueTest\Uri\Schemes;

use League\Uri\Schemes\Exceptions\Exception;
use League\Uri\Schemes\Ws as WsUri;

/**
 * @group ws
 */
class WsTest extends AbstractTestCase
{
    public function testDefaultConstructor()
    {
        $this->assertSame('', WsUri::createFromString()->__toString());
    }

    /**
     * @dataProvider validUrlArray
     * @param $expected
     * @param $input
     */
    public function testCreateFromString($input, $expected)
    {
        $this->assertSame($expected, WsUri::createFromString($input)->__toString());
    }

    public function validUrlArray()
    {
        return [
            'with default port' => [
                'Ws://ExAmpLe.CoM:80/foo/bar?foo=bar',
                'ws://example.com/foo/bar?foo=bar',
            ],
            'with user info' => [
                'wss://login:pass@example.com/',
                'wss://login:pass@example.com/',
            ],
            'network path' => [
                '//ExAmpLe.CoM:21',
                '//example.com:21',
            ],
            'absolute path' => [
                '/path/to/my/file',
                '/path/to/my/file',
            ],
            'relative path' => [
                '.././path/../is/./relative',
                '.././path/../is/./relative',
            ],
            'empty string' => [
                '',
                '',
            ],
        ];
    }

    /**
     * @dataProvider invalidArgumentExceptionProvider
     */
    public function testConstructorThrowInvalidArgumentException($uri)
    {
        $this->expectException(Exception::class);
        WsUri::createFromString($uri);
    }

    public function invalidArgumentExceptionProvider()
    {
        return [
            ['http://example.com'],
            ['wss:example.com'],
            ['wss:/example.com'],
            ['//example.com:80/foo/bar?foo=bar#content'],
        ];
    }

    public function testModificationFailedWithEmptyAuthority()
    {
        $this->expectException(Exception::class);
        WsUri::createFromString('wss://example.com/path')
            ->withScheme('')
            ->withHost('')
            ->withPath('//toto');
    }
}
