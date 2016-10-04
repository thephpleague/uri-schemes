<?php

namespace LeagueTest\Uri\Schemes;

use InvalidArgumentException;
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
        $raw = 'HtTpS://MaStEr.eXaMpLe.CoM:443/%7ejohndoe/%a1/in+dex.php?foo.bar=value#fragment';
        $normalized = 'https://master.example.com/%7Ejohndoe/%A1/in+dex.php?foo.bar=value#fragment';
        $this->assertSame($normalized, (string) Http::createFromString($raw));
    }

    public function testRemoveComponents()
    {
        $uri = Http::createFromString('http://user:pass@example.com:42/path?query#fragment');

        $this->assertSame('http://user:pass@example.com:42/path?query', (string) $uri->withFragment(''));
        $this->assertSame('http://user:pass@example.com:42/path#fragment', (string) $uri->withQuery(''));
        $this->assertSame('http://user:pass@example.com:42?query#fragment', (string) $uri->withPath(''));
        $this->assertSame('//user:pass@example.com:42/path?query#fragment', (string) $uri->withScheme(''));
        $this->assertSame('http://user:pass@example.com/path?query#fragment', (string) $uri->withPort(null));
        $this->assertSame('http://example.com:42/path?query#fragment', (string) $uri->withUserInfo(''));

        $uri_with_host = (string) $uri->withUserInfo('')->withPort(null)->withScheme('')->withHost('');
        $this->assertSame('/path?query#fragment', $uri_with_host);
    }


    /**
     * @param $uri
     * @param $port
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

    /**
     * @expectedException InvalidArgumentException
     */
    public function testWithSchemeFailedWithUnsupportedScheme()
    {
        Http::createFromString('http://example.com')->withScheme('telnet');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testWithPathFailedWithInvalidChars()
    {
        Http::createFromString('http://example.com')->withPath('#24');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testWithQueryFailedWithInvalidChars()
    {
        Http::createFromString('http://example.com')->withQuery('?#');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testModificationFailedWithUnsupportedType()
    {
        Http::createFromString('http://example.com/path')->withQuery(null);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testModificationFailedWithUnsupportedPort()
    {
        Http::createFromString('http://example.com/path')->withPort(12365894);
    }

    /**
     * @expectedException InvalidArgumentException
     * @dataProvider invalidUserInfoProvider
     */
    public function testModificationFailedWithInvalidUserInfo($user, $password)
    {
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
     * @expectedException InvalidArgumentException
     * @param $input
     */
    public function testCreateFromInvalidUrlKO($input)
    {
        Http::createFromString($input);
    }

    public function invalidURI()
    {
        return [
            ['http://user@:80'],
            ['//user@:80'],
        ];
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testModificationFailed()
    {
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

    /**
     * @expectedException InvalidArgumentException
     */
    public function testThrowExceptionOnUnknowPropertyGetter()
    {
        $this->uri->unknownProperty;
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testThrowExceptionOnUnknowPropertySetter()
    {
        $this->uri->unknownProperty = true;
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testThrowExceptionOnUnknowPropertyUnset()
    {
        unset($this->uri->unknownProperty);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testThrowExceptionOnUnknowPropertyIsset()
    {
        isset($this->uri->unknownProperty);
    }

    public function testSetState()
    {
        $uri = Http::createFromString('https://a:b@c:442/d?q=r#f');
        $generateUri = eval('return '.var_export($uri, true).';');
        $this->assertEquals($uri, $generateUri);
    }
}
