<?php namespace Matura\Test\SelfHosted;

/**
 * Tests the construction of our test graph - which relies on a lot of code thats
 * too clever for it's own good.
 */
describe('Block Model', function ($ctx) {

    before(function ($ctx) {
        // Officially, nesting suites in this manner is unsupported.
        $ctx->suite = suite('Fixture Suite', function() {
            describe('Fixture', function ($ctx) {
                it('Skipped', function ($test) {
                    // If you need to skip the test partway into execution.
                    skip();
                });
            });
        });
        $ctx->describe = $ctx->suite->find('Fixture Suite:Fixture');
        $ctx->test = $ctx->suite->find('Fixture Suite:Fixture:Skipped');
    });

    describe('Suite', function ($ctx) {
        it('should be a Suite Block', function ($ctx) {
            expect($ctx->suite)->to->be->an('Matura\Blocks\Suite');
        });

        it('should have a name', function ($ctx) {
            expect($ctx->suite->name())->to->eql('Fixture Suite');
        });

        it('should have a path', function ($ctx) {
            expect($ctx->suite->path())->to->eql('Fixture Suite');
        });

        it('should not have a parent Suite block', function ($ctx) {
            expect($ctx->suite->parentBlock())->to->eql(null);
        });

        describe('Describe', function($ctx) {
            it('should be a Describe Block', function($ctx) {
                expect($ctx->describe)->to->be->a('Matura\Blocks\Describe');
            });

            it('should have the correct parent Block', function($ctx) {
                expect($ctx->describe->parentBlock())->to->be($ctx->suite);
            });

            /** @isolated */
            describe('TestMethod', function($ctx) {
                it('should be a TestMethod', function($ctx) {
                    expect($ctx->test)->to->be->a('Matura\Blocks\Methods\TestMethod');
                });

                it('should have the correct parent Block', function($ctx) {
                    expect($ctx->test->parentBlock())->to->be($ctx->describe);
                });
            });
        });
    });
});
