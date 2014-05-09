<?php namespace Matura\Test\SelfHosted;

suite('File Filtering', function ($ctx) {
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
    });
});
