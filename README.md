**[Requirements](#requirements)** |
**[Installation](#installation)** |
**[Usage](#usage)**

# thelevti/spork

[![Build Status](https://travis-ci.com/TheLevti/spork.svg?branch=develop)](https://travis-ci.com/TheLevti/spork)

PHP on a fork.

thelevti/spork follows semantic versioning. Read more on [semver.org][1].

----

## Requirements

 - PHP 7.2 or above
 - [php-pcntl][2] to allow this library forking processes.
 - [php-posix][3] to allow this library getting process information.
 - [php-shmop][4] to allow this library doing interprocess communication.

----

## Installation

### Composer

To use this library through [composer][5], run the following terminal command
inside your repository's root folder.

```sh
composer require "thelevti/spork"
```

## Usage

This library uses the namespace `Spork`.

```php
<?php

$manager = new Spork\ProcessManager();
$manager->fork(function () {
    // do something in another process!
    return 'Hello from ' . getmypid();
})->then(function (Spork\Fork $fork) {
    // do something in the parent process when it's done!
    echo "{$fork->getPid()} says '{$fork->getResult()}'\n";
});
```

### Example: Upload images to your CDN

Feed an iterator into the process manager and it will break the job into
multiple batches and spread them across many processes.

```php
<?php

$files = new RecursiveDirectoryIterator('/path/to/images');
$files = new RecursiveIteratorIterator($files);

$manager = new Spork\ProcessManager();
$manager->process($files, function(SplFileInfo $file) {
    // upload this file
});

$manager->wait();
```

### Example: Working with Doctrine DBAL

When working with database connections, there is a known issue regarding parent/child processes.
From http://php.net/manual/en/function.pcntl-fork.php#70721:

> the child process inherits the parent's database connection.
> When the child exits, the connection is closed.
> If the parent is performing a query at this very moment, it is doing it on an already closed connection

This will mean that in our example, we will see a `SQLSTATE[HY000]: General error: 2006 MySQL server has gone away`
exception being thrown in the parent process.

One work-around for this situation is to force-close the DB connection before forking, by using the PRE_FORK event.

```php
<?php

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
    $conn = Doctrine\DBAL\DriverManager::getConnection($params);
    $conn->connect();

    $sql = 'SELECT NOW() AS now';
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $dbResult = $stmt->fetch();
    $conn->close();

    return ['pid' => getmypid(), 'value' => $value, 'result' => $dbResult];
};

// Get DB connection in parent
$parentConnection = Doctrine\DBAL\DriverManager::getConnection($params);
$parentConnection->connect();

$dispatcher = new Spork\EventDispatcher\EventDispatcher();
$dispatcher->addListener(Spork\EventDispatcher\Events::PRE_FORK, function () use ($parentConnection) {
    $parentConnection->close();
});

$manager = new Spork\ProcessManager($dispatcher, null, true);

/** @var Spork\Fork $fork */
$fork = $manager->process($dataArray, $callback, new Spork\Batch\Strategy\ChunkStrategy($forks));
$manager->wait();

$result = $fork->getResult();

// Safe to use now
$sql = 'SELECT NOW() AS now_parent';
$stmt = $parentConnection->prepare($sql);
$stmt->execute();
$dbResult = $stmt->fetch();
$parentConnection->close();
```

[1]: https://semver.org
[2]: https://php.net/manual/en/book.pcntl.php
[3]: https://php.net/manual/en/book.posix.php
[4]: https://php.net/manual/en/book.shmop.php
[5]: https://getcomposer.org
