<?php namespace Matura\Test\SelfHosted;

use Matura\Runners\TestRunner;

describe('TestRunner', function($ctx) {

  // Tests this directory structure, under a TestRunner.
  //
  // ▾ subfolder/
  //     sub_folder_test.php
  //   another_fake_test.php
  //   fake_test.php

  before(function($ctx) {
      $ctx->fixture_folder = __DIR__.'/../fixtures/fake_folders/';
  });

  describe('Filtering', function ($ctx) {
      describe('Unfiltered', function($ctx) {
          before(function ($ctx) {
              $ctx->runner = new TestRunner($ctx->fixture_folder);
          });

          it('should include all *.php files if no filter is specified', function ($ctx) {
              $files = $ctx->runner->collectFiles();
              expect(iterator_to_array($files))->to->have->length(3);
          });
      });

      describe('Filtered', function($ctx) {
          before(function ($ctx) {
            $ctx->runner = new TestRunner($ctx->fixture_folder, array('filter' => '/\/fake(\w|\.)*$/'));
          });

          it('should only include files that match start with fake.', function ($ctx) {
              $files = $ctx->runner->collectFiles();
              expect(iterator_to_array($files))->to->have->length(1);
          });
      });
  });

  describe('Grepping', function ($ctx) {
      describe('Ungrepped', function ($ctx) {
          before(function ($ctx) {
              $ctx->runner = new TestRunner(
                  $ctx->fixture_folder . '/fake_test.php'
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
              // Level R1:nested 0
              // Level R1:nested 1
              // Level R1:Level L2:nested 0
              // Level R1:Level L2:nested 1
              // Level R1:Level R2:nested 0
              // Level R1:Level R2:nested 1
              expect($result->totalTests())->to->eql(12);
          });
      });

      describe('Grepped `Level L`', function($ctx) {
          before(function ($ctx) {
              $ctx->runner = new TestRunner(
                  $ctx->fixture_folder . '/fake_test.php',
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

      describe('Grepped `Level L1:Level R2`', function($ctx) {
          before(function ($ctx) {
              $ctx->runner = new TestRunner(
                  $ctx->fixture_folder . '/fake_test.php',
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
});
