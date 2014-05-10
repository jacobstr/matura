<?php namespace Matura\Test\PHPUnit;

use PHPUnit_Framework_TestCase;
use Mockery;

use Matura\Blocks\Suite;
use Matura\Matura;
use Matura\Core\TestContext;

class TestObjectGraph extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Matura::cleanup();
        Mockery::close();
    }

    public function setUp()
    {
        Matura::init();
    }

    // A moderately complex test fixture.
    protected function getUserTest($spy)
    {
        return suite('User', function ($suite) use ($spy) {

           $suite->spy = $spy;

            describe('Model', function ($suite) {
                it('saving', array($suite->spy, 'saving'));

                it('removal', array($suite->spy, 'removal'));

                before(array($suite->spy, 'beforeModel'));
                onceBefore(array($suite->spy, 'beforeModelOnce'));

                after(array($suite->spy, 'afterModel'));
                onceAfter(array($suite->spy, 'afterModelOnce'));
            });

            describe('API', function ($suite) {
                it('valid api token', array($suite->spy, 'api_token'));

                before(array($suite->spy, 'beforeAPI'));
                onceBefore(array($suite->spy, 'beforeAPIOnce'));

                after(array($suite->spy, 'afterAPI'));
                onceAfter(array($suite->spy, 'afterAPIOnce'));
            });

            before(array($suite->spy,'beforeUser'));
            onceBefore(array($suite->spy,'beforeUserOnce'));

            after(array($suite->spy,'afterUser'));
            onceAfter(array($suite->spy,'afterUserOnce'));
        });
    }

    public function testHookOrdering()
    {
        $spy = Mockery::mock();

        $spy->shouldReceive('beforeUserOnce')->once()->ordered();
        $spy->shouldReceive('beforeUser')->ordered();
        $spy->shouldReceive('beforeModelOnce')->ordered();
        $spy->shouldReceive('beforeModel')->ordered();
        $spy->shouldReceive('saving')->once()->ordered();

        $spy->shouldReceive('afterModel')->ordered();
        $spy->shouldReceive('afterModelOnce')->ordered();
        $spy->shouldReceive('afterUser')->ordered();
        $spy->shouldReceive('afterUserOnce')->ordered();

        $suite = $this->getUserTest($spy);

        $saving_test = $suite->find('User:Model:saving');
        $this->assertInstanceOf('\Matura\Blocks\Methods\TestMethod', $saving_test);
        $this->assertEquals('User:Model:saving', $saving_test->path());

        $saving_test->invokeAll();
    }

    public function testOnceHooks()
    {
        $spy = Mockery::mock();

        $spy->shouldReceive('beforeUserOnce')->ordered();
        $spy->shouldReceive('beforeUser')->ordered();
        $spy->shouldReceive('beforeModelOnce')->ordered();
        $spy->shouldReceive('beforeModel')->ordered();
        $spy->shouldReceive('saving')->once()->ordered();

        $spy->shouldReceive('afterModel')->ordered();
        $spy->shouldReceive('afterModelOnce')->ordered();
        $spy->shouldReceive('afterUser')->ordered();
        $spy->shouldReceive('afterUserOnce')->ordered();

        $spy->shouldReceive('beforeUser')->ordered();
        $spy->shouldReceive('beforeModel')->ordered();
        $spy->shouldReceive('removal')->ordered();
        $spy->shouldReceive('afterUser')->ordered();
        $spy->shouldReceive('afterModel')->ordered();

        $suite = $this->getUserTest($spy);

        $saving_test = $suite->find('User:Model:saving');
        $saving_test->invoke();

        $removal_test = $suite->find('User:Model:removal');
        $removal_test->invoke();
    }
}
