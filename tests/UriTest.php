<?php

namespace LeagueTest\Uri;

use BadMethodCallException;
use League\Uri\Exception as ParserException;
use League\Uri\Http;
use League\Uri\UriException;
use PHPUnit\Framework\TestCase;

/**
 * @group uri
 * @coversDefaultClass League\Uri\Schemes\AbstractUri
 */
class UriTest extends TestCase
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

    /**
     * @covers ::getParser
     * @covers ::__toString
     * @covers ::formatHost
     * @covers ::formatQueryAndFragment
     * @covers ::formatPort
     * @covers ::formatUserInfo
     * @covers ::formatScheme
     */
    public function testAutomaticUrlNormalization()
    {
        $raw = 'HtTpS://MaStEr.eXaMpLe.CoM:/%7ejohndoe/%a1/in+dex.php?fào.%bar=v%61lue#fragment';
        $normalized = 'https://master.example.com/%7ejohndoe/%a1/in+dex.php?f%C3%A0o.%bar=v%61lue#fragment';
        $this->assertSame($normalized, (string) Http::createFromString($raw));
    }

    /**
     * @covers ::__toString
     * @covers ::formatHost
     */
    public function testAutomaticUrlNormalizationBis()
    {
        $this->assertSame(
            'http://xn--bb-bjab.be./path',
            (string) Http::createFromString('http://Bébé.BE./path')
        );
    }

    /**
     * @covers ::getParser
     * @covers ::getUriString
     * @covers ::__toString
     * @covers ::formatUserInfo
     * @covers ::formatQueryAndFragment
     */
    public function testPreserveComponentsOnInstantiation()
    {
        $uri = 'http://:@example.com?#';
        $this->assertSame($uri, (string) Http::createFromString($uri));
    }

    /**
     * @covers ::getScheme
     * @covers ::withScheme
     */
    public function testScheme()
    {
        $this->assertSame('http', $this->uri->getScheme());
        $this->assertSame($this->uri, $this->uri->withScheme('http'));
        $this->assertNotEquals($this->uri, $this->uri->withScheme('https'));
        $this->assertSame(
            '//login:pass@secure.example.com:443/test/query.php?kingkong=toto#doc3',
            (string) $this->uri->withScheme('')
        );
    }

    /**
     * @covers ::getUserInfo
     * @covers ::withUserInfo
     * @covers ::formatUserInfo
     */
    public function testUserInfo()
    {
        $this->assertSame('login:pass', $this->uri->getUserInfo());
        $this->assertSame($this->uri, $this->uri->withUserInfo('login', 'pass'));
        $this->assertNotEquals($this->uri, $this->uri->withUserInfo('login', null));
        $this->assertSame(
            'http://secure.example.com:443/test/query.php?kingkong=toto#doc3',
            (string) $this->uri->withUserInfo('')
        );
    }

    /**
     * @covers ::getHost
     * @covers ::withHost
     */
    public function testHost()
    {
        $this->assertSame('secure.example.com', $this->uri->getHost());
        $this->assertSame($this->uri, $this->uri->withHost('secure.example.com'));
        $this->assertNotEquals($this->uri, $this->uri->withHost('shop.example.com'));
    }

    /**
     * @covers ::getAuthority
     */
    public function testGetAuthority()
    {
        $this->assertSame('login:pass@secure.example.com:443', $this->uri->getAuthority());
    }

    /**
     * @covers ::withUserInfo
     * @covers ::withPort
     * @covers ::withScheme
     * @covers ::withHost
     */
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
     * @covers ::getPort
     * @covers ::withPort
     */
    public function testPort()
    {
        $this->assertSame(443, $this->uri->getPort());
        $this->assertSame($this->uri, $this->uri->withPort(443));
        $this->assertNotEquals($this->uri, $this->uri->withPort(81));
        $this->assertSame(
            'http://login:pass@secure.example.com/test/query.php?kingkong=toto#doc3',
            (string) $this->uri->withPort(null)
        );
    }

    /**
     * @covers ::getPath
     * @covers ::withPath
     */
    public function testPath()
    {
        $this->assertSame('/test/query.php', $this->uri->getPath());
        $this->assertSame($this->uri, $this->uri->withPath('/test/query.php'));
        $this->assertNotEquals($this->uri, $this->uri->withPath('/test/file.php'));
        $this->assertSame(
            'http://login:pass@secure.example.com:443?kingkong=toto#doc3',
            (string) $this->uri->withPath('')
        );
    }

    /**
     * @covers ::getQuery
     * @covers ::withQuery
     */
    public function testQuery()
    {
        $this->assertSame('kingkong=toto', $this->uri->getQuery());
        $this->assertSame($this->uri, $this->uri->withQuery('kingkong=toto'));
        $this->assertNotEquals($this->uri, $this->uri->withQuery('kingkong=tata'));
        $this->assertSame(
            'http://login:pass@secure.example.com:443/test/query.php#doc3',
            (string) $this->uri->withQuery('')
        );
    }

    /**
     * @covers ::getFragment
     * @covers ::withFragment
     */
    public function testFragment()
    {
        $this->assertSame('doc3', $this->uri->getFragment());
        $this->assertSame($this->uri, $this->uri->withFragment('doc3'));
        $this->assertNotEquals($this->uri, $this->uri->withFragment('doc2'));
        $this->assertSame(
            'http://login:pass@secure.example.com:443/test/query.php?kingkong=toto',
            (string) $this->uri->withFragment('')
        );
    }

    public function testWithInvalidCharacters()
    {
        $this->expectException(ParserException::class);
        Http::createFromString("http://example.com/path\n");
    }

    /**
     * @covers ::assertValidState
     */
    public function testWithSchemeFailedWithUnsupportedScheme()
    {
        $this->expectException(UriException::class);
        Http::createFromString('http://example.com')->withScheme('telnet');
    }

    /**
     * @covers ::assertValidState
     */
    public function testWithPathFailedWithInvalidChars()
    {
        $this->expectException(UriException::class);
        Http::createFromString('http://example.com')->withPath('#24');
    }

    /**
     * @covers ::assertValidState
     */
    public function testWithPathFailedWithInvalidPathRelativeToTheAuthority()
    {
        $this->expectException(UriException::class);
        Http::createFromString('http://example.com')->withPath('foo/bar');
    }

    /**
     * @covers ::assertValidState
     */
    public function testWithQueryFailedWithInvalidChars()
    {
        $this->expectException(UriException::class);
        Http::createFromString('http://example.com')->withQuery('?#');
    }

    /**
     * @covers ::assertValidState
     */
    public function testModificationFailedWithUnsupportedPort()
    {
        $this->expectException(UriException::class);
        Http::createFromString('http://example.com/path')->withPort(12365894);
    }

    /**
     * @covers ::assertValidState
     */
    public function testModificationFailedWithInvalidHost()
    {
        $this->expectException(UriException::class);
        Http::createFromString('http://example.com/path')->withHost('%23');
    }

    /**
     * @covers ::assertValidState
     * @dataProvider invalidUserInfoProvider
     *
     * @param mixed $user
     * @param mixed $password
     */
    public function testModificationFailedWithInvalidUserInfo($user, $password)
    {
        $this->expectException(UriException::class);
        Http::createFromString('http://example.com/path')->withUserInfo($user, $password);
    }

    public function invalidUserInfoProvider()
    {
        return [
            ['login:', null],
            ['login', 'password@'],
        ];
    }

    /**
     * @covers ::assertValidState
     * @dataProvider invalidURI
     *
     * @param mixed $uri
     */
    public function testCreateFromInvalidUrlKO($uri)
    {
        $this->expectException(UriException::class);
        Http::createFromString($uri);
    }

    public function invalidURI()
    {
        return [
            ['http://user@:80'],
            ['//user@:80'],
        ];
    }

    /**
     * @covers ::assertValidState
     * @dataProvider missingAuthorityProvider
     * @param mixed $path
     */
    public function testModificationFailedWithMissingAuthority($path)
    {
        $this->expectException(UriException::class);
        Http::createFromString('http://example.com/path')
            ->withScheme('')
            ->withHost('')
            ->withPath($path);
    }

    /**
     * @covers ::assertValidState
     */
    public function missingAuthorityProvider()
    {
        return [
            ['data:go'],
            ['//data'],
        ];
    }

    /**
     * @covers ::__toString
     * @covers ::formatHost
     * @covers ::formatQueryAndFragment
     * @covers ::formatPort
     * @covers ::formatUserInfo
     */
    public function testEmptyValueDetection()
    {
        $expected = '//0:0@0/0?0#0';
        $this->assertSame($expected, Http::createFromString($expected)->__toString());
    }

    public function testPathDetection()
    {
        $expected = 'foo/bar:';
        $this->assertSame($expected, Http::createFromString($expected)->getPath());
    }

    /**
     * @covers ::__set_state
     */
    public function testSetState()
    {
        $uri = Http::createFromString('https://a:b@c:442/d?q=r#f');
        $generateUri = eval('return '.var_export($uri, true).';');
        $this->assertEquals($uri, $generateUri);
    }

    /**
     * @covers ::createFromComponents
     */
    public function testCreateFromComponents()
    {
        $uri = '//0:0@0/0?0#0';
        $this->assertEquals(
            Http::createFromComponents(parse_url($uri)),
            Http::createFromString($uri)
        );
    }

    public function testCreateFromComponentsTrowsException()
    {
        $this->expectException(ParserException::class);
        Http::createFromComponents(['host' => '[127.0.0.1]']);
    }

    /**
     * @covers ::__set
     */
    public function testInvalidSetterThrowException()
    {
        $this->expectException(BadMethodCallException::class);
        Http::createFromString()->host = 'thephpleague.com';
    }

    /**
     * @covers ::__get
     */
    public function testInvalidGetterThrowException()
    {
        $this->expectException(BadMethodCallException::class);
        Http::createFromString()->path;
    }

    /**
     * @covers ::__isset
     */
    public function testInvalidIssetThrowException()
    {
        $this->expectException(BadMethodCallException::class);
        isset(Http::createFromString()->path);
    }

    /**
     * @covers ::__unset
     */
    public function testInvalidUnssetThrowException()
    {
        $this->expectException(BadMethodCallException::class);
        unset(Http::createFromString()->path);
    }
}
