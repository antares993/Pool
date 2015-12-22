<?php

namespace Pool\Tests;

use Pool\StaticPool;

class StaticPoolTest extends PoolTest
{
    protected function getPool()
    {
        $pool = new StaticPool();

        if (!$pool->isAlreadyDefined()) {
            $pool->set('foo', function() { return new \stdClass(); });
        }

        return $pool;
    }
}
