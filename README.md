Uri Schemes
=======

This package contains concrete URI objects represented as immutable value object. Each URI object implements `League\Uri\Interfaces\Uri` interface as defined in the [uri-interfaces package](https://github.com/thephpleague/uri-interfaces) or the `Psr\Http\Message\UriInterface` from [PSR-7](http://www.php-fig.org/psr/psr-7/).

System Requirements
-------

You need:

- **PHP >= 5.6.0** but the latest stable version of PHP is recommended
- the `mbstring` extension
- the `intl` extension

Dependencies
-------

- [PSR-7](http://www.php-fig.org/psr/psr-7/)
- [uri-interfaces](https://github.com/thephpleague/uri-interfaces)
- [uri-components](https://github.com/thephpleague/uri-components)
- [uri-parser](https://github.com/thephpleague/uri-parser)

Installation
--------

Clone this repo and use composer install

Documentation
--------

The following URI objects are defined (order alphabetically):

- `League\Uri\Schemes\Data` : represents a Data scheme URI
- `League\Uri\Schemes\File` : represents a File scheme URI
- `League\Uri\Schemes\FTP` : represents a FTP scheme URI
- `League\Uri\Schemes\Http` : represents a HTTP/HTTPS scheme URI
- `League\Uri\Schemes\Ws` : represents a WS/WSS scheme URI

Usage
-------

All URI objects expose the same methods.

### Accessing URI parts and components

You can access the URI string, its individual parts and components using their respective getter methods.

```php
<?php

public Uri::__toString(): string
public Uri::getScheme(void): string
public Uri::getUserInfo(void): string
public Uri::getHost(void): string
public Uri::getPort(void): int|null
public Uri::getAuthority(void): string
public Uri::getPath(void): string
public Uri::getQuery(void): string
public Uri::getFragment(void): string
```

Which will lead to the following result for a simple URI:

```php
<?php

use League\Uri\Schemes\Http;

$uri = new Http("http://foo:bar@www.example.com:81/how/are/you?foo=baz#title");
echo $uri;                 //displays "http://foo:bar@www.example.com:81/how/are/you?foo=baz#title"
echo $uri->getScheme();    //displays "http"
echo $uri->getUserInfo();  //displays "foo:bar"
echo $uri->getHost();      //displays "www.example.com"
echo $uri->getPort();      //displays 81 as an integer
echo $uri->getAuthority(); //displays "foo:bar@www.example.com:81"
echo $uri->getPath();      //displays "/how/are/you"
echo $uri->getQuery();     //displays "foo=baz"
echo $uri->getFragment();  //displays "title"
```

### Modifying URIs, URI parts and components

**If the modifications do not alter the current object, it is returned as is, otherwise, a new modified object is returned.**

**The method may trigger a `InvalidArgumentException` exception if the resulting URI is not valid for a scheme specific URI.**

## Basic modifications

To completely replace one of the URI part you can use the modifying methods exposed by all URI object

```php
<?php

public Uri::withScheme(string $scheme): self
public Uri::withUserInfo(string $user [, string $password = null]): self
public Uri::withHost(string $host): self
public Uri::withPort(int|null $port): self
public Uri::withPath(string $path): self
public Uri::withQuery(string $query): self
public Uri::withFragment(string $fragment): self
```

Since All URI object are immutable you can chain each modifying methods to simplify URI creation and/or modification.

```php
<?php

use League\Uri\Schemes\Ws;

$uri = new Ws("ws://thephpleague.com/fr/")
    ->withScheme("wss")
    ->withUserInfo("foo", "bar")
    ->withHost("www.example.com")
    ->withPort(81)
    ->withPath("/how/are/you")
    ->withQuery("foo=baz");

echo $uri; //displays wss://foo:bar@www.example.com:81/how/are/you?foo=baz
```

Contributing
-------

Contributions are welcome and will be fully credited. Please see [CONTRIBUTING](.github/CONTRIBUTING.md) and [CONDUCT](CONDUCT.md) for details.

Testing
-------

`uri-schemes` has a [PHPUnit](https://phpunit.de) test suite and a coding style compliance test suite using [PHP CS Fixer](http://cs.sensiolabs.org/). To run the tests, run the following command from the project folder.

``` bash
$ composer test
```

Security
-------

If you discover any security related issues, please email nyamsprod@gmail.com instead of using the issue tracker.

Credits
-------

- [ignace nyamagana butera](https://github.com/nyamsprod)
- [All Contributors](https://github.com/thephpleague/uri/contributors)

License
-------

The MIT License (MIT). Please see [License File](LICENSE) for more information.
