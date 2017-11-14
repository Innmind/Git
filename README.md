# Git

| `master` | `develop` |
|----------|-----------|
| [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Innmind/Git/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Innmind/Git/?branch=master) | [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Innmind/Git/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/Git/?branch=develop) |
| [![Code Coverage](https://scrutinizer-ci.com/g/Innmind/Git/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/Innmind/Git/?branch=master) | [![Code Coverage](https://scrutinizer-ci.com/g/Innmind/Git/badges/coverage.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/Git/?branch=develop) |
| [![Build Status](https://scrutinizer-ci.com/g/Innmind/Git/badges/build.png?b=master)](https://scrutinizer-ci.com/g/Innmind/Git/build-status/master) | [![Build Status](https://scrutinizer-ci.com/g/Innmind/Git/badges/build.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/Git/build-status/develop) |

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
    Revision\Branch
};
use Innmind\Server\Control\ServerFactory;
use Innmind\Url\Path;

$git = new Git((new ServerFactory)->make());
$repository = $git->repository(new Path('/somewhere/on/the/local/machine'));
$repository
    ->init()
    ->remotes()
    ->add(new Name('origin'), new Url('git@github.com:Vendor/Repo.git'))
    ->push(new Branch('master'));
$repository
    ->branches()
    ->new(new Branch('develop'));
$repository
    ->checkout()
    ->revision(new Branch('develop'));
```

This example initialize a local git repository, declare a github repository as its remote and finally checkout the new branch `develop`.

The offered functionalities goes beyond this single example, check the classes' interface to discover all of them.
