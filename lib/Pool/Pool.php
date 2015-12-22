<?php

namespace Pool;

class Pool implements PoolInterface
{
    /**
     * ['id' => function($instance) {...}]
     * @var array
     */
    private $generators;

    /**
     * ['id' => ['event_name' => function($instance) {...}]]
     * @var array
     */
    private $eventsCallbacks;

    /**
     * ['id' => ['availableCount' => 0, 'hashes' => ['hash1', 'hash2']]]
     * @var array
     */
    private $instancesHashes;

    /**
     * ['hash' => ['id' => $id, 'available' => true, 'instance' => $instance]]
     * @var array
     */
    private $instances;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->generators = array();
        $this->eventsCallbacks = array();
        $this->instancesHashes = array();
        $this->instances = array();
    }

    /**
     * {@inheritdoc}
     */
    public function set($id, $generator, $eventsCallbacks = null)
    {
        $this->generators[$id] = $generator;

        if ($eventsCallbacks !== null) {
            $this->setEventsCallbacks($eventsCallbacks);
        }

        $this->instancesHashes[$id] = array(
            'availableCount' => 0,
            'hashes' => array()
        );

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function get($id)
    {
        if ($this->instancesHashes[$id]['availableCount'] === 0) {
            $hash = $this->createInstance($id);
            $instance = $this->instances[$hash]['instance'];
        }

        foreach ($this->instancesHashes[$id]['hashes'] as $hash) {

            if ($this->instances[$hash]['available']) {
                $this->instancesHashes[$id]['availableCount']--;

                $instance = $this->instances[$hash]['instance'];
                break;
            }
        }

        if (!empty($this->eventsCallbacks[$id][PoolInterface::EVENT_GET])) {
            $this->eventsCallbacks[$id][PoolInterface::EVENT_GET]($instance);
        }

        return $instance;
    }

    /**
     * Create an instance of the object with given id and return its hash.
     *
     * @param string $id
     *
     * @return string
     */
    private function createInstance($id)
    {
        $instance = $this->generators[$id]();
        $hash = spl_object_hash($instance);

        $this->instances[$hash] = array(
            'id' => $id,
            'available' => false,
            'instance' => $instance
        );

        $this->instancesHashes[$id]['hashes'][] = $hash;

        return $hash;
    }

    /**
     * {@inheritdoc}
     */
    public function dispose($instance)
    {
        $hash = spl_object_hash($instance);
        $id = $this->instances[$hash]['id'];

        if (!empty($this->eventsCallbacks[$id][PoolInterface::EVENT_DISPOSE])) {
            $this->eventsCallbacks[$id][PoolInterface::EVENT_DISPOSE]($instance);
        }

        $this->instances[$hash]['available'] = true;
        $this->instancesHashes[$id]['availableCount']++;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setEventsCallbacks($id, $callbacks)
    {
        foreach ($callbacks as $event => $callback) {
            $this->setEventCallback($id, $event, $callback);
        }
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setEventCallback($id, $event, $callback)
    {
        $this->eventsCallbacks[$id][$event] = $callback;
        return $this;
    }
}
