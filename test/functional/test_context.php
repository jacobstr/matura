<?php namespace Matura\Test;

use Matura\Exceptions\Exception;
use Matura\Test\Support\User;
use Matura\Test\Support\Group;

describe('Context', function($ctx) {
    before(function($ctx) {
        $ctx->before_scalar = 5;
        $ctx->user = new User('bob');
    });

    before_all(function($ctx) {
        $ctx->once_before_scalar = 10;
        $ctx->group = new Group('admins');
    });

    it('should return null for an undefined value', function($ctx) {
        expect($ctx->never_set)->to->be(null);
    });

    it('should have a user', function($ctx) {
        expect($ctx->user)->to->be->a('\Matura\Test\Support\User');
        expect($ctx->user->name)->to->eql('bob');
    });

    it('should have a group', function($ctx) {
        expect($ctx->group)->to->be->a('\Matura\Test\Support\Group');
        expect($ctx->group->name)->to->eql('admins');
    });

    it('should have a scalar from the before hook', function($ctx) {
        expect($ctx->before_scalar)->to->be(5);
    });

    it('should have a scalar from the once before hook', function($ctx) {
        expect($ctx->once_before_scalar)->to->be(10);
    });

    describe('Nested, Undefined Values', function($ctx) {
        it('should return null for an undefined value when nested deeper', function($ctx) {
            expect($ctx->another_never_set)->to->be(null);
        });
    });

    describe('Sibling-Of Isolation Block', function($ctx) {
        before_all(function($ctx) {
            $ctx->once_before_scalar = 15;
        });

        before(function($ctx) {
            $ctx->before_scalar = 10;
            $ctx->group = new Group('staff');
        });

        it("should have the clobbered value of `once_before_scalar`", function($ctx) {
            expect($ctx->once_before_scalar)->to->be(15);
        });

        it("should have the clobbered value of `group`", function($ctx) {
            expect($ctx->group->name)->to->be('staff');
        });
    });

    describe('Isolation', function() {
      it("should have the parent `once_before_scalar` and not a sibling's", function($ctx) {
          expect($ctx->once_before_scalar)->to->be(10);
      });

      it("should have the parent `before_scalar` and not a sibling's", function($ctx) {
          expect($ctx->before_scalar)->to->be(5);
      });
    });
});
