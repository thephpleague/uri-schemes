# Changelog

All Notable changes to `League\Uri\Schemes` will be documented in this file

## 1.1.0 - TBD

### Added

- `League\Uri\Uri` default URI object which validate RFC3986
- `League\Uri\AbstractUri`
- `League\Uri\Data`
- `League\Uri\File`
- `League\Uri\Ftp`
- `League\Uri\Http`
- `League\Uri\UriException`
- `League\Uri\Ws`

### Fixed

- improve path and user info component encoding

### Deprecated

- `League\Uri\Schemes\AbstractUri` replace by `League\Uri\AbstractUri`
- `League\Uri\Schemes\Data` replace by `League\Uri\Data`
- `League\Uri\Schemes\File` replace by `League\Uri\File`
- `League\Uri\Schemes\Ftp` replace by `League\Uri\Ftp`
- `League\Uri\Schemes\Http` replace by `League\Uri\Http`
- `League\Uri\Schemes\UriException` replace by `League\Uri\UriException`
- `League\Uri\Schemes\Ws` replace by `League\Uri\Ws`

### Remove

- None

## 1.0.6 - 2017-08-10

### Added

- None

### Fixed

- Bug fix label conversion depending on locale [issue #102](https://github.com/thephpleague/uri/issues/102)

### Deprecated

- None

### Remove

- None

## 1.0.5 - 2017-06-12

### Added

- None

### Fixed

- Improve `Http::createFromServer` to handle IIS servers see [PR #3](https://github.com/thephpleague/uri-schemes/pull/3) and [issue #101](https://github.com/thephpleague/uri/issues/101)

### Deprecated

- None

### Removed

- None

## 1.0.4 - 2017-04-19

### Added

- None

### Fixed

- Bug fix Host normalization `League\Uri\Schemes\AbstractUri::formatHost` see [issue #5](https://github.com/thephpleague/uri-parser/issue/5)

### Deprecated

- None

### Removed

- None

## 1.0.3 - 2017-03-06

### Added

- None

### Fixed

- Bug fix `$_SERVER['SERVER_PORT']` value with `League\Uri\Schemes\Http::createFromServer` see [#PR1](https://github.com/thephpleague/uri-schemes/pull/1)

### Deprecated

- None

### Removed

- None

## 1.0.2 - 2017-03-01

### Added

- None

### Fixed

- Improved `League\Uri\Schemes\Http::createFromServer` with `$_SERVER['REQUEST_URI']`

### Deprecated

- None

### Removed

- None

## 1.0.1 - 2017-01-16

### Added

- None

### Fixed

- `League\Uri\Schemes\Http::createFromComponents` with invalid Host

### Deprecated

- None

### Removed

- None

## 1.0.0 - 2017-01-04

### Added

- None

### Fixed

- Improve `League\Uri\Schemes\Http::createFromServer` static method

### Deprecated

- None

### Removed

- PHP5 support

## 0.4.0 - 2016-12-09

### Added

- Added `League\Uri\Schemes\UriException` to replace `League\Uri\Schemes\Exceptions\Exception`

### Fixed

- `League\Uri\Schemes\AbstractUri` implements `League\Uri\Interfaces\Uri`
- `League\Uri\Schemes\File` host normalization is done when the host is empty

### Deprecated

- None

### Removed

- `League\Uri\Schemes\Exceptions\Exception`

## 0.3.0 - 2016-12-01

### Added

- Re-instate `createFromComponents` named constructor to allow swapping the URI parser
- Added specific exceptions class extending InvalidArgumentException to isolate the library
exceptions.
- Improve components encoding

### Fixed

- userInfo encoding
- `File::createFromUnixPath` encoding
- `File::createFromWindowsPath` encoding
- `Http::createFromServer` user info encoding
- Host normalisation now convert host into RFC3986 encoding using punycode if needed

### Deprecated

- None

### Removed

- Dependency to league uri components

## 0.1.0 - 2016-10-17

### Added

- `League\Uri\Schemes\File`

### Fixed

- None

### Deprecated

- `createFromString` named constructor you should use the constructor directly

### Removed

- `createFromComponents` named constructor