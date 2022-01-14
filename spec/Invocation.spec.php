<?php

namespace Kavinsky\CloverMerge\Spec;

use Kavinsky\CloverMerge\Command\MergeCommand;
use Kavinsky\CloverMerge\Console\Application;
use Kavinsky\CloverMerge\Invocation;
use Kavinsky\CloverMerge\Accumulator;
use Kavinsky\CloverMerge\Utilities;
use Kavinsky\CloverMerge\Metrics;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\StreamOutput;

/**
 * @phan-closure-scope \Kahlan\Scope
 * @phan-file-suppress PhanParamTooMany
 */
describe('Invocation', function () {
    describe('__construct', function () {
        context('Receives a valid cli argument list.', function () {
            beforeEach(function () {
                allow('is_file')->toBeCalled()->andReturn(true);
                allow('simplexml_load_file')->toBeCalled()->andReturn(
                    new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><coverage/>')
                );
                $this->invocation = new MergeCommand();
            });

            it('produces an invocation instance.', function () {
                expect($this->invocation)->toBeAnInstanceOf('Kavinsky\CloverMerge\Command\MergeCommand');
            });
        });
        context('Receives an empty cli argument list.', function () {
            beforeEach(function () {
                $this->closure = function () {
                    $cmd = new MergeCommand();
                    $cmd->run(new ArgvInput([]), new BufferedOutput());
                };
            });

            it('throws an error.', function () {
                expect($this->closure)->toThrow('At least one input path is required (preferably two).');
            });
        });
        context('Receives a cli argument list missing the output option.', function () {
            beforeEach(function () {
                $this->closure = function () {
                    $cmd = new MergeCommand();

                    $input = new ArgvInput(
                        [
                            'merge',
                            'spec/fixtures/empty-file-with-package.xml',
                            'spec/fixtures/empty-file-without-package.xml'
                        ],
                        $cmd->getDefinition()
                    );

                    $cmd->run($input, new BufferedOutput());
                };
            });

            it('throws an error.', function () {
                expect($this->closure)->toThrow('Missing required option: output');
            });
        });
        context('Receives a cli argument list with an invalid mode option.', function () {
            beforeEach(function () {
                $this->closure = function () {
                    $cmd = new MergeCommand();

                    $input = new ArgvInput(
                        [
                            'merge',
                            '-o',
                            'test',
                            '-m',
                            'bogus',
                            'file'
                        ],
                        $cmd->getDefinition()
                    );

                    $cmd->run($input, new BufferedOutput());
                };
            });

            it('throws an error.', function () {
                expect($this->closure)->toThrow('Merge option must be one of: additive, exclusive or inclusive.');
            });
        });
        context('Receives a cli argument list without any filenames given.', function () {
            beforeEach(function () {
                $this->closure = function () {
                    $cmd = new MergeCommand();

                    $input = new ArgvInput(
                        [
                            'merge',
                            '-o',
                            'test'
                        ],
                        $cmd->getDefinition()
                    );

                    $cmd->run($input, new BufferedOutput());
                };
            });

            it('throws an error.', function () {
                expect($this->closure)->toThrow("At least one input path is required (preferably two).");
            });
        });
        context('Receives a cli argument list containing a list of files to merge.', function () {
            context('Where one doesn\'t exist', function () {
                beforeEach(function () {
                    allow('is_file')->toBeCalled()->andReturn(false);
                    $this->closure = function () {
                        $cmd = new MergeCommand();

                        $input = new ArgvInput(
                            [
                                'merge',
                                '-o',
                                'test',
                                'file',
                                'names'
                            ],
                            $cmd->getDefinition()
                        );

                        $cmd->run($input, new BufferedOutput());
                    };
                });

                it('throws an error.', function () {
                    expect($this->closure)->toThrow("One or more of the given file paths couldn't be found.");
                });
            });

            context('Where one refers to an invalid XML document.', function () {
                beforeEach(function () {
                    allow('is_file')->toBeCalled()->andReturn(true);
                    allow('simplexml_load_file')->toBeCalled()->andReturn(false);
                    $this->closure = function () {
                        $cmd = new MergeCommand();
                        $input = new ArgvInput(
                            [
                                'merge', '-o', 'test', 'file', 'names'
                            ],
                            $cmd->getDefinition()
                        );

                        $cmd->run($input, new BufferedOutput());
                    };
                });

                it('throws an error.', function () {
                    expect($this->closure)->toThrow("Unable to parse one or more of the input files.");
                });
            });
        });
    });
    describe('execute', function () {
        context('With fixtures.', function () {
            context('Executes on all available fixtures.', function () {
                beforeEach(function () {
                    $fixtures = glob(__DIR__.'/fixtures/*.xml');
                    assert(is_array($fixtures));

                    $cmd = new MergeCommand();
                    $input = new ArgvInput(
                        array_merge([
                            'merge',
                            '-o', __DIR__.'/../test_output/fixtures_result.xml'
                        ], $fixtures),
                        $cmd->getDefinition()
                    );

                    $this->closure = function () use ($cmd, $input) {
                        $this->output = new BufferedOutput();
                        $cmd->run($input, $this->output);
                    };


                    allow(Utilities::class)->toReceive('::logWarning')->andReturn();
                    allow('file_put_contents')->toBeCalled();
                });
                it('writes to the output file.', function () {
                    allow('printf')->toBeCalled();
                    expect('file_put_contents')->toBeCalled()->with(
                        __DIR__.'/../test_output/fixtures_result.xml'
                    )->once();
                    $this->closure();
                });

                it('Prints the coverage stats.', function () {
                    $this->closure();
                    expect($this->output->fetch())->toBe(
                        "Files Discovered: 4".PHP_EOL.
                        "Final Coverage: 17/24 (70.83%)".PHP_EOL
                    );
                });
            });
            context('With an unsatisfied minimum coverage threshold.', function () {
                beforeEach(function () {
                    allow(Utilities::class)->toReceive('::logWarning')->andReturn();
                    allow('file_put_contents')->toBeCalled();

                    $cmd = new MergeCommand();
                    $input = new ArgvInput(
                        [
                            'merge',
                            '-o', __DIR__.'/../test_output/fixtures_result.xml',
                            '--enforce', '90.0',
                            __DIR__.'/fixtures/file-without-package.xml'
                        ],
                        $cmd->getDefinition()
                    );

                    $this->closure = function () use ($cmd, $input) {
                        $this->output = new BufferedOutput();
                        return $cmd->run($input, $this->output);
                    };
                });

                it('Returns false.', function () {
                    allow('printf')->toBeCalled();
                    expect($this->closure())->toBe(Command::FAILURE);
                });

                it('Prints the coverage stats.', function () {
                    $this->closure();
                    expect($this->output->fetch())->toBe(
                        "Files Discovered: 1" . PHP_EOL .
                        "Final Coverage: 4/5 (80.00%)" . PHP_EOL .
                        "Coverage is below required threshold (80.00% < 90.00%)." . PHP_EOL
                    );
                });
            });
            context('With a satisfied minimum coverage threshold.', function () {
                beforeEach(function () {
                    allow(Utilities::class)->toReceive('::logWarning')->andReturn();
                    allow('file_put_contents')->toBeCalled();

                    $cmd = new MergeCommand();

                    $input = new ArgvInput([
                        'merge',
                        '-o', __DIR__.'/../test_output/fixtures_result.xml',
                        '-e', '50.0',
                        __DIR__.'/fixtures/file-without-package.xml'
                    ]);
                    $this->closure = function () use ($cmd, $input) {
                        $this->output = new BufferedOutput();
                        return $cmd->run($input, $this->output);
                    };
                });

                it('Returns false.', function () {
                    expect($this->closure())->toBe(Command::SUCCESS);
                });

                it('Prints the coverage stats.', function () {
                    $this->closure();

                    expect($this->output->fetch())->toBe(
                        "Files Discovered: 1" . PHP_EOL .
                        "Final Coverage: 4/5 (80.00%)" . PHP_EOL .
                        "Coverage is above required threshold (80.00% > 50.00%)." . PHP_EOL
                    );
                });
            });
        });
        context('With mocked dependencies.', function () {
            beforeEach(function () {
                allow('is_file')->toBeCalled()->andReturn(true);
                allow('simplexml_load_file')->toBeCalled()->andReturn(
                    new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><coverage/>')
                );
                allow(Accumulator::class)->toReceive('parseAll')->andReturn();
                allow(Accumulator::class)->toReceive('toXml')->andReturn([new \Ds\Map(), new Metrics()]);

                $this->input = new ArgvInput(['prog', '-o', 'test', 'path', 'path2']);
                $this->output = new BufferedOutput();
                $this->closure = function () {
                    $cmd = new MergeCommand();
                    $cmd->run($this->input, $this->output);
                };
            });
            context('Executes an invocation instance where the output file is readable.', function () {
                beforeEach(function () {
                    allow('file_put_contents')->toBeCalled()->andReturn(100);
                });
                it('delegates to Accumulator::parseAll.', function () {
                    $this->closure();
                    expect($this->output->fetch())->toBe(
                        "Files Discovered: 0" . PHP_EOL .
                        "Final Coverage: 0/0 (0.00%)" . PHP_EOL
                    );
                    expect(Accumulator::class)->toReceive('parseAll');
                });
                it('delegates to Accumulator::toXml.', function () {
                    $this->closure();
                    expect($this->output->fetch())->toBe(
                        "Files Discovered: 0" . PHP_EOL .
                        "Final Coverage: 0/0 (0.00%)" . PHP_EOL
                    );
                    expect(Accumulator::class)->toReceive('toXml');
                });
                it('writes to the output file.', function () {
                    expect('file_put_contents')->toBeCalled()->with(
                        'test'
                    )->once();
                    $this->closure();
                    expect($this->output->fetch())->toBe(
                        "Files Discovered: 0" . PHP_EOL .
                        "Final Coverage: 0/0 (0.00%)" . PHP_EOL
                    );
                });
            });
            context('Executes an invocation instance where the output file in unreadable.', function () {
                beforeEach(function () {
                    allow('file_put_contents')->toBeCalled()->andReturn(false);
                });
                it('attempts to write to the file and throws an error.', function () {
                    expect('file_put_contents')->toBeCalled()->with(
                        'test'
                    )->once();
                    expect($this->closure)->toThrow("Unable to write to given output file.");
                });
            });
        });
    });
});
