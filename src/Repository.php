<?php
declare(strict_types = 1);

namespace Innmind\Git;

use Innmind\Git\{
    Revision\Hash,
    Revision\Branch,
    Repository\Branches,
    Repository\Remotes,
    Repository\Checkout,
    Repository\Tags,
    Exception\RepositoryInitFailed,
    Exception\PathNotUsable,
    Exception\DomainException
};
use Innmind\Server\Control\{
    Server,
    Server\Command
};
use Innmind\Url\{
    PathInterface,
    Path
};
use Innmind\Immutable\Str;

final class Repository
{
    private $execute;
    private $path;
    private $branches;
    private $remotes;
    private $checkout;
    private $tags;

    public function __construct(Server $server, PathInterface $path)
    {
        $this->execute = new Binary($server, $path);

        $code = $server
            ->processes()
            ->execute(
                Command::foreground('mkdir')
                    ->withShortOption('p')
                    ->withArgument((string) $path)
            )
            ->wait()
            ->exitCode();

        if (!$code->isSuccessful()) {
            throw new PathNotUsable((string) $path);
        }
    }

    public function init(): self
    {
        $output = ($this->execute)('init');
        $outputStr = new Str((string) $output);

        if (
            $outputStr->contains('Initialized empty Git repository') ||
            $outputStr->contains('Reinitialized existing Git repository')
        ) {
            return $this;
        }

        throw new RepositoryInitFailed($output);
    }

    public function head(): Revision
    {
        $output = new Str((string) ($this->execute)('branch --no-color'));
        $revision = $output
            ->split("\n")
            ->filter(static function(Str $line): bool {
                return $line->matches('~^\* .+~');
            })
            ->first();

        if ($revision->matches('~\(HEAD detached at [a-z0-9]{7,40}\)~')) {
            return new Hash(
                (string) $revision
                    ->capture('~\(HEAD detached at (?P<hash>[a-z0-9]{7,40})\)~')
                    ->get('hash')
            );
        }

        return new Branch((string) $revision->substring(2));
    }

    public function branches(): Branches
    {
        return $this->branches ?? $this->branches = new Branches($this->execute);
    }

    public function push(): self
    {
        ($this->execute)('push');

        return $this;
    }

    public function pull(): self
    {
        ($this->execute)('pull');

        return $this;
    }

    public function remotes(): Remotes
    {
        return $this->remotes ?? $this->remotes = new Remotes($this->execute);
    }

    public function checkout(): Checkout
    {
        return $this->checkout ?? $this->checkout = new Checkout($this->execute);
    }

    public function tags(): Tags
    {
        return $this->tags ?? $this->tags = new Tags($this->execute);
    }

    public function add(PathInterface $file): self
    {
        ($this->execute)("add $file");

        return $this;
    }

    public function commit(Message $message): self
    {
        ($this->execute)("commit -m '$message'");

        return $this;
    }

    public function merge(Branch $branch): self
    {
        ($this->execute)("merge $branch");

        return $this;
    }
}
