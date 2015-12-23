<?php

namespace Pool\Tests;

use Pool\StaticPool;

class StaticPoolTest extends PoolTest
{
    protected function getPool()
    {
        $pool = new StaticPool();
        $pool->set('foo', function() { return new \stdClass(); });

        return $pool;
    }

    public function testIsAlreadyDefined()
    {
        $pool1 = $this->getPool();
        $pool2 = $this->getPool();

        $this->assertTrue($pool2->isAlreadyDefined());
    }
}
