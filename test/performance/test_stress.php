<?php namespace Matura\Test\SelfHosted;

/**
 * Recursively constructs a test Suite.
 */
$gensuite = function ($depth, $tests_per_level, $befores_per_level) use (&$gensuite) {
    if ($depth == 0) {
        return;
    }

    foreach(array('L','R') as $side) {
        describe("Level {$side}$depth", function ($ctx) use (
            $depth,
            $tests_per_level,
            $befores_per_level,
            &$gensuite
        ) {
            for ($i = 0; $i < $tests_per_level; $i++) {
                it("nested $i", function ($ctx) {
                    expect($ctx->value)->to->eql(5);
                });
            }

            for ($i = 0; $i < $befores_per_level; $i++) {
                before(function ($ctx) {
                    $ctx->value = 5;
                });
            }
            $gensuite($depth - 1, $tests_per_level, $befores_per_level);
        });
    }
};

describe('Deep and Branched', function ($ctx) use (&$gensuite) {
    $gensuite(5, 15, 5);
});

describe('Shallow', function ($ctx) use (&$gensuite) {
    $gensuite(1, 10000, 5);
});
