# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2019-11-29

### Added

- d806fe4: Added phpstan to travis for static code analysis.

### Changed

- #1: PHP 7.2 min requirement and updated library to support it.
- #4: Added DBAL usage documentation and updated existing examples.
- 2d0951d: Applied PSR12 and additional rules to library.

### Removed

- #1: Removed support for PHP <7.2.
- d806fe4: Removed deprecated mongo db support.

### Fixed

- d806fe4: Fixed static code analysis issues.

## [0.3.0] - 2015-05-18

### Changed

- Changed ProcessManager constructor to accept new Factory class as second argument.
- Use shared memory for interprocess communications (@MattJaniszewski).
- Added progress callbacks to Deferred.
- Added serializable objects for exit and error messages.

[Unreleased]: https://github.com/TheLevti/spork/compare/0.3.0...HEAD
[1.0.0]: https://github.com/TheLevti/spork/releases/1.0.0
[0.3.0]: https://github.com/TheLevti/spork/releases/0.3.0
