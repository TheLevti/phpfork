# thelevti/phpfork

**[Requirements](#requirements)** |
**[Installation](#installation)** |
**[Usage](#usage)**

[![Build Status][1]][2]

A simple library to make forking a processes as easy as possible.

`thelevti/phpfork` follows semantic versioning. Read more on [semver.org][3].

----

## Requirements

- PHP 7.2 or above
- [php-pcntl][4] to allow this library forking processes.
- [php-posix][5] to allow this library getting process information.
- [php-shmop][6] to allow this library doing interprocess communication.

----

## Installation

### Composer

To use this library with [composer][7], run the following terminal command
inside your repository's root folder.

```bash
composer require "thelevti/phpfork"
```

## Usage

This library uses the namespace `TheLevti\phpfork`.

### Example: Basic process forking

```php
<?php

use TheLevti\phpfork\Fork;
use TheLevti\phpfork\ProcessManager;
use TheLevti\phpfork\SharedMemory;

$manager = new ProcessManager();
$fork = $manager->fork(function (SharedMemory $shm) {
    // Do something in a forked process!
    return 'Hello from ' . posix_getpid();
})->then(function (Fork $fork) {
    // Do something in the parent process when the fork is done!
    echo "{$fork->getPid()} says '{$fork->getResult()}'\n";
});

$manager->wait();
```

### Example: Upload images to a CDN

Feed an iterator into the process manager and it will break the job into
multiple batches and spread them across many processes.

```php
<?php

use TheLevti\phpfork\ProcessManager;
use SplFileInfo;

$files = new RecursiveDirectoryIterator('/path/to/images');
$files = new RecursiveIteratorIterator($files);

$manager = new ProcessManager();
$batchJob = $manager->process($files, function(SplFileInfo $file) {
    // upload this file
});

$manager->wait();
```

### Example: Working with Doctrine DBAL

When working with database connections, there is a known issue regarding
parent/child processes. See php doc for [pcntl_fork][8]:

> The reason for the MySQL "Lost Connection during query" issue when forking is
the fact that the child process inherits the parent's database connection. When
the child exits, the connection is closed. If the parent is performing a query
at this very moment, it is doing it on an already closed connection, hence the
error.

This will mean that in our example, we will see a `SQLSTATE[HY000]: General
error: 2006 MySQL server has gone away` exception being thrown in the parent
process.

One work-around for this situation is to force-close the DB connection before
forking, by using the `PRE_FORK` event.

```php
<?php

use Doctrine\DBAL\DriverManager;
use TheLevti\phpfork\Batch\Strategy\ChunkStrategy;
use TheLevti\phpfork\EventDispatcher\Events;
use TheLevti\phpfork\EventDispatcher\SignalEventDispatcher;
use TheLevti\phpfork\ProcessManager;

$params = array(
    'dbname'    => '...',
    'user'      => '...',
    'password'  => '...',
    'host'      => '...',
    'driver'    => 'pdo_mysql',
);

$forks = 4;
$dataArray = range(0, 15);

$callback = function ($value) use ($params) {
    // Child process acquires its own DB connection
    $conn = DriverManager::getConnection($params);
    $conn->connect();

    $sql = 'SELECT NOW() AS now';
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $dbResult = $stmt->fetch();
    $conn->close();

    return ['pid' => getmypid(), 'value' => $value, 'result' => $dbResult];
};

// Get DB connection in parent
$parentConnection = DriverManager::getConnection($params);
$parentConnection->connect();

$dispatcher = new SignalEventDispatcher();
$dispatcher->addListener(Events::PRE_FORK, function () use ($parentConnection) {
    $parentConnection->close();
});

$manager = new ProcessManager($dispatcher, null, true);

/** @var TheLevti\phpfork\Fork $fork */
$fork = $manager->process($dataArray, $callback, new ChunkStrategy($forks));
$manager->wait();

$result = $fork->getResult();

// Safe to use now
$sql = 'SELECT NOW() AS now_parent';
$stmt = $parentConnection->prepare($sql);
$stmt->execute();
$dbResult = $stmt->fetch();
$parentConnection->close();
```

[1]: https://travis-ci.com/TheLevti/phpfork.svg?branch=master
[2]: https://travis-ci.com/TheLevti/phpfork
[3]: https://semver.org
[4]: https://php.net/manual/en/book.pcntl.php
[5]: https://php.net/manual/en/book.posix.php
[6]: https://php.net/manual/en/book.shmop.php
[7]: https://getcomposer.org
[8]: http://php.net/manual/en/function.pcntl-fork.php#70721
