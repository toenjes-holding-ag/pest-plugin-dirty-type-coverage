<?php

declare(strict_types=1);

namespace Pest\DirtyTypeCoverage;

use Pest\Contracts\Plugins\HandlesArguments;
use Pest\DirtyTypeCoverage\Contracts\Logger;
use Pest\DirtyTypeCoverage\Logging\NullLogger;
use Pest\DirtyTypeCoverage\Support\ConfigurationSourceDetector;
use Pest\Exceptions\MissingDependency;
use Pest\Exceptions\NoDirtyTestsFound;
use Pest\Panic;
use Pest\Support\View;
use Pest\TestSuite;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;
use function Termwind\render;
use function Termwind\renderUsing;
use function Termwind\terminal;

/**
 * @internal
 *
 * @final
 */
class Plugin implements HandlesArguments
{

    /** @var array<string> */
    private array $changedFiles = [];

    /**
     * The logger used to output type coverage to a file.
     */
    private Logger $coverageLogger;

    /**
     * Creates a new Plugin instance.
     */
    public function __construct(
        private readonly OutputInterface $output
    )
    {
        $this->coverageLogger = new NullLogger;
    }

    public function handleArguments(array $arguments): array
    {
        $continue = false;

        foreach ($arguments as $argument) {
            if (str_starts_with($argument, '--dirty-type-coverage')) {
                $continue = true;
            }
        }

        if (! $continue) {
            return $arguments;
        }

        $source = ConfigurationSourceDetector::detect();

        if ($source === []) {
            View::render('components.badge', [
                'type' => 'ERROR',
                'content' => 'No source section found. Did you forget to add a `source` section to your `phpunit.xml` file?',
            ]);

            $this->exit(1);
        }

        $files = [];

        $this->loadChangedFiles();

        foreach ($this->changedFiles as $changedFile) {
            $files = Finder::create()->in($source)->name($changedFile)->files()->append($files);
        }

        $this->output->writeln(['Analyses of dirty files:']);
        Analyser::analyse(
            array_keys(iterator_to_array($files)),
            function (Result $result) use (&$totals): void {
                $path = str_replace(TestSuite::getInstance()->rootPath.'/', '', $result->file);

                $truncateAt = max(1, terminal()->width() - 12);

                $uncoveredLines = [];
                $uncoveredLinesIgnored = [];

                $errors = $result->errors;
                $errorsIgnored = $result->errorsIgnored;

                usort($errors, static fn(Error $a, Error $b): int => $a->line <=> $b->line);
                usort($errorsIgnored, static fn(Error $a, Error $b): int => $a->line <=> $b->line);

                foreach ($errors as $error) {
                    $uncoveredLines[] = $error->getShortType().$error->line;
                }
                foreach ($errorsIgnored as $error) {
                    $uncoveredLinesIgnored[] = $error->getShortType().$error->line;
                }

                $color = $uncoveredLines === [] ? 'green' : 'yellow';

                $this->coverageLogger->append($path, $uncoveredLines, $uncoveredLinesIgnored, $result->totalCoverage);

                $uncoveredLines = implode(', ', $uncoveredLines);
                $uncoveredLinesIgnored = implode(', ', $uncoveredLinesIgnored);

                // if there are uncovered lines, add a space before the ignored lines
                // but only if there are ignored lines
                if ($uncoveredLinesIgnored !== '') {
                    $uncoveredLinesIgnored = '<span class="text-gray">'.$uncoveredLinesIgnored.'</span>';
                    if ($uncoveredLines !== '') {
                        $uncoveredLinesIgnored = ' '.$uncoveredLinesIgnored;
                    }
                }

                $totals[] = $percentage = $result->totalCoverage;

                renderUsing($this->output);
                render(<<<HTML
                <div class="flex mx-2">
                    <span class="truncate-{$truncateAt}">{$path}</span>
                    <span class="flex-1 content-repeat-[.] text-gray mx-1"></span>
                    <span class="text-{$color}">$uncoveredLines{$uncoveredLinesIgnored} {$percentage}%</span>
                </div>
                HTML
                );
            },
        );

        $this->coverageLogger->output();

        $this->exit(0);
    }

    public function exit(int $code): never
    {
        exit($code);
    }

    private function loadChangedFiles(): void
    {
        $process = new Process(['git', 'status', '--short', '--', '*.php']);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new MissingDependency('Filter by dirty files', 'git');
        }

        $output = preg_split('/\R+/', $process->getOutput(), flags: PREG_SPLIT_NO_EMPTY);
        assert(is_array($output));

        $dirtyFiles = [];

        foreach ($output as $dirtyFile) {
            $dirtyFiles[substr($dirtyFile, 3)] = trim(substr($dirtyFile, 0, 3));
        }

        $dirtyFiles = array_filter($dirtyFiles, function (string $status) {
            return in_array($status, ['M', 'A', 'R', 'RM', 'D', 'MM'], true);
        });

        $dirtyFiles = array_map(
            fn(string $file, string $status): string => in_array($status, ['R', 'RM'], true)
                ? explode(' -> ', $file)[1]
                : $file, array_keys($dirtyFiles), $dirtyFiles,
        );

        $dirtyFiles = array_values($dirtyFiles);

        if ($dirtyFiles === []) {
            Panic::with(new NoDirtyTestsFound);
        }

        $changedFileNames = array_map('basename', $dirtyFiles);

        $this->changedFiles = $changedFileNames;
    }
}
