# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [5.0.0] - 2020-03-28

### Changed

- #12: Fix all phpstan errors and general typing improvements.

## [4.0.0] - 2020-02-09

### Changed

- 27d0cc4: Add vendor namespace prefix and improve README.md.

### Fixed

- kriswallsmith/spork#40: Do not lose fork messages after receive call.

## [3.0.0] - 2020-02-02

### Changed

- 4cc83f1: Replace child process shutdown function and improve typings.
- d6ecf04: Rebrand library to `phpfork`.

## [2.0.2] - 2020-02-02

### Changed

- #10: Improved shared memory cleanup code.

## [2.0.1] - 2020-02-02

### Fixed

- #10: Properly detach from and cleanup shared memory.

## [2.0.0] - 2020-02-01

### Changed

- #5: Preserve and restore previous signal handler. Refactored event dispatcher.

### Removed

- #5: Removed the method `addListener` from the `ProcessManager` class. Add
  signal/normal listeners through the event dispatcher on the process manager.

### Fixed

- #7: Fixed missing null terminator handling on shared memory blocks.
- #8: Fixed parent's shutdown function being executed in child processes.

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

[Unreleased]: https://github.com/TheLevti/phpfork/compare/5.0.0...HEAD
[5.0.0]: https://github.com/TheLevti/phpfork/releases/5.0.0
[4.0.0]: https://github.com/TheLevti/phpfork/releases/4.0.0
[3.0.0]: https://github.com/TheLevti/phpfork/releases/3.0.0
[2.0.2]: https://github.com/TheLevti/phpfork/releases/2.0.2
[2.0.1]: https://github.com/TheLevti/phpfork/releases/2.0.1
[2.0.0]: https://github.com/TheLevti/phpfork/releases/2.0.0
[1.0.0]: https://github.com/TheLevti/phpfork/releases/1.0.0
[0.3.0]: https://github.com/TheLevti/phpfork/releases/0.3.0
