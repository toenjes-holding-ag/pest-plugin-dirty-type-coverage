<?php

use Pest\DirtyTypeCoverage\Plugin;
use Symfony\Component\Console\Output\BufferedOutput;

test('output', function () {
    $output = new BufferedOutput;
    $plugin = new class($output) extends Plugin
    {
        public function exit(int $code): never
        {
            throw new Exception($code);
        }
    };

    expect(fn () => $plugin->handleArguments(['--dirty-type-coverage']))->toThrow(Exception::class, 0)
        ->and($output->fetch())->toContain(
            '.. 100%',
            '.. pr12 87',
            '.. co14, pr16, pa18, pa18, rt18 12',
            '.. co14 87',
            '.. rt12 75',
            '.. pa12 87',
        );
});

test('it can output to json', function () {
    $output = new BufferedOutput;
    $plugin = new class($output) extends Plugin
    {
        public function exit(int $code): never
        {
            throw new Exception($code);
        }
    };

    expect(fn () => $plugin->handleArguments(['--type-coverage', '--type-coverage-json=test.json']))->toThrow(Exception::class, 0);

    expect(__DIR__.'/../test.json')->toBeReadableFile();

    expect(file_get_contents(__DIR__.'/../test.json'))->json()->toMatchArray([
        'format' => 'pest',
        'coverage-min' => 0,
        'result' => [
            [
                'file' => 'src/PHPStanAnalyser.php',
                'uncoveredLines' => [],
                'uncoveredLinesIgnored' => [],
                'percentage' => 100,
            ],
            [
                'file' => 'src/TestCaseForTypeCoverage.php',
                'uncoveredLines' => [],
                'uncoveredLinesIgnored' => [],
                'percentage' => 100,
            ],
            [
                'file' => 'src/Contracts/Logger.php',
                'uncoveredLines' => [],
                'uncoveredLinesIgnored' => [],
                'percentage' => 100,
            ],
            [
                'file' => 'src/Plugin.php',
                'uncoveredLines' => [],
                'uncoveredLinesIgnored' => [],
                'percentage' => 100,
            ],
            [
                'file' => 'src/Result.php',
                'uncoveredLines' => [],
                'uncoveredLinesIgnored' => [],
                'percentage' => 100,
            ],
            [
                'file' => 'src/Error.php',
                'uncoveredLines' => [
                    'co15',
                    'co17',
                    'co19',
                    'co21',
                ],
                'uncoveredLinesIgnored' => [],
                'percentage' => 75,
            ],
            [
                'file' => 'src/Support/ConfigurationSourceDetector.php',
                'uncoveredLines' => [],
                'uncoveredLinesIgnored' => [],
                'percentage' => 100,
            ],
            [
                'file' => 'src/Analyser.php',
                'uncoveredLines' => [],
                'uncoveredLinesIgnored' => [],
                'percentage' => 100,
            ],
            [
                'file' => 'src/Logging/NullLogger.php',
                'uncoveredLines' => [],
                'uncoveredLinesIgnored' => [],
                'percentage' => 100,
            ],
            [
                'file' => 'src/Logging/JsonLogger.php',
                'uncoveredLines' => [],
                'uncoveredLinesIgnored' => [],
                'percentage' => 100,
            ],
            [
                'file' => 'tests/Fixtures/Properties.php',
                'uncoveredLines' => ['pr12'],
                'uncoveredLinesIgnored' => [],
                'percentage' => 87,
            ],
            [
                'file' => 'tests/Fixtures/All.php',
                'uncoveredLines' => [
                    'co14',
                    'pr16',
                    'pa18',
                    'pa18',
                    'rt18',
                ],
                'uncoveredLinesIgnored' => [],
                'percentage' => 12,
            ],
            [
                'file' => 'tests/Fixtures/Constants.php',
                'uncoveredLines' => ['co14'],
                'uncoveredLinesIgnored' => [],
                'percentage' => 87,
            ],
            [
                'file' => 'tests/Fixtures/ReturnType.php',
                'uncoveredLines' => ['rt12'],
                'uncoveredLinesIgnored' => [],
                'percentage' => 75,
            ],
            [
                'file' => 'tests/Fixtures/Parameters.php',
                'uncoveredLines' => ['pa12'],
                'uncoveredLinesIgnored' => [],
                'percentage' => 87,
            ],
        ],
        'total' => 88.2,
    ]);

    unlink(__DIR__.'/../test.json');
})->todo();
