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
    public function set($id, $generator, array $eventsCallbacks = null)
    {
        $this->generators[$id] = $generator;
        $this->eventsCallbacks[$id] = array();

        if ($eventsCallbacks !== null) {
            $this->addEventsCallbacks($eventsCallbacks);
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
        if (empty($this->generators[$id])) {
            throw new \InvalidArgumentException($id." is not a valid pool id.");
        }

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

        $this->triggerEvent($id, PoolInterface::EVENT_GET, $instance);

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

        $this->triggerEvent($id, PoolInterface::EVENT_CREATE, $instance);

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
        if (!is_object($instance)) {
            throw new \InvalidArgumentException("The pool can only store objects.");
        }

        $hash = spl_object_hash($instance);
        $id = $this->instances[$hash]['id'];

        $this->triggerEvent($id, PoolInterface::EVENT_DISPOSE, $instance);

        $this->instances[$hash]['available'] = true;
        $this->instancesHashes[$id]['availableCount']++;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        foreach ($this->instancesHashes as $id => $hashesList) {
            foreach ($hashesList['hashes'] as $hash) {
                $instance = $this->instances[$hash]['instance'];
                if (!$this->instances[$hash]['available']) {
                    $this->dispose($instance);
                }

                $this->triggerEvent($id, PoolInterface::EVENT_DESTRUCT, $instance);
                unset($this->instances[$hash]['instance']);
            }
        }

        $this->generators = array();
        $this->eventsCallbacks = array();
        $this->instancesHashes = array();
        $this->instances = array();
    }

    /**
     * {@inheritdoc}
     */
    public function addEventsCallbacks($id, array $callbacks)
    {
        foreach ($callbacks as $event => $callbackList) {
            foreach ($callbackList as $callback) {
                $this->addEventCallback($id, $event, $callback);
            }
        }
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addEventCallback($id, $event, $callback)
    {
        if (empty($this->eventsCallbacks[$id][$event])) {
            $this->eventsCallbacks[$id][$event] = array();
        }

        $this->eventsCallbacks[$id][$event][] = $callback;
        return $this;
    }

    /**
     * Trigger an event.
     *
     * @param  string $id
     * @param  string $event
     * @param  object $instance
     */
    private function triggerEvent($id, $event, $instance)
    {
        $events = array(
            PoolInterface::EVENT_GET,
            PoolInterface::EVENT_DISPOSE,
            PoolInterface::EVENT_CREATE,
            PoolInterface::EVENT_DESTRUCT
        );

        if (!in_array($event, $events)) {
            throw new \InvalidArgumentException("Invalid pool event.");
        }

        if (!empty($this->eventsCallbacks[$id][$event])) {
            foreach ($this->eventsCallbacks[$id][$event] as $callback) {
                $callback($instance);
            }
        }
    }
}
