<?php

namespace Pool;

/**
 * This pool uses a static internal pool.
 */
class StaticPool implements PoolInterface
{
    /**
     * @var PoolInterface
     */
    private static $pool = null;

    /**
     * @var boolean
     */
    private $alreadyDefined;

    /**
     * Constructor.
     *
     * @param PoolInterface $pool
     */
    public function __construct(PoolInterface $pool = null)
    {
        $this->alreadyDefined = self::$pool !== null;

        if (!$this->alreadyDefined) {
            if ($pool === null) {
                $pool = new Pool();
            }

            self::$pool = $pool;
        }
    }

    /**
     * Set the static pool.
     *
     * @param PoolInterface $pool [description]
     *
     * @return self
     */
    public function setPool(PoolInterface $pool)
    {
        self::$pool = $pool;
        return $this;
    }

    /**
     * Indicates if the pool has already been defined.
     *
     * @return boolean
     */
    public function isAlreadyDefined()
    {
        return $this->alreadyDefined;
    }

    /**
     * {@inheritdoc}
     */
    public function set($id, $generator, $eventsCallbacks = null)
    {
        self::$pool->set($id, $generator, $eventsCallbacks);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function get($id)
    {
        return self::$pool->get($id);
    }

    /**
     * {@inheritdoc}
     */
    public function dispose($instance)
    {
        return self::$pool->dispose($instance);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setEventsCallbacks($id, $callbacks)
    {
        self::$pool->setEventsCallbacks($id, $callbacks);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setEventCallback($id, $event, $callback)
    {
        self::$pool->setEventCallback($id, $event, $callback);
        return $this;
    }
}
