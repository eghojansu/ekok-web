<?php

namespace Ekok\Web;

final class ContainerValueIterator implements \Iterator
{
    private $container;
    private $keys;

    public function __construct(Container $container, array $keys)
    {
        $this->container = $container;
        $this->keys = $keys;
    }

    public function valid()
    {
        return null !== key($this->keys);
    }

    public function current()
    {
        return $this->container[current($this->keys)];
    }

    public function key()
    {
        return current($this->keys);
    }

    public function rewind()
    {
        reset($this->keys);
    }

    public function next()
    {
        next($this->keys);
    }
}
