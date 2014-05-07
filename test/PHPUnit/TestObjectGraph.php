<?php namespace Matura\Test\PHPUnit;

use PHPUnit_Framework_TestCase;
use Mockery;

use Matura\Matura;
use Matura\Core\TestContext;

class TestObjectGraph extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function setUp()
    {
        $this->matura_context = new TestContext();

        Matura::reset();
        Matura::start(null, $this->matura_context);
    }

    // A moderately complex test fixture.
    protected function getUserTest()
    {
        return describe('User', function ($ctx) {
            describe('Model', function ($ctx) {
                it('saving', array($ctx->spy, 'saving'));

                it('removal', array($ctx->spy, 'removal'));

                before(array($ctx->spy, 'beforeModel'));
                onceBefore(array($ctx->spy, 'beforeModelOnce'));

                after(array($ctx->spy, 'afterModel'));
                onceAfter(array($ctx->spy, 'afterModelOnce'));
            });

            describe('API', function ($ctx) {
                it('valid api token', array($ctx->spy, 'api_token'));

                before(array($ctx->spy, 'beforeAPI'));
                onceBefore(array($ctx->spy, 'beforeAPIOnce'));

                after(array($ctx->spy, 'afterAPI'));
                onceAfter(array($ctx->spy, 'afterAPIOnce'));
            });

            before(array($ctx->spy,'beforeUser'));
            onceBefore(array($ctx->spy,'beforeUserOnce'));

            after(array($ctx->spy,'afterUser'));
            onceAfter(array($ctx->spy,'afterUserOnce'));
        });
    }

    public function testDescribe()
    {
        $suite = describe('User', function ($ctx) {
            describe('API', function ($ctx) {
                describe('V1', function ($ctx) {
                });
            });

            describe('Model', function ($ctx) {
            });
        });

        // Basic
        $this->assertEquals('User', $suite->path());

        // Block ordering
        $children = $suite->describes();
        $this->assertEquals(2, count($children));
        $this->assertEquals('User:API', $children[0]->path());
        $this->assertEquals('User:Model', $children[1]->path());

        // Nesting
        $v1 = $suite->find('User:API:V1');
        $this->assertEquals('User:API:V1', $v1->path());
        $this->assertEquals(0, count($v1->describes()));
    }

    public function testHookOrdering()
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

        $this->matura_context->spy = $spy;

        $suite = $this->getUserTest();

        $saving_test = $suite->find('User:Model:saving');
        $this->assertInstanceOf('\Matura\Blocks\Methods\TestMethod', $saving_test);
        $this->assertEquals('User:Model:saving', $saving_test->path());

        $saving_test->invoke($this->matura_context);
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

        $this->matura_context->spy = $spy;

        $suite = $this->getUserTest();

        $saving_test = $suite->find('User:Model:saving');
        $saving_test->invoke($this->matura_context);

        $removal_test = $suite->find('User:Model:removal');
        $removal_test->invoke($this->matura_context);
    }
}
