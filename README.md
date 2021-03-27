Uri Schemes
=======

# This package is EOL since 2019-10-18

**You should instead use: [The latest League URI package](https://github.com/thephpleague/uri/releases).**

[![Build Status](https://img.shields.io/travis/thephpleague/uri-schemes/master.svg?style=flat-square)](https://travis-ci.org/thephpleague/uri-schemes)
[![Latest Version](https://img.shields.io/github/release/thephpleague/uri-schemes.svg?style=flat-square)](https://github.com/thephpleague/uri-components/schemes)

This package contains concrete URI objects represented as immutable value object. Each URI object implements the `League\Uri\Interfaces\Uri` interface as defined in the [uri-interfaces package](https://github.com/thephpleague/uri-interfaces) or the `Psr\Http\Message\UriInterface` from [PSR-7](http://www.php-fig.org/psr/psr-7/).

System Requirements
-------

You need:

- **PHP >= 7.0.13** but the latest stable version of PHP is recommended

While the library no longer requires the `intl` extension, it is strongly advise to install this extension if you are dealing with URIs containing non-ASCII host. Without it, an exception will be thrown if such host is used.

Dependencies
-------

- [PSR-7](http://www.php-fig.org/psr/psr-7/)
- [League URI Interfaces](https://github.com/thephpleague/uri-interfaces)
- [League URI Parser](https://github.com/thephpleague/uri-parser)

Installation
--------

```
$ composer require league/uri-schemes
```

Documentation
--------

Full documentation can be found at [uri.thephpleague.com](http://uri.thephpleague.com).


Contributing
-------

Contributions are welcome and will be fully credited. Please see [CONTRIBUTING](.github/CONTRIBUTING.md) and [CONDUCT](CONDUCT.md) for details.

Testing
-------

`League Uri Schemes` has a :

- a [PHPUnit](https://phpunit.de) test suite
- a coding style compliance test suite using [PHP CS Fixer](http://cs.sensiolabs.org/).
- a code analysis compliance test suite using [PHPStan](https://github.com/phpstan/phpstan).

To run the tests, run the following command from the project folder.

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
