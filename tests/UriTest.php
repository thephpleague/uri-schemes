<?php

namespace LeagueTest\Uri;

use BadMethodCallException;
use League\Uri\Exception as ParserException;
use League\Uri\Http;
use League\Uri\Uri;
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
        $this->uri = Uri::createFromString(
            'http://login:pass@secure.example.com:443/test/query.php?kingkong=toto#doc3'
        );
    }

    protected function tearDown()
    {
        $this->uri = null;
    }

    /**
     * @covers ::__toString
     * @covers ::formatHost
     * @covers ::formatRegisteredName
     * @covers ::formatQueryAndFragment
     * @covers ::formatPort
     * @covers ::formatUserInfo
     * @covers ::formatScheme
     */
    public function testAutomaticUrlNormalization()
    {
        $raw = 'HtTpS://MaStEr.B%c3%A9b%c3%a9.eXaMpLe.CoM:/%7ejohndoe/%a1/in+dex.php?fào.%bar=v%61lue#fragment';
        $normalized = 'https://master.xn--bb-bjab.example.com/%7ejohndoe/%a1/in+dex.php?f%C3%A0o.%bar=v%61lue#fragment';
        $this->assertSame($normalized, (string) Uri::createFromString($raw));
    }

    /**
     * @covers ::__toString
     * @covers ::formatHost
     */
    public function testAutomaticUrlNormalizationBis()
    {
        $this->assertSame(
            'http://xn--bb-bjab.be./path',
            (string) Uri::createFromString('http://Bébé.BE./path')
        );
    }

    /**
     * @covers ::getUriString
     * @covers ::__toString
     * @covers ::formatUserInfo
     * @covers ::formatQueryAndFragment
     */
    public function testPreserveComponentsOnInstantiation()
    {
        $uri = 'http://:@example.com?#';
        $this->assertSame($uri, (string) Uri::createFromString($uri));
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
     * @covers ::formatHost
     * @covers ::formatIp
     * @covers ::formatRegisteredName
     */
    public function testHost()
    {
        $this->assertSame('secure.example.com', $this->uri->getHost());
        $this->assertSame($this->uri, $this->uri->withHost('secure.example.com'));
        $this->assertNotEquals($this->uri, $this->uri->withHost('[::1]'));
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
     * @covers ::filterPort
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

    /**
     * @covers ::getIDNAErrors
     * @covers ::formatHost
     */
    public function testCannotConvertInvalidHost()
    {
        $this->expectException(ParserException::class);
        Uri::createFromString('http://_b%C3%A9bé.be-/foo/bar');
    }

    public function testWithSchemeFailedWithInvalidSchemeValue()
    {
        $this->expectException(UriException::class);
        Uri::createFromString('http://example.com')->withScheme('tété');
    }

    /**
     * @covers ::filterString
     */
    public function testWithInvalidCharacters()
    {
        $this->expectException(ParserException::class);
        Uri::createFromString("http://example.com/path\n");
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
        Uri::createFromString('http://example.com')->withPath('#24');
    }

    /**
     * @covers ::assertValidState
     */
    public function testWithPathFailedWithInvalidPathRelativeToTheAuthority()
    {
        $this->expectException(UriException::class);
        Uri::createFromString('http://example.com')->withPath('foo/bar');
    }

    /**
     * @covers ::formatRegisteredName
     * @covers ::withHost
     */
    public function testModificationFailedWithInvalidHost()
    {
        $this->expectException(UriException::class);
        Uri::createFromString('http://example.com/path')->withHost('%23');
    }

    /**
     * @covers ::assertValidState
     * @dataProvider missingAuthorityProvider
     * @param mixed $path
     */
    public function testModificationFailedWithMissingAuthority($path)
    {
        $this->expectException(UriException::class);
        Uri::createFromString('http://example.com/path')
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
     * @covers ::formatRegisteredName
     * @covers ::formatQueryAndFragment
     * @covers ::formatPort
     * @covers ::formatUserInfo
     */
    public function testEmptyValueDetection()
    {
        $expected = '//0:0@0/0?0#0';
        $this->assertSame($expected, Uri::createFromString($expected)->__toString());
    }

    public function testPathDetection()
    {
        $expected = 'foo/bar:';
        $this->assertSame($expected, Uri::createFromString($expected)->getPath());
    }

    /**
     * @covers ::__set_state
     */
    public function testSetState()
    {
        $uri = Uri::createFromString('https://a:b@c:442/d?q=r#f');
        $generateUri = eval('return '.var_export($uri, true).';');
        $this->assertEquals($uri, $generateUri);
    }

    /**
     * @covers ::createFromComponents
     * @covers ::formatRegisteredName
     */
    public function testCreateFromComponents()
    {
        $uri = '//0:0@0/0?0#0';
        $this->assertEquals(
            Uri::createFromComponents(parse_url($uri)),
            Uri::createFromString($uri)
        );
    }

    /**
     * @covers ::filterPort
     * @covers ::withPort
     */
    public function testModificationFailedWithInvalidPort()
    {
        $this->expectException(UriException::class);
        Uri::createFromString('http://example.com/path')->withPort(-1);
    }


    /**
     * @covers ::formatIp
     */
    public function testCreateFromComponentsHandlesScopedIpv6()
    {
        $expected = '[fe80:1234::%251]';
        $this->assertSame(
            $expected,
            Uri::createFromComponents(['host' => $expected])->getHost()
        );
    }

    /**
     * @covers ::formatIp
     */
    public function testCreateFromComponentsHandlesIpvFuture()
    {
        $expected = '[v1.ZZ.ZZ]';
        $this->assertSame(
            $expected,
            Uri::createFromComponents(['host' => $expected])->getHost()
        );
    }


    /**
     * @covers ::formatIp
     */
    public function testCreateFromComponentsThrowsOnInvalidIpvFuture()
    {
        $this->expectException(ParserException::class);
        Uri::createFromComponents(['host' => '[v4.1.2.3]']);
    }

    /**
     * @covers ::filterString
     */
    public function testCreateFromComponentsThrowsExceptionWithInvalidChars()
    {
        $this->expectException(ParserException::class);
        Uri::createFromComponents()->withFragment("\n\rtoto");
    }

    /**
     * @covers ::formatIp
     */
    public function testCreateFromComponentsThrowsException()
    {
        $this->expectException(ParserException::class);
        Uri::createFromComponents(['host' => '[127.0.0.1]']);
    }

    /**
     * @covers ::formatIp
     */
    public function testCreateFromComponentsThrowsException2()
    {
        $this->expectException(ParserException::class);
        Uri::createFromComponents(['host' => '[127.0.0.1%251]']);
    }

    /**
     * @covers ::formatIp
     */
    public function testCreateFromComponentsThrowsException3()
    {
        $this->expectException(ParserException::class);
        Uri::createFromComponents(['host' => '[fe80:1234::%25 1]']);
    }

    /**
     * @covers ::formatIp
     */
    public function testCreateFromComponentsThrowsException4()
    {
        $this->expectException(ParserException::class);
        Uri::createFromComponents(['host' => '[::1%251]']);
    }

    /**
     * @covers ::formatRegisteredName
     */
    public function testCreateFromComponentsThrowsException5()
    {
        $this->expectException(ParserException::class);
        Uri::createFromComponents(['host' => 'a⒈com']);
    }

    /**
     * @covers ::__set
     */
    public function testInvalidSetterThrowException()
    {
        $this->expectException(BadMethodCallException::class);
        Uri::createFromString()->host = 'thephpleague.com';
    }

    /**
     * @covers ::__get
     */
    public function testInvalidGetterThrowException()
    {
        $this->expectException(BadMethodCallException::class);
        Uri::createFromString()->path;
    }

    /**
     * @covers ::__isset
     */
    public function testInvalidIssetThrowException()
    {
        $this->expectException(BadMethodCallException::class);
        isset(Uri::createFromString()->path);
    }

    /**
     * @covers ::__unset
     */
    public function testInvalidUnssetThrowException()
    {
        $this->expectException(BadMethodCallException::class);
        unset(Uri::createFromString()->path);
    }

    /**
     * @covers ::filterPath
     */
    public function testReservedCharsInPathUnencoded()
    {
        $uri = Uri::createFromString()
            ->withHost('api.linkedin.com')
            ->withScheme('https')
            ->withPath('/v1/people/~:(first-name,last-name,email-address,picture-url)');

        $this->assertContains(
            '/v1/people/~:(first-name,last-name,email-address,picture-url)',
            (string) $uri
        );
    }

    /**
     * @dataProvider userInfoProvider
     * @param mixed $user
     * @param mixed $credential
     * @param mixed $expected
     */
    public function testWithUserInfoEncodesUsernameAndPassword($user, $credential, $expected)
    {
        $uri = Uri::createFromString('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $new = $uri->withUserInfo($user, $credential);
        $this->assertSame($expected, $new->getUserInfo());
    }

    public function userInfoProvider()
    {
        return [
            'no password' => ['login:', null, 'login%3A'],
            'password with delimiter' => ['login', 'password@', 'login:password%40'],
            'valid-chars' => ['foo', 'bar', 'foo:bar'],
            'colon'       => ['foo:bar', 'baz:bat', 'foo%3Abar:baz:bat'],
            'at'          => ['user@example.com', 'cred@foo', 'user%40example.com:cred%40foo'],
            'percent'     => ['%25', '%25', '%25:%25'],
            'invalid-enc' => ['%ZZ', '%GG', '%25ZZ:%25GG'],
        ];
    }
}
