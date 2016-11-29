<?php

namespace LeagueTest\Uri\Schemes;

use League\Uri\ParserException;
use League\Uri\Schemes\Exceptions\Exception;
use League\Uri\Schemes\Http;

/**
 * @group uri
 */
class UriTest extends AbstractTestCase
{
    /**
     * @var Http
     */
    private $uri;

    protected function setUp()
    {
        $this->uri = Http::createFromString(
            'http://login:pass@secure.example.com:443/test/query.php?kingkong=toto#doc3'
        );
    }

    protected function tearDown()
    {
        $this->uri = null;
    }

    public function testGetterAccess()
    {
        $this->assertSame('http', $this->uri->getScheme());
        $this->assertSame('login:pass', $this->uri->getUserInfo());
        $this->assertSame('secure.example.com', $this->uri->getHost());
        $this->assertSame(443, $this->uri->getPort());
        $this->assertSame('login:pass@secure.example.com:443', $this->uri->getAuthority());
        $this->assertSame('/test/query.php', $this->uri->getPath());
        $this->assertSame('kingkong=toto', $this->uri->getQuery());
        $this->assertSame('doc3', $this->uri->getFragment());
    }

    public function testKeepSameInstanceIfPropertyDoesNotChange()
    {
        $this->assertSame($this->uri, $this->uri->withScheme('http'));
        $this->assertSame($this->uri, $this->uri->withUserInfo('login', 'pass'));
        $this->assertSame($this->uri, $this->uri->withHost('secure.example.com'));
        $this->assertSame($this->uri, $this->uri->withPort(443));
        $this->assertSame($this->uri, $this->uri->withPath('/test/query.php'));
        $this->assertSame($this->uri, $this->uri->withQuery('kingkong=toto'));
        $this->assertSame($this->uri, $this->uri->withFragment('doc3'));
    }

    public function testCreateANewInstanceWhenPropertyChanges()
    {
        $this->assertNotEquals($this->uri, $this->uri->withScheme('https'));
        $this->assertNotEquals($this->uri, $this->uri->withUserInfo('login', null));
        $this->assertNotEquals($this->uri, $this->uri->withHost('shop.example.com'));
        $this->assertNotEquals($this->uri, $this->uri->withPort(81));
        $this->assertNotEquals($this->uri, $this->uri->withPath('/test/file.php'));
        $this->assertNotEquals($this->uri, $this->uri->withQuery('kingkong=tata'));
        $this->assertNotEquals($this->uri, $this->uri->withFragment('doc2'));
    }

    public function testAutomaticUrlNormalization()
    {
        $raw = 'HtTpS://MaStEr.eXaMpLe.CoM:/%7ejohndoe/%a1/in+dex.php?fào.%bar=v%61lue#fragment';
        $normalized = 'https://master.example.com/%7ejohndoe/%a1/in+dex.php?f%C3%A0o.%bar=v%61lue#fragment';
        $this->assertSame($normalized, (string) Http::createFromString($raw));
    }

    public function testAutomaticUrlNormalizationBis()
    {
        $raw = 'http://Bébé.BE./path';
        $normalized = 'http://xn--bb-bjab.be./path';
        $this->assertSame($normalized, (string) Http::createFromString($raw));
    }

    public function testPreserveComponentsOnInstantiation()
    {
        $uri = 'http://:@example.com?#';
        $this->assertSame($uri, (string) Http::createFromString($uri));
    }

    public function testRemoveFragment()
    {
        $this->assertSame(
            'http://login:pass@secure.example.com:443/test/query.php?kingkong=toto',
            (string) $this->uri->withFragment('')
        );
    }

    public function testRemoveQuery()
    {
        $this->assertSame(
            'http://login:pass@secure.example.com:443/test/query.php#doc3',
            (string) $this->uri->withQuery('')
        );
    }

    public function testRemovePath()
    {
        $this->assertSame(
            'http://login:pass@secure.example.com:443?kingkong=toto#doc3',
            (string) $this->uri->withPath('')
        );
    }

    public function testRemovePort()
    {
        $this->assertSame(
            'http://login:pass@secure.example.com/test/query.php?kingkong=toto#doc3',
            (string) $this->uri->withPort(null));
    }

