<?php

use Peridot\Console\Environment;
use Evenement\EventEmitterInterface;
use Peridot\Reporter\CodeCoverageReporters;
use Peridot\Reporter\CodeCoverage\AbstractCodeCoverageReporter;

error_reporting(-1);

return function (EventEmitterInterface $emitter) {
    $coverage = new CodeCoverageReporters($emitter);
    $coverage->register();

    // set the default path
    $emitter->on('peridot.start', function (Environment $environment) {
        $environment->getDefinition()->getArgument('path')->setDefault('specs');
        $environment->getDefinition()->getOption('reporter')->setDefault(array(
            'spec',
            'html-code-coverage',
        ));
    });

    $emitter->on('code-coverage.start', function (AbstractCodeCoverageReporter $reporter) {
        $reporter->addDirectoryToWhitelist(__DIR__ . '/src');
    });
};
