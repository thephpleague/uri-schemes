# Changelog

All Notable changes to `League\Uri\Components` will be documented in this file

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