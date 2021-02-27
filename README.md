# Git

[![Build Status](https://github.com/innmind/git/workflows/CI/badge.svg?branch=master)](https://github.com/innmind/git/actions?query=workflow%3ACI)
[![codecov](https://codecov.io/gh/innmind/git/branch/develop/graph/badge.svg)](https://codecov.io/gh/innmind/git)
[![Type Coverage](https://shepherd.dev/github/innmind/git/coverage.svg)](https://shepherd.dev/github/innmind/git)

Abstraction layer to manipulate local git repositories.

Feel free to submit a PR to add other git functionalities.

## Installation

```sh
composer require innmind/git
```

## Usage

```php
use Innmind\Git\{
    Git,
    Repository\Remote\Name,
    Repository\Remote\Url,
    Revision\Branch,
};
use Innmind\Server\Control\ServerFactory;
use Innmind\TimeContinuum\Earth\Clock;
use Innmind\Url\Path;

$git = new Git(ServerFactory::build(), new Clock);
$repository = $git->repository(Path::of('/somewhere/on/the/local/machine'));
$remotes = $repository->init()->remotes();
$remotes->add(new Name('origin'), new Url('git@github.com:Vendor/Repo.git'))
$remotes->push(new Branch('master'));
$repository
    ->branches()
    ->new(new Branch('develop'));
$repository
    ->checkout()
    ->revision(new Branch('develop'));
```

This example initialize a local git repository, declare a github repository as its remote and finally checkout the new branch `develop`.

The offered functionalities goes beyond this single example, check the classes' interface to discover all of them.
