<?php namespace Matura\Test\SelfHosted;

use Matura\Core\TestRunner;

describe('TestRunner', function($suite) {
  describe('Filtering', function ($suite) {
      describe('Unfiltered', function($suite) {
          before(function ($suite) {
              $suite->runner = new TestRunner(__DIR__);
          });

          it('should only include files that match a regex.', function ($suite) {
              $files = $suite->runner->collectFiles();
              expect(iterator_to_array($files))->to->have->length(3);
          });
      });

      describe('Filtered', function($suite) {
          before(function ($suite) {
              $suite->runner = new TestRunner(__DIR__, array('filter' => '/stress|runner/'));
          });

          it('should only include files that match a regex.', function ($suite) {
              $files = $suite->runner->collectFiles();
              expect(iterator_to_array($files))->to->have->length(2);
          });
      });
  });
});
