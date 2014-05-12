<?php namespace Matura\Test;

use Matura\Exceptions\Exception;
use Matura\Test\Support\User;
use Matura\Test\Support\Group;

/**
 * Tests the construction of our test graph - which relies on a lot of code thats
 * too clever for it's own good.
 */
describe('Matura', function ($ctx) {
    before(function ($ctx) {
        // Officially, nesting suites in this manner is unsupported.
        // A Suite block is automatically created for every test file.
        $ctx->suite = suite('Suite', function () {
            describe('Fixture', function ($ctx) {
                it('TestMethod', function ($test) {

                });
            });
        });
    });

    describe('Suite', function ($ctx) {
        before(function ($ctx) {
            $ctx->describe = $ctx->suite->find('Suite:Fixture');
        });

        it('should be a Suite Block', function ($ctx) {
            expect($ctx->suite)->to->be->an('Matura\Blocks\Suite');
        });

        it('should have a name', function ($ctx) {
            expect($ctx->suite->getName())->to->eql('Suite');
        });

        it('should have a path', function ($ctx) {
            expect($ctx->suite->path())->to->eql('Suite');
        });

        it('should not have a parent Suite block', function ($ctx) {
            expect($ctx->suite->parentBlock())->to->eql(null);
        });
    });

    describe('Describe', function ($ctx) {
        before(function ($ctx) {
            $ctx->describe = $ctx->suite->find('Suite:Fixture');
        });

        it('should be a Describe Block', function ($ctx) {
            expect($ctx->describe)->to->be->a('Matura\Blocks\Describe');
        });

        it('should have the correct parent Block', function ($ctx) {
            expect($ctx->describe->parentBlock())->to->be($ctx->suite);
        });
    });

    describe('TestMethod', function ($ctx) {
        before(function ($ctx) {
            $ctx->test = $ctx->suite->find('Suite:Fixture:TestMethod');
        });

        it('should be a TestMethod', function ($ctx) {
            expect($ctx->test)->to->be->a('Matura\Blocks\Methods\TestMethod');
        });

        it('should have the correct parent Block', function ($ctx) {
            expect($ctx->test->parentBlock())->to->be->a('Matura\Blocks\Describe');
        });

        it('should fail', function ($ctx) {
            throw new Exception('sdfs');
        });
    });
});
