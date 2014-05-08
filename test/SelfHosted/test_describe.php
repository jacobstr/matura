<?php namespace Matura\Test\SelfHosted;

$suite = suite('Fixture', function ($ctx) {
    describe('Test Skipping', function ($ctx) {
        it('should skip me', function ($test) {
            skip();
        });
    });
});

suite('Object Graph', function ($ctx) use ($suite) {
    // I suppose you can do this too ;)
    $ctx->suite = $suite;

    describe('Suite', function ($ctx) {
        it('should have a top level suite', function ($ctx) {
            expect($ctx->suite)->to->be->an('Matura\Blocks\Suite');
        });

        it('should have a name', function ($ctx) {
            expect($ctx->suite->path())->to->eql('Fixture');
        });

        it('should have a path', function ($ctx) {
            expect($ctx->suite->name())->to->eql('Fixture');
        });

        it('should not have a parent block', function ($ctx) {
            expect($ctx->suite->parentBlock())->to->be(null);
        });
    });
});
