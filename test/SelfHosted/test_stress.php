<?php namespace Matura\Test\SelfHosted;

// Keeping PSR-2 Happy.
$gensuite = function ($depth, $tests_per_level, $before_per_level) use (&$gensuite) {
    if ($depth == 0) {
        return;
    }

    describe("Level $depth", function ($ctx) use (
        $depth,
        $tests_per_level,
        $before_per_level,
        &$gensuite
    ) {
        for ($i = 0; $i < $tests_per_level; $i++) {
            it("nested $i", function ($ctx) {
                expect(true)->to->eql(true);
            });
        }

        for ($i = 0; $i < $before_per_level; $i++) {
            before(function ($ctx) {
            });
        }
        $gensuite($depth - 1, $tests_per_level, $before_per_level);
    });
};

suite('Fixture', function ($ctx) use (&$gensuite) {
    $gensuite(15, 25, 5);
});
