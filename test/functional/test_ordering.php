<?php namespace Matura\Test;

use Matura\Test\Support\User;
use Matura\Test\Support\Group;
use Matura\Test\Support\Spy;

use Matura\Runners\SuiteRunner;
use Matura\Core\ResultSet;

// Generates various test trees for us.
function gentree($spy, $max_depth, $describes, $methods = array())
{
    $generate_test_block = function($block_method, $path, $index, $spy) {
        array_push($path, $block_method, $index);
        $spy_method_name = implode(".", $path);
        if($block_method == 'it') {
            call_user_func($block_method, $spy_method_name, array($spy, $spy_method_name));
        } else {
            call_user_func($block_method, array($spy, $spy_method_name));
        }
    };

    $generate = function($depth, $path) use (&$generate, $describes, $methods, &$generate_test_block, $spy, $max_depth) {
        foreach($methods as $block_method => $num) {
            foreach(range(1, $num) as $index) {
                $generate_test_block($block_method, $path, $index, $spy);
            }
        }

        if($depth < $max_depth) {
            foreach(range(1, $describes) as $index) {
                describe("describe_$index", function()  use (&$generate, $depth, $path, $index) {
                    $generate($depth+1, array_merge($path, array("describe","$index")));
                });
            }
        }
    };

    return suite('Root',function($ctx) use ($generate) {
        $generate(1, array());
    });
}

describe('Ordering', function($ctx) {
    it('should invoke 1 test and its hooks in the correct order.', function($ctx) {
        $spy = new Spy();

        $suite = gentree($spy, 1, 1, array(
            'before' => 1,
            'after' => 1,
            'before_all' => 1,
            'after_all' => 1,
            'it' => 1
        ));

        $suite_runner = new SuiteRunner($suite, new ResultSet());
        $suite_runner->run();

        expect($spy->invocations)->to->eql(array(
            'before_all.1',
            'before.1',
            'it.1',
            'after.1',
            'after_all.1'
        ));
    });

    it('should invoke 2 tests and its hooks in the correct order.', function($ctx) {
        $spy = new Spy();

        $suite = gentree($spy, 1, 1, array(
            'before' => 1,
            'after' => 1,
            'before_all' => 1,
            'after_all' => 1,
            'it' => 2
        ));

        $suite_runner = new SuiteRunner($suite, new ResultSet());
        $suite_runner->run();

        expect($spy->invocations)->to->eql(array(
            'before_all.1',

            'before.1',
            'it.1',
            'after.1',

            'before.1',
            'it.2',
            'after.1',

            'after_all.1'
        ));
    });

    it('should invoke nested describes and their hooks in the prescribed order.', function($ctx) {
        $spy = new Spy();

        $suite = gentree($spy, 2, 2, array(
            'before' => 1,
            'after' => 1,
            'before_all' => 1,
            'after_all' => 1,
            'it' => 2
        ));

        $suite_runner = new SuiteRunner($suite, new ResultSet());
        $suite_runner->run();

        expect($spy->invocations)->to->eql(array(
            // suite
            'before_all.1',

            // test
            'before.1',
            'it.1',
            'after.1',

            // test
            'before.1',
            'it.2',
            'after.1',

                // First describe
                'describe.1.before_all.1',

                // test
                'before.1', // This might come as a surprise!
                'describe.1.before.1',
                'describe.1.it.1',
                'describe.1.after.1',
                'after.1',

                // test
                'before.1',
                'describe.1.before.1',
                'describe.1.it.2',
                'describe.1.after.1',
                'after.1',

                'describe.1.after_all.1',

                // Second describe
                'describe.2.before_all.1',

                // test
                'before.1',
                'describe.2.before.1',
                'describe.2.it.1',
                'describe.2.after.1',
                'after.1',

                // test
                'before.1',
                'describe.2.before.1',
                'describe.2.it.2',
                'describe.2.after.1',
                'after.1',

                'describe.2.after_all.1',

            'after_all.1'
        ));
    });
});
