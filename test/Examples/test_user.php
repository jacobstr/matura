<?php namespace Matura\Test\Examples;

use Matura\Test\Support\User;
use Matura\Test\Support\Group;

suite('User', function ($test) {
    before(function ($test) {
        $bob = new User();
        $admins = new Group('admins');

        $bob->first_name = 'bob';
        $bob->group = $admins;

        $test->bob = $bob;
        $test->admins = $admins;
    });

    it('should set the bob user', function ($test) {
        expect($test->bob)->to->be->a('Matura\Test\Support\User');
    });

    it('should set the admins group', function ($test) {
        expect($test->admins)->to->be->a('Group');
    });

    xit('should skip this via xit', function ($test) {
    });

    it('should skip this test when invoked', function ($test) {
        skip();
    });

    it('should fail because of a E_NOTICE', function ($test) {
        $arr = array(0);
        $result = $arr[0] + $arr[1];
    });

    // A nested describe block that groups Model / ORM related tests and
    // assertions.
    describe('Model', function ($test) {
        before(function ($test) {
            global $DB;
            // Purge and re-seed the database.
        });

        it('should save bob', function ($test) {

        });
    });
});
