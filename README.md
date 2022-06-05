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
use Innmind\OperatingSystem\Factory;
use Innmind\Url\Path;

$os = Factory::build();
$git = Git::of($os->control(), $os->clock());
$repository = $git->repository(Path::of('/somewhere/on/the/local/machine'))->match(
    static fn($repository) => $repository,
    static fn() => throw new \RuntimeException('The path does not exist'),
);
$_ = $repository->init()->match(
    static fn() => null, // pass
    static fn() => throw new \RuntimeException('Failed to init the repository'),
);
$remotes = $repository->remotes();
$remotes->add(Name::of('origin'), Url::of('git@github.com:Vendor/Repo.git'))
$remotes->push(Branch::of('master'));
$repository
    ->branches()
    ->new(Branch::of('develop'));
$repository
    ->checkout()
    ->revision(Branch::of('develop'));
```

This example initialize a local git repository, declare a github repository as its remote and finally checkout the new branch `develop`.

The offered functionalities goes beyond this single example, check the classes' interface to discover all of them.
