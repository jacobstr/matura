<?php namespace Matura\Test\SelfHosted;

use Matura\Runners\TestRunner;

describe('TestRunner', function($ctx) {

  before(function($ctx) {
      $ctx->fixture_folder = __DIR__.'/../Support/';
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
              $ctx->runner = new TestRunner($ctx->fixture_folder, array('filter' => '/Group/'));
          });

          it('should only include files that match a regex.', function ($ctx) {
              $files = $ctx->runner->collectFiles();
              expect(iterator_to_array($files))->to->have->length(1);
          });
      });
  });
});
