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
        return suite('User', function ($ctx) use ($spy) {

            describe('Model', function ($ctx) use ($spy) {
                it('saving', array($spy, 'saving'));

                it('removal', array($spy, 'removal'));

                before(array($spy, 'beforeModel'));
                onceBefore(array($spy, 'beforeModelOnce'));

                after(array($spy, 'afterModel'));
                onceAfter(array($spy, 'afterModelOnce'));
            });

            describe('API', function ($ctx) use ($spy) {
                it('valid api token', array($spy, 'api_token'));

                before(array($spy, 'beforeAPI'));
                onceBefore(array($spy, 'beforeAPIOnce'));

                after(array($spy, 'afterAPI'));
                onceAfter(array($spy, 'afterAPIOnce'));
            });

            before(array($spy,'beforeUser'));
            onceBefore(array($spy,'beforeUserOnce'));

            after(array($spy,'afterUser'));
            onceAfter(array($spy,'afterUserOnce'));
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

        $ctx = $this->getUserTest($spy);

        $saving_test = $ctx->find('User:Model:saving');
        $this->assertInstanceOf('\Matura\Blocks\Methods\TestMethod', $saving_test);
        $this->assertEquals('User:Model:saving', $saving_test->path());

        $saving_test->invokeAll();
    }

    public function testOnceHooks()
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

        $spy->shouldReceive('beforeUser')->ordered();
        $spy->shouldReceive('beforeModel')->ordered();
        $spy->shouldReceive('removal')->ordered();
        $spy->shouldReceive('afterUser')->ordered();
        $spy->shouldReceive('afterModel')->ordered();

        $ctx = $this->getUserTest($spy);

        $saving_test = $ctx->find('User:Model:saving');
        $saving_test->invoke();

        $removal_test = $ctx->find('User:Model:removal');
        $removal_test->invoke();
    }
}
