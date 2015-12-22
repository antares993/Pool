<?php

namespace Pool\Tests;

use Pool\PoolInterface;
use Pool\Pool;

class PoolTest extends \PHPUnit_Framework_TestCase
{
    protected function getPool()
    {
        $pool = new Pool();
        $pool->set('foo', function() { return new \stdClass(); });

        return $pool;
    }

    public function testSet()
    {
        $pool = $this->getPool();

        $instance = $pool->get('foo');

        $this->assertEquals('stdClass', get_class($instance));
    }

    public function testMultipleInstances()
    {
        $pool = $this->getPool();

        $instance1 = $pool->get('foo');
        $instance2 = $pool->get('foo');

        $this->assertNotSame($instance1, $instance2);
    }

    public function testDispose()
    {
        $pool = $this->getPool();

        $instance1 = $pool->get('foo');
        $pool->dispose($instance1);
        $instance2 = $pool->get('foo');

        $this->assertSame($instance1, $instance2);
    }

    public function testGetEvent()
    {
        $pool = $this->getPool();

        $count = 0;
        $pool->addEventCallback(
            'foo',
            PoolInterface::EVENT_GET,
            function($instance) use (&$count) { $count++; }
        );

        $instance = $pool->get('foo');

        $this->assertEquals(1, $count);
    }

    public function testDisposeEvent()
    {
        $pool = $this->getPool();

        $count = 0;
        $pool->addEventCallback(
            'foo',
            PoolInterface::EVENT_DISPOSE,
            function($instance) use (&$count) { $count++; }
        );

        $instance = $pool->get('foo');
        $pool->dispose($instance);

        $this->assertEquals(1, $count);
    }

    public function testMultipleEvents()
    {
        $pool = $this->getPool();

        $count = 0;
        $pool->addEventsCallbacks('foo', [
            PoolInterface::EVENT_GET => [function($instance) use (&$count) {
                $count++;
            }],
            PoolInterface::EVENT_DISPOSE => [function($instance) use (&$count) {
                $count++;
            }]
        ]);

        $instance = $pool->get('foo');
        $pool->dispose($instance);

        $this->assertEquals(2, $count);
    }
}
