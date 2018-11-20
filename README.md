Uri Schemes
=======

[![Build Status](https://img.shields.io/travis/thephpleague/uri-schemes/master.svg?style=flat-square)](https://travis-ci.org/thephpleague/uri-schemes)
[![Latest Version](https://img.shields.io/github/release/thephpleague/uri-schemes.svg?style=flat-square)](https://github.com/thephpleague/uri-components/schemes)

This package contains concrete URI objects represented as immutable value object. Each URI object implements the `League\Uri\Interfaces\Uri` interface as defined in the [uri-interfaces package](https://github.com/thephpleague/uri-interfaces) or the `Psr\Http\Message\UriInterface` from [PSR-7](http://www.php-fig.org/psr/psr-7/).

System Requirements
-------

You need:

- **PHP >= 7.1.3** but the latest stable version of PHP is recommended
- the `mbstring` extension
- the `intl` extension

Dependencies
-------

- [PSR-7](https://www.php-fig.org/psr/psr-7/)
- [PSR-17](https://www.php-fig.org/psr/psr-17/)
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
