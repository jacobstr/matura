Matura
======

An Rspec / Mocha inspired testing tool for php. Version: alpha.

---

## Installation

You may need to update your composer.json file with `"minimum-stability" : "dev"`.

## Example

From the project folder run: `bin/mat test test/examples`.

```
<?php namespace Matura\Test\Examples;

use Matura\Test\Support\User;
use Matura\Test\Support\Group;

describe('Simple Example', function ($ctx) {
    before(function ($ctx) {
        $bob = new User('bob');
        $admins = new Group('admins');

        $bob->first_name = 'bob';
        $bob->group = $admins;

        $ctx->bob = $bob;
        $ctx->admins = $admins;
    });

    it('should set the bob user', function ($ctx) {
        $ctx->sibling_value = 10;
        expect($ctx->bob)->to->be->a('Matura\Test\Support\User');
    });

    it('should not inherit a sibling\'s context modifications', function ($ctx) {
        expect($ctx->sibling_value)->to->be(null);
    });

    it('should set the admins group', function ($ctx) {
        expect($ctx->admins)->to->be->a('Matura\Test\Support\Group');
    });

    it('should skip this test when invoked', function ($ctx) {
        skip();
    });

    it('should be strict about undefined variables', function ($ctx) {
        $arr = array(0);
        $result = $arr[0] + $arr[1];
    });

    // Nested blocks help organize tests and allow progressive augmentation of
    // test context.
    describe('Inner Block with Before All and Context Clobbering', function ($ctx) {
        before_all(function ($ctx) {
            // Do something costly like purge and re-seed a database.
            $ctx->purged_database = true;
        });

        before(function ($ctx) {
            $ctx->admins = new Group('modified_admins');
        });

        it('should inherit context from outer before blocks', function ($ctx) {
            expect($ctx->bob)->to->be->a('Matura\Test\Support\User');
        });

        it('should shadow context variables from outer contexts if assigned', function ($ctx) {
            expect($ctx->admins->name)->to->eql('modified_admins');
        });
    });
});

```
![Matura Shell Output](docs/sample_shell_output.png)

## Documentation

Unfortunately, for now: the [tests themselves](test/functional).

* [In what order is everything run?](test/functional/test_ordering.php)
* [What is that $ctx parameter?](test/functional/test_context.php)

## The CLI


	./mat test <path> [--filter=] [--grep=]

Tests can be filtered by filename using the `--filter` option. If you wish to filter specific tests within a suite/file, use `--grep`. Matura will be clever enough to run the requisite before/after hooks - hopefully. This is a bit fresh.


## Naive Todo List


* There's currently nothing like PHPUnit's backupGlobals.
* xit / xdescribe and so on are not tested.
* Backtraces annoyingly include calls internal to the framework.
 