    public function testRemoveUserInfo()
    {
        $this->assertSame(
            'http://secure.example.com:443/test/query.php?kingkong=toto#doc3',
            (string) $this->uri->withUserInfo('')
        );
    }

    public function testRemoveScheme()
    {
        $this->assertSame(
            '//login:pass@secure.example.com:443/test/query.php?kingkong=toto#doc3',
            (string) $this->uri->withScheme('')
        );
    }

    public function testRemoveAuthority()
    {
        $uri_with_host = (string) $this->uri
            ->withUserInfo('')
            ->withPort(null)
            ->withScheme('')
            ->withHost('');
        $this->assertSame('/test/query.php?kingkong=toto#doc3', $uri_with_host);
    }

    /**
     * @dataProvider portProvider
     */
    public function testPort($uri, $port)
    {
        $this->assertSame($port, Http::createFromString($uri)->getPort());
    }

    public function portProvider()
    {
        return [
            ['http://www.example.com:443/', 443],
            ['http://www.example.com:80/', null],
            ['http://www.example.com', null],
            ['//www.example.com:80/', 80],
        ];
    }

    public function testWithInvalidCharacters()
    {
        $this->expectException(ParserException::class);
        Http::createFromString("http://example.com/path\n");
    }

    public function testWithSchemeFailedWithUnsupportedScheme()
    {
        $this->expectException(Exception::class);
        Http::createFromString('http://example.com')->withScheme('telnet');
    }

    public function testWithPathFailedWithInvalidChars()
    {
        $this->expectException(Exception::class);
        Http::createFromString('http://example.com')->withPath('#24');
    }

    public function testWithQueryFailedWithInvalidChars()
    {
        $this->expectException(Exception::class);
        Http::createFromString('http://example.com')->withQuery('?#');
    }

    public function testModificationFailedWithUnsupportedType()
    {
        $this->expectException(Exception::class);
        Http::createFromString('http://example.com/path')->withQuery(null);
    }

    public function testModificationFailedWithUnsupportedPort()
    {
        $this->expectException(ParserException::class);
        Http::createFromString('http://example.com/path')->withPort(12365894);
    }

    /**
     * @dataProvider invalidUserInfoProvider
     */
    public function testModificationFailedWithInvalidUserInfo($user, $password)
    {
        $this->expectException(Exception::class);
        Http::createFromString('http://example.com/path')->withUserInfo($user, $password);
    }

    public function invalidUserInfoProvider()
    {
        return [
            ['login:', null],
            ['login', 'password@'],
            [['login'], null],
        ];
    }

    /**
     * @dataProvider invalidURI
     */
    public function testCreateFromInvalidUrlKO($uri)
    {
        $this->expectException(ParserException::class);
        Http::createFromString($uri);
    }

    public function invalidURI()
    {
        return [
            ['http://user@:80'],
            ['//user@:80'],
        ];
    }

    public function testModificationFailed()
    {
        $this->expectException(Exception::class);
        Http::createFromString('http://example.com/path')
            ->withScheme('')
            ->withHost('')
            ->withPath('data:go');
    }

    public function testEmptyValueDetection()
    {
        $expected = '//0:0@0/0?0#0';
        $this->assertSame($expected, Http::createFromString($expected)->__toString());
    }

    public function testCreateFromComponents()
    {
        $uri = '//0:0@0/0?0#0';
        $this->assertEquals(
            Http::createFromComponents(parse_url($uri)),
            Http::createFromString($uri)
        );
    }

    /**
     * @supportsDebugInfo
     */
    public function testDebugInfo()
    {
        $this->assertInternalType('array', $this->uri->__debugInfo());
        ob_start();
        var_dump($this->uri);
        $res = ob_get_clean();
        $this->assertContains($this->uri->__toString(), $res);
    }

    public function testSetState()
    {
        $uri = Http::createFromString('https://a:b@c:442/d?q=r#f');
        $generateUri = eval('return '.var_export($uri, true).';');
        $this->assertEquals($uri, $generateUri);
    }
}
