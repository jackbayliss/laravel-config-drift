<?php

namespace JackBayliss\LaravelConfigOutdated\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use JackBayliss\LaravelConfigOutdated\ConfigurationComparator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Finder\Finder;
use Throwable;

#[AsCommand(name: 'config:outdated')]
class LaravelConfigOutdatedCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'config:outdated
                            {file? : Only check the given configuration file}
                            {--json : Output the outdated configuration files as JSON}
                            {--fail : Exit with a non-zero code if any configuration file is outdated}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Determine which published configuration files are out of date';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(Filesystem $files, ConfigurationComparator $comparator)
    {
        $configuration = $this->packageConfigurationFiles();

        if ($name = $this->argument('file')) {
            $name = Str::chopEnd($name, '.php');

            if (! $configuration->has($name)) {
                $this->components->error("Unable to find a published [{$name}] configuration file.");

                return self::FAILURE;
            }

            $configuration = $configuration->only($name);
        }

        $results = $configuration
            ->map(fn ($paths, $name) => $this->compare($files, $comparator, $name, $paths['source'], $paths['published']))
            ->filter();

        $outdated = $results
            ->filter(fn ($result) => $result['missing'] || $result['unknown'])
            ->values();

        if ($this->option('json')) {
            $this->output->writeln($outdated->toJson());
        } elseif ($results->isEmpty()) {
            $this->components->info('No published configuration files were found.');
        } elseif ($outdated->isEmpty()) {
            $this->components->info('All published configuration files are up to date.');
        } else {
            $this->displayOutdated($outdated);
        }

        return $this->option('fail') && $outdated->isNotEmpty()
            ? self::FAILURE
            : self::SUCCESS;
    }

    /**
     * Compare the given published configuration file against its source.
     *
     * @param  string  $name
     * @param  string  $source
     * @param  string  $published
     * @return array|null
     */
    protected function compare(Filesystem $files, ConfigurationComparator $comparator, $name, $source, $published)
    {
        try {
            $original = $files->getRequire($source);
            $current = $files->getRequire($published);
        } catch (Throwable $e) {
            if (! $this->option('json')) {
                $this->components->warn("Unable to load the [{$name}] configuration file: {$e->getMessage()}");
            }

            return null;
        }

        if (! is_array($original) || ! is_array($current)) {
            return null;
        }

        $result = $comparator->compare($original, $current);

        return [
            'file' => $this->relativePath($published),
            'source' => $this->relativePath($source),
            'missing' => array_map(fn ($key) => "{$name}.{$key}", $result['missing']),
            'unknown' => array_map(fn ($key) => "{$name}.{$key}", $result['unknown']),
        ];
    }

    /**
     * Get the package configuration files that have been published.
     *
     * @return Collection
     */
    protected function packageConfigurationFiles()
    {
        if (($configPath = realpath($this->laravel->configPath())) === false) {
            return new Collection;
        }

        return (new Collection(ServiceProvider::pathsToPublish()))
            ->flatMap(fn ($to, $from) => is_dir($from)
                ? $this->filesWithin($from, $to)
                : [$from => $to])
            ->filter(fn ($to, $from) => Str::endsWith($to, '.php')
                && is_file($from)
                && is_file($to)
                && Str::startsWith(realpath($to), $configPath.DIRECTORY_SEPARATOR))
            ->mapWithKeys(fn ($to, $from) => [
                $this->configurationKey($configPath, realpath($to)) => [
                    'source' => $from,
                    'published' => $to,
                ],
            ]);
    }

    /**
     * Get the files within the given published directory.
     *
     * @param  string  $from
     * @param  string  $to
     * @return array
     */
    protected function filesWithin($from, $to)
    {
        return (new Collection(Finder::create()->files()->in($from)))
            ->mapWithKeys(fn ($file) => [
                $file->getPathname() => $to.DIRECTORY_SEPARATOR.$file->getRelativePathname(),
            ])
            ->all();
    }

    /**
     * Get the configuration key for the given configuration file.
     *
     * @param  string  $configPath
     * @param  string  $path
     * @return string
     */
    protected function configurationKey($configPath, $path)
    {
        return Str::of(substr($path, strlen($configPath) + 1))
            ->chopEnd('.php')
            ->replace(DIRECTORY_SEPARATOR, '.')
            ->toString();
    }

    /**
     * Get the given path relative to the base path.
     *
     * @param  string  $path
     * @return string
     */
    protected function relativePath($path)
    {
        $path = str_replace('\\', '/', $path);

        $base = str_replace('\\', '/', $this->laravel->basePath()).'/';

        return Str::startsWith($path, $base) ? Str::after($path, $base) : $path;
    }

    /**
     * Display the outdated configuration files in the console.
     *
     * @return void
     */
    protected function displayOutdated(Collection $outdated)
    {
        $this->newLine();

        $outdated->each(function ($result) {
            $this->components->twoColumnDetail(
                '<fg=green;options=bold>'.$result['file'].'</>',
                $this->summary($result),
            );

            foreach ($result['missing'] as $key) {
                $this->components->twoColumnDetail($key, '<fg=yellow>missing</>');
            }

            foreach ($result['unknown'] as $key) {
                $this->components->twoColumnDetail($key, '<fg=gray>unknown</>');
            }

            $this->newLine();
        });
    }

    /**
     * Get the summary for the given outdated configuration file.
     *
     * @return string
     */
    protected function summary(array $result)
    {
        return (new Collection(['missing' => count($result['missing']), 'unknown' => count($result['unknown'])]))
            ->filter()
            ->map(fn ($count, $type) => "{$count} {$type}")
            ->implode(', ');
    }
}
