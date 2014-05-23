<?php namespace Matura\Test\Examples;

use Matura\Test\Support\User;
use Matura\Test\Support\Group;

suite('User', function ($ctx) {
    before(function ($ctx) {
        $bob = new User();
        $admins = new Group('admins');

        $bob->first_name = 'bob';
        $bob->group = $admins;

        $ctx->bob = $bob;
        $ctx->admins = $admins;
    });

    it('should set the bob user', function ($ctx) {
        expect($ctx->bob)->to->be->a('Matura\Test\Support\User');
    });

    it('should set the admins group', function ($ctx) {
        expect($ctx->admins)->to->be->a('Group');
    });

    xit('should skip this via xit', function ($ctx) {
    });

    it('should skip this test when invoked', function ($ctx) {
        skip();
    });

    it('should fail because of a E_NOTICE', function ($ctx) {
        $arr = array(0);
        $result = $arr[0] + $arr[1];
    });

    // A nested describe block that groups Model / ORM related tests and
    // assertions.
    describe('Model', function ($ctx) {
        before(function ($ctx) {
            global $DB;
            // Purge and re-seed the database.
        });

        it('should save bob', function ($ctx) {

        });
    });
});
