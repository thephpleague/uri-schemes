<?php

/**
 * League.Uri (http://uri.thephpleague.com).
 *
 * @package    League\Uri
 * @subpackage League\Uri\Schemes
 * @author     Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @license    https://github.com/thephpleague/uri-schemes/blob/master/LICENSE (MIT License)
 * @version    1.2.1
 * @link       https://github.com/thephpleague/uri-schemes
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LeagueTest\Uri;

use BadMethodCallException;
use League\Uri\Exception as ParserException;
use League\Uri\Http;
use League\Uri\Uri;
use League\Uri\UriException;
use League\Uri\UriInterface;
use PHPUnit\Framework\TestCase;
use function parse_url;
use function var_export;

/**
 * @group uri
 * @coversDefaultClass League\Uri\Schemes\AbstractUri
 */
class UriTest extends TestCase
{
    /**
     * @var Uri
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
        unset($this->uri);
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
        self::assertSame($normalized, (string) Uri::createFromString($raw));
    }

    /**
     * @covers ::__toString
     * @covers ::formatHost
     */
    public function testAutomaticUrlNormalizationBis()
    {
        self::assertSame(
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
        self::assertSame($uri, (string) Uri::createFromString($uri));
    }

    /**
     * @covers ::getScheme
     * @covers ::withScheme
     */
    public function testScheme()
    {
        self::assertSame('http', $this->uri->getScheme());
        self::assertSame($this->uri, $this->uri->withScheme('http'));
        self::assertNotEquals($this->uri, $this->uri->withScheme('https'));
        self::assertSame(
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
        self::assertSame('login:pass', $this->uri->getUserInfo());
        self::assertSame($this->uri, $this->uri->withUserInfo('login', 'pass'));
        self::assertNotEquals($this->uri, $this->uri->withUserInfo('login', null));
        self::assertSame(
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
        self::assertSame('secure.example.com', $this->uri->getHost());
        self::assertSame($this->uri, $this->uri->withHost('secure.example.com'));
        self::assertNotEquals($this->uri, $this->uri->withHost('[::1]'));
    }

    /**
     * @covers ::getAuthority
     */
    public function testGetAuthority()
    {
        self::assertSame('login:pass@secure.example.com:443', $this->uri->getAuthority());
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
        self::assertSame('/test/query.php?kingkong=toto#doc3', $uri_with_host);
    }

    /**
     * @covers ::getPort
     * @covers ::withPort
     * @covers ::filterPort
     */
    public function testPort()
    {
        self::assertSame(443, $this->uri->getPort());
        self::assertSame($this->uri, $this->uri->withPort(443));
        self::assertNotEquals($this->uri, $this->uri->withPort(81));
        self::assertSame(
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
        self::assertSame('/test/query.php', $this->uri->getPath());
        self::assertSame($this->uri, $this->uri->withPath('/test/query.php'));
        self::assertNotEquals($this->uri, $this->uri->withPath('/test/file.php'));
        self::assertSame(
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
        self::assertSame('kingkong=toto', $this->uri->getQuery());
        self::assertSame($this->uri, $this->uri->withQuery('kingkong=toto'));
        self::assertNotEquals($this->uri, $this->uri->withQuery('kingkong=tata'));
        self::assertSame(
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
        self::assertSame('doc3', $this->uri->getFragment());
        self::assertSame($this->uri, $this->uri->withFragment('doc3'));
        self::assertNotEquals($this->uri, $this->uri->withFragment('doc2'));
        self::assertSame(
            'http://login:pass@secure.example.com:443/test/query.php?kingkong=toto',
            (string) $this->uri->withFragment('')
        );
    }

    /**
     * @covers ::getIdnaErrorMessage
     * @covers ::formatHost
     */
    public function testCannotConvertInvalidHost()
    {
        self::expectException(ParserException::class);
        Uri::createFromString('http://_b%C3%A9bé.be-/foo/bar');
    }

    public function testWithSchemeFailedWithInvalidSchemeValue()
    {
        self::expectException(UriException::class);
        Uri::createFromString('http://example.com')->withScheme('tété');
    }

    /**
     * @covers ::filterString
     */
    public function testWithInvalidCharacters()
    {
        self::expectException(ParserException::class);
        Uri::createFromString("http://example.com/path\n");
    }

    /**
     * @covers ::assertValidState
     */
    public function testWithSchemeFailedWithUnsupportedScheme()
    {
        self::expectException(UriException::class);
        Http::createFromString('http://example.com')->withScheme('telnet');
    }

    /**
     * @covers ::assertValidState
     */
    public function testWithPathFailedWithInvalidChars()
    {
        self::expectException(UriException::class);
        Uri::createFromString('http://example.com')->withPath('#24');
    }

    /**
     * @covers ::assertValidState
     */
    public function testWithPathFailedWithInvalidPathRelativeToTheAuthority()
    {
        self::expectException(UriException::class);
        Uri::createFromString('http://example.com')->withPath('foo/bar');
    }

    /**
     * @covers ::formatRegisteredName
     * @covers ::withHost
     */
    public function testModificationFailedWithInvalidHost()
    {
        self::expectException(UriException::class);
        Uri::createFromString('http://example.com/path')->withHost('%23');
    }

    /**
     * @covers ::assertValidState
     * @dataProvider missingAuthorityProvider
     */
    public function testModificationFailedWithMissingAuthority($path)
    {
        self::expectException(UriException::class);
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
        self::assertSame($expected, Uri::createFromString($expected)->__toString());
    }

    public function testPathDetection()
    {
        $expected = 'foo/bar:';
        self::assertSame($expected, Uri::createFromString($expected)->getPath());
    }

    /**
     * @dataProvider setStateDataProvider
     *
     *
     * @covers ::__set_state
     */
    public function testSetState(UriInterface $uri)
    {
        self::assertEquals($uri, eval('return '.var_export($uri, true).';'));
    }

    public function setStateDataProvider()
    {
        return [
            'all components' => [Uri::createFromString('https://a:b@c:442/d?q=r#f')],
            'without scheme' => [Uri::createFromString('//a:b@c:442/d?q=r#f')],
            'without userinfo' => [Uri::createFromString('https://c:442/d?q=r#f')],
            'without port' => [Uri::createFromString('https://a:b@c/d?q=r#f')],
            'without path' => [Uri::createFromString('https://a:b@c:442?q=r#f')],
            'without query' => [Uri::createFromString('https://a:b@c:442/d#f')],
            'without fragment' => [Uri::createFromString('https://a:b@c:442/d?q=r')],
            'without pass' => [Uri::createFromString('https://a@c:442/d?q=r#f')],
            'without authority' => [Uri::createFromString('/d?q=r#f')],
       ];
    }

    /**
     * @covers ::createFromComponents
     * @covers ::formatRegisteredName
     */
    public function testCreateFromComponents()
    {
        $uri = '//0:0@0/0?0#0';
        self::assertEquals(
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
        self::expectException(UriException::class);
        Uri::createFromString('http://example.com/path')->withPort(-1);
    }


    /**
     * @covers ::formatIp
     */
    public function testCreateFromComponentsHandlesScopedIpv6()
    {
        $expected = '[fe80:1234::%251]';
        self::assertSame(
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
        self::assertSame(
            $expected,
            Uri::createFromComponents(['host' => $expected])->getHost()
        );
    }


    /**
     * @covers ::formatIp
     */
    public function testCreateFromComponentsThrowsOnInvalidIpvFuture()
    {
        self::expectException(ParserException::class);
        Uri::createFromComponents(['host' => '[v4.1.2.3]']);
    }

    /**
     * @covers ::filterString
     */
    public function testCreateFromComponentsThrowsExceptionWithInvalidChars()
    {
        self::expectException(ParserException::class);
        Uri::createFromComponents()->withFragment("\n\rtoto");
    }

    /**
     * @covers ::formatIp
     */
    public function testCreateFromComponentsThrowsException()
    {
        self::expectException(ParserException::class);
        Uri::createFromComponents(['host' => '[127.0.0.1]']);
    }

    /**
     * @covers ::formatIp
     */
    public function testCreateFromComponentsThrowsException2()
    {
        self::expectException(ParserException::class);
        Uri::createFromComponents(['host' => '[127.0.0.1%251]']);
    }

    /**
     * @covers ::formatIp
     */
    public function testCreateFromComponentsThrowsException3()
    {
        self::expectException(ParserException::class);
        Uri::createFromComponents(['host' => '[fe80:1234::%25 1]']);
    }

    /**
     * @covers ::formatIp
     */
    public function testCreateFromComponentsThrowsException4()
    {
        self::expectException(ParserException::class);
        Uri::createFromComponents(['host' => '[::1%251]']);
    }

    /**
     * @covers ::formatRegisteredName
     */
    public function testCreateFromComponentsThrowsException5()
    {
        self::expectException(ParserException::class);
        Uri::createFromComponents(['host' => 'a⒈com']);
    }

    /**
     * @covers ::__set
     */
    public function testInvalidSetterThrowException()
    {
        self::expectException(BadMethodCallException::class);
        Uri::createFromString()->host = 'thephpleague.com';
    }

    /**
     * @covers ::__get
     */
    public function testInvalidGetterThrowException()
    {
        self::expectException(BadMethodCallException::class);
        Uri::createFromString()->path;
    }

    /**
     * @covers ::__isset
     */
    public function testInvalidIssetThrowException()
    {
        self::expectException(BadMethodCallException::class);
        isset(Uri::createFromString()->path);
    }

    /**
     * @covers ::__unset
     */
    public function testInvalidUnssetThrowException()
    {
        self::expectException(BadMethodCallException::class);
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

        self::assertContains(
            '/v1/people/~:(first-name,last-name,email-address,picture-url)',
            (string) $uri
        );
    }

    /**
     * @dataProvider userInfoProvider
     */
    public function testWithUserInfoEncodesUsernameAndPassword($user, $credential, $expected)
    {
        $uri = Uri::createFromString('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $new = $uri->withUserInfo($user, $credential);
        self::assertSame($expected, $new->getUserInfo());
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
