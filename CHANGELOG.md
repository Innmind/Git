# Changelog

## [Unreleased]

### Changed

- `Innmind\Git\Git` constructor is now private, use the named constructor `::of()` instead
- `Innmind\Git\Git::repository()` now returns `Innmind\Immutable\Maybe<Innmind\Git\Repository>` instead of throwing an exception
- `Innmind\Git\Git::version()` now returns `Innmind\Immutable\Maybe<Innmind\Git\Repository>` instead of throwing an exception
- `Innmind\Git\Message` constructor is now private, use the named constructors `::of()` or `::maybe()` instead
- `Innmind\Git\Version` constructor is now private, use the named constructor `::of()` instead
- `Innmind\Git\Revision\Branch` constructor is now private, use the named constructors `::of()` or `::maybe()` instead
- `Innmind\Git\Revision\Hash` constructor is now private, use the named constructor `::maybe()` instead
- `Innmind\Git\Repository\Tag\Name` constructor is now private, use the named constructors `::of()` or `::maybe()` instead
- `Innmind\Git\Repository\Remote\Name` constructor is now private, use the named constructors `::of()` or `::maybe()` instead
- `Innmind\Git\Repository\Remote\Url` constructor is now private, use the named constructors `::of()` or `::maybe()` instead
- `Innmind\Git\Repository::init()` now returns `Innmind\Immutable\Maybe<Innmind\Git\Repository>` instead of throwing an exception
- `Innmind\Git\Repository::head()` now returns `Innmind\Immutable\Maybe<Innmind\Git\Revision\Branch|Innmind\Git\Revision\Hash>` instead of throwing an exception
- `Innmind\Git\Repository::push()` now returns `Innmind\Immutable\Maybe<Innmind\Git\Repository>` instead of throwing an exception
- `Innmind\Git\Repository::pull()` now returns `Innmind\Immutable\Maybe<Innmind\Git\Repository>` instead of throwing an exception
- `Innmind\Git\Repository::add()` now returns `Innmind\Immutable\Maybe<Innmind\Git\Repository>` instead of throwing an exception
- `Innmind\Git\Repository::commit()` now returns `Innmind\Immutable\Maybe<Innmind\Git\Repository>` instead of throwing an exception
- `Innmind\Git\Repository::merge()` now returns `Innmind\Immutable\Maybe<Innmind\Git\Repository>` instead of throwing an exception
- `Innmind\Git\Repository\Branches::new()` now returns `Innmind\Immutable\Maybe<Innmind\Immutable\SideEffect>` instead of throwing an exception
- `Innmind\Git\Repository\Branches::newOrphan()` now returns `Innmind\Immutable\Maybe<Innmind\Immutable\SideEffect>` instead of throwing an exception
- `Innmind\Git\Repository\Branches::delete()` now returns `Innmind\Immutable\Maybe<Innmind\Immutable\SideEffect>` instead of throwing an exception
- `Innmind\Git\Repository\Branches::forceDelete()` now returns `Innmind\Immutable\Maybe<Innmind\Immutable\SideEffect>` instead of throwing an exception
- `Innmind\Git\Repository\Checkout::file()` now returns `Innmind\Immutable\Maybe<Innmind\Immutable\SideEffect>` instead of throwing an exception
- `Innmind\Git\Repository\Checkout::revision()` now returns `Innmind\Immutable\Maybe<Innmind\Immutable\SideEffect>` instead of throwing an exception
- `Innmind\Git\Repository\Remote::prune()` now returns `Innmind\Immutable\Maybe<Innmind\Immutable\SideEffect>` instead of throwing an exception
- `Innmind\Git\Repository\Remote::setUrl()` now returns `Innmind\Immutable\Maybe<Innmind\Immutable\SideEffect>` instead of throwing an exception
- `Innmind\Git\Repository\Remote::addUrl()` now returns `Innmind\Immutable\Maybe<Innmind\Immutable\SideEffect>` instead of throwing an exception
- `Innmind\Git\Repository\Remote::deleteUrl()` now returns `Innmind\Immutable\Maybe<Innmind\Immutable\SideEffect>` instead of throwing an exception
- `Innmind\Git\Repository\Remote::push()` now returns `Innmind\Immutable\Maybe<Innmind\Immutable\SideEffect>` instead of throwing an exception
- `Innmind\Git\Repository\Remote::delete()` now returns `Innmind\Immutable\Maybe<Innmind\Immutable\SideEffect>` instead of throwing an exception
- `Innmind\Git\Repository\Remotes::remove()` now returns `Innmind\Immutable\Maybe<Innmind\Immutable\SideEffect>` instead of throwing an exception
- `Innmind\Git\Repository\Tags::push()` now returns `Innmind\Immutable\Maybe<Innmind\Immutable\SideEffect>` instead of throwing an exception
- `Innmind\Git\Repository\Tags::add()` now returns `Innmind\Immutable\Maybe<Innmind\Immutable\SideEffect>` instead of throwing an exception
- `Innmind\Git\Repository\Tags::sign()` now returns `Innmind\Immutable\Maybe<Innmind\Immutable\SideEffect>` instead of throwing an exception

### Removed

- Support for PHP `7.4` and `8.0`
- `Innmind\Git\Exception\CommandFailed`
- `Innmind\Git\Exception\PathNotUsable`
- `Innmind\Git\Exception\RepositoryInitFailed`
- `Innmind\Git\Exception\RuntimeException`
- `Innmind\Git\Revision`
