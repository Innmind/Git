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
use Innmind\Url\Path;
use Innmind\TimeContinuum\Clock;
use Innmind\Immutable\Str;

final class Repository
{
    private Binary $binary;
    private Clock $clock;
    private Path $path;
    private ?Branches $branches = null;
    private ?Remotes $remotes = null;
    private ?Checkout $checkout = null;
    private ?Tags $tags = null;

    public function __construct(
        Server $server,
        Path $path,
        Clock $clock
    ) {
        $this->binary = new Binary($server, $path);
        $this->clock = $clock;

        $process = $server
            ->processes()
            ->execute(
                Command::foreground('mkdir')
                    ->withShortOption('p')
                    ->withArgument($path->toString())
            );
        $process->wait();
        $code = $process->exitCode();

        if (!$code->isSuccessful()) {
            throw new PathNotUsable($path->toString());
        }
    }

    public function init(): void
    {
        $output = ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('init')
        );
        $outputStr = Str::of($output->toString());

        if (
            $outputStr->contains('Initialized empty Git repository') ||
            $outputStr->contains('Reinitialized existing Git repository')
        ) {
            return;
        }

        throw new RepositoryInitFailed($output);
    }

    public function head(): Revision
    {
        $output = Str::of(($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('branch')
                ->withOption('no-color')
        )->toString());
        $revision = $output
            ->split("\n")
            ->filter(static function(Str $line): bool {
                return $line->matches('~^\* .+~');
            })
            ->first();

        if ($revision->matches('~\(HEAD detached at [a-z0-9]{7,40}\)~')) {
            return new Hash(
                $revision
                    ->capture('~\(HEAD detached at (?P<hash>[a-z0-9]{7,40})\)~')
                    ->get('hash')
                    ->toString(),
            );
        }

        return new Branch($revision->substring(2)->toString());
    }

    public function branches(): Branches
    {
        return $this->branches ??= new Branches($this->binary);
    }

    public function push(): void
    {
        ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('push')
        );
    }

    public function pull(): void
    {
        ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('pull')
        );
    }

    public function remotes(): Remotes
    {
        return $this->remotes ??= new Remotes($this->binary);
    }

    public function checkout(): Checkout
    {
        return $this->checkout ??= new Checkout($this->binary);
    }

    public function tags(): Tags
    {
        return $this->tags ??= new Tags($this->binary, $this->clock);
    }

    public function add(Path $file): void
    {
        ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('add')
                ->withArgument($file->toString())
        );
    }

    public function commit(Message $message): void
    {
        ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('commit')
                ->withShortOption('m')
                ->withArgument($message->toString())
        );
    }

    public function merge(Branch $branch): void
    {
        ($this->binary)(
            $this
                ->binary
                ->command()
                ->withArgument('merge')
                ->withArgument($branch->toString())
        );
    }
}
