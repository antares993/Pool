<?php

namespace Pool;

interface PoolInterface
{
    const EVENT_GET = 'get';

    const EVENT_DISPOSE = 'dispose';

    /**
     * Add a generator to the pool.
     * The generator is a function that return a new instance of the wanted object.
     *
     * @param string $id
     * @param callback $generator
     * @param array $eventsCallbacks
     */
    public function set($id, $generator, array $eventsCallbacks = null);

    /**
     * Get an instance from the pool.
     *
     * @param string $id
     *
     * @return object
     */
    public function get($id);

    /**
     * Release given instance in the pool.
     *
     * @param object $instance
     *
     * @return self
     */
    public function dispose($instance);

    /**
     * Set the events callbacks for given id.
     *
     * @param string $id
     * @param array $callbacks
     *
     * @return self
     */
    public function addEventsCallbacks($id, array $callbacks);

    /**
     * Set the callback function applied to given event.
     *
     * @param string $id
     * @param string $event
     * @param callback $callback
     *
     * @return self
     */
    public function addEventCallback($id, $event, $callback);
}
