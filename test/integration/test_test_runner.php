<?php namespace Matura\Test\SelfHosted;

use Matura\Runners\TestRunner;
use Matura\Runners\SuiteRunner;
use Matura\Core\ResultSet;

use Mockery;

describe('TestRunner', function ($ctx) {

    // Tests this directory structure, under a TestRunner.
    // ▾ fake_folders/
    //   ▾ subfolder/
    //       sub_folder_test.php
    //     another_fake_test.php
    //     fake_test.php
    //     not_a_test.txt

    after(function ($ctx) {
        Mockery::close();
    });

    describe('Filtering', function ($ctx) {
        before(function ($ctx) {
            $ctx->fixture_folder = __DIR__.'/../fixtures/fake_folders/';
        });

        describe('Unfiltered', function ($ctx) {
            before(function ($ctx) {
                $ctx->runner = new TestRunner($ctx->fixture_folder);
            });

            it('should include all *.php files if no filter is specified', function ($ctx) {
                $files = $ctx->runner->collectFiles();
                expect(iterator_to_array($files))->to->have->length(3);
            });
        });

        describe('Filtered', function ($ctx) {
            before(function ($ctx) {
                $ctx->runner = new TestRunner($ctx->fixture_folder, array('filter' => '/\/fake(\w|\.)*$/'));
            });

            it('should only include files that start with `fake`.', function ($ctx) {
                $files = $ctx->runner->collectFiles();
                expect(iterator_to_array($files))->to->have->length(1);
            });
        });
    });

    describe('Grepping', function ($ctx) {
        before(function ($ctx) {
            $ctx->fixture_folder = __DIR__.'/../fixtures/tests/';
            $ctx->test_file = $ctx->fixture_folder . '/dynamically_generated_test.php';
        });

        describe('Ungrepped', function ($ctx) {
            before(function ($ctx) {
                $ctx->runner = new TestRunner($ctx->test_file);
            });

            it('should run the correct tests', function ($ctx) {
                $result = $ctx->runner->run();
                // Level L1:nested 0
                // Level L1:nested 1
                // Level L1:Level L2:nested 0
                // Level L1:Level L2:nested 1
                // Level L1:Level R2:nested 0
                // Level L1:Level R2:nested 1
                // Level R1:nested 0
                // Level R1:nested 1
                // Level R1:Level L2:nested 0
                // Level R1:Level L2:nested 1
                // Level R1:Level R2:nested 0
                // Level R1:Level R2:nested 1
                expect($result->totalTests())->to->eql(12);
            });
        });

        describe('Grepped `Level L`', function ($ctx) {
            before(function ($ctx) {
                $ctx->runner = new TestRunner(
                    $ctx->test_file,
                    array('grep' => '/Level L1/')
                );
            });

            it('should run the correct tests', function ($ctx) {
                $result = $ctx->runner->run();
                // Level L1:nested 0
                // Level L1:nested 1
                // Level L1:Level L2:nested 0
                // Level L1:Level L2:nested 1
                // Level L1:Level R2:nested 0
                // Level L1:Level R2:nested 1
                expect($result->totalTests())->to->eql(6);
            });
        });

        describe('Grepped `Level L1:Level R2`', function ($ctx) {
            before(function ($ctx) {
                $ctx->runner = new TestRunner(
                    $ctx->test_file,
                    array('grep' => '/Level L1:Level R2/')
                );
            });

            it('should run the correct tests', function ($ctx) {
                $result = $ctx->runner->run();
                // Level L1:Level R2:nested 0
                // Level L1:Level R2:nested 1
                expect($result->totalTests())->to->eql(2);
            });
        });
    });

    describe('Error Capture and Reporting', function ($ctx) {
        before(function ($ctx) {
            $ctx->spy = $spy = Mockery::mock()->shouldIgnoreMissing();
            $ctx->listener = Mockery::mock('Matura\Events\Listener')->shouldIgnoreMissing();
            $ctx->suite = suite('Fixture', function ($inner_ctx) use ($spy, $ctx) {
                $ctx->before_all = before_all(array($spy, 'before_all'));
                $ctx->after_all = after_all(array($spy, 'after_all'));
                $ctx->after = after(array($spy, 'after'));
                $ctx->before = before(array($spy, 'before'));
                $ctx->describe = describe('Inner', function ($inner_ctx) use ($spy, $ctx) {
                    $ctx->inner_before_all = before_all(array($spy, 'inner_before_all'));
                    $ctx->inner_after_all = after_all(array($spy, 'inner_after_all'));
                    $ctx->inner_after = after(array($spy, 'inner_after'));
                    $ctx->inner_before = before(array($spy, 'inner_before'));
                    $ctx->test = it('should have a test case', array($spy,'it'));
                });
            });
            $ctx->suite_runner = new SuiteRunner($ctx->suite, new ResultSet());
            $ctx->suite_runner->addListener($ctx->listener);
        });

        describe('At the Suite Level', function ($ctx) {
            it('should capture before_all errors', function ($ctx) {
                $ctx->spy->shouldReceive('before_all')->once()->andThrow('\Exception');
                $ctx->suite_runner->run();
                $failures = $ctx->suite_runner->getResultSet()->getFailures();
                expect($failures)->to->have->length(1);
                expect($failures[0]->getBlock())->to->be($ctx->suite);
            });

            it('should capture after_all errors', function ($ctx) {
                $ctx->spy->shouldReceive('after_all')->once()->andThrow('\Exception');
                $ctx->suite_runner->run();
                $failures = $ctx->suite_runner->getResultSet()->getFailures();
                expect($failures)->to->have->length(1);
                expect($failures[0]->getBlock())->to->be($ctx->suite);
            });
        });

        describe('At the Describe Level', function ($ctx) {
            it('should capture inner before_all errors', function ($ctx) {
                $ctx->spy->shouldReceive('inner_before_all')->once()->andThrow('\Exception');
                $ctx->suite_runner->run();
                $failures = $ctx->suite_runner->getResultSet()->getFailures();
                expect($failures)->to->have->length(1);
                expect($failures[0]->getBlock())->to->be($ctx->describe);
            });

            it('should capture inner after_all errors', function ($ctx) {
                $ctx->spy->shouldReceive('inner_after_all')->once()->andThrow('\Exception');
                $ctx->suite_runner->run();
                $failures = $ctx->suite_runner->getResultSet()->getFailures();
                expect($failures)->to->have->length(1);
                expect($failures[0]->getBlock())->to->be($ctx->describe);
            });
        });

        describe('At the Test Level', function ($ctx) {
            it('should capture test before errors', function ($ctx) {
                $ctx->spy->shouldReceive('inner_before')->once()->andThrow('\Exception');
                $ctx->suite_runner->run();
                $failures = $ctx->suite_runner->getResultSet()->getFailures();
                expect($failures)->to->have->length(1);
                expect($failures[0]->getBlock())->to->be($ctx->test);
            });

            it('should capture test after errors', function ($ctx) {
                $ctx->spy->shouldReceive('inner_after')->once()->andThrow('\Exception');
                $ctx->suite_runner->run();
                $failures = $ctx->suite_runner->getResultSet()->getFailures();
                expect($failures)->to->have->length(1);
                expect($failures[0]->getBlock())->to->be($ctx->test);
            });
        });

        describe('Within Listeners', function ($test) {
            it('should capture listener errors somewhere...', function ($ctx) {
                $ctx->listener->shouldReceive('onTestComplete')->once()->andThrow('\Exception');
                $ctx->suite_runner->run();
                $failures = $ctx->suite_runner->getResultSet()->getFailures();
                expect($failures)->to->have->length(1);
                expect($failures[0]->getBlock())->to->be->a('Matura\Blocks\Block');
            });

        });
    });

    describe('End to End', function ($ctx) {
        before(function ($ctx) {
            $ctx->fixture_folder = __DIR__.'/../fixtures/tests/';
            $ctx->test_file = $ctx->fixture_folder . '/failing_and_skipping_test.php';
            $ctx->runner = new TestRunner($ctx->test_file);
            $ctx->result = $ctx->runner->run();
        });

        it('should run all tests', function ($ctx) {
            expect($ctx->result->totalTests())->to->eql(8);
        });

        it('should skip 2 tests', function ($ctx) {
            expect($ctx->result->totalSkipped())->to->eql(2);
        });

        it('should fail 1 test', function ($ctx) {
            expect($ctx->result->totalFailures())->to->eql(1);
        });

        it('will only count executed assertions', function ($ctx) {
            expect($ctx->result->totalAssertions())->to->eql(5);
        });
    });
});
