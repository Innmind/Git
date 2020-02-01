# Git

| `develop` |
|-----------|
| [![codecov](https://codecov.io/gh/Innmind/Git/branch/develop/graph/badge.svg)](https://codecov.io/gh/Innmind/Git) |
| [![Build Status](https://github.com/Innmind/Git/workflows/CI/badge.svg)](https://github.com/Innmind/Git/actions?query=workflow%3ACI) |

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
use Innmind\Url\Path;

$git = new Git(ServerFactory::build());
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
