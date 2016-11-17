# Changelog

All Notable changes to `League\Uri\Components` will be documented in this file

## 0.2.0 - Next

### Added

- Re-instate `createFromComponents` named constructor to allow swapping the URI parser
- Added specific exceptions class extending InvalidArgumentException to isolate the library
exceptions.
- Improve components encoding

### Fixed

- None

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