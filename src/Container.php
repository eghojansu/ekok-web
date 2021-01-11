<?php

namespace Ekok\Web;

class Container
{
    protected $values = array();
    protected $keys = array();
    protected $raw = array();
    protected $frozen = array();
    protected $factories;
    protected $protected;

    public function __construct(array $initial = null)
    {
        $this->factories = new \SplObjectStorage();
        $this->protected = new \SplObjectStorage();

        $this->setAll($initial ?? array());
    }

    public function has($key): bool
    {
        return isset($this->keys[$key]);
    }

    public function get($key, $default = null)
    {
        if (!isset($this->keys[$key])) {
            return $default;
        }

        if (
            isset($this->raw[$key])
            || !is_object($this->values[$key])
            || isset($this->protected[$this->values[$key]])
            || !method_exists($this->values[$key], '__invoke')
        ) {
            return $this->values[$key];
        }

        if (isset($this->factories[$this->values[$key]])) {
            return ($this->values[$key])($this);
        }

        $raw = $this->values[$key];
        $this->values[$key] = $raw($this);
        $this->raw[$key] = $raw;
        $this->frozen[$key] = true;

        return $this->values[$key];
    }

    public function set($key, $value): Container
    {
        if (isset($this->frozen[$key])) {
            throw new \LogicException("Service has been frozen: {$key}.");
        }

        $this->values[$key] = $value;
        $this->keys[$key] = true;

        return $this;
    }

    public function remove($key): Container
    {
        if (isset($this->keys[$key])) {
            if (is_object($this->values[$key])) {
                unset($this->factories[$this->values[$key]], $this->protected[$this->values[$key]]);
            }

            unset($this->values[$key], $this->frozen[$key], $this->raw[$key], $this->keys[$key]);
        }

        return $this;
    }

    public function raw($key, $default = null)
    {
        return $this->raw[$key] ?? $this->values[$key] ?? $default;
    }

    public function hasAll(string ...$keys): bool
    {
        foreach ($keys as $key) {
            if (!$this->has($key)) {
                return false;
            }
        }

        return (bool) $keys;
    }

    public function getAll(array $keys): array
    {
        $data = array();

        foreach ($keys as $alias => $key) {
            if (is_numeric($alias)) {
                $data[$key] = $this->get($key);
            } else {
                $data[$alias] = $this->get($key);
            }
        }

        return $data;
    }

    public function setAll(array $data): Container
    {
        foreach ($data as $key => $value) {
            $this->set($key, $value);
        }

        return $this;
    }

    public function removeAll(string ...$keys): Container
    {
        foreach ($keys as $key) {
            $this->remove($key);
        }

        return $this;
    }

    public function protect($key, $callable): Container
    {
        if (!is_object($callable) || !method_exists($callable, '__invoke')) {
            throw new \InvalidArgumentException("Callable is not a Closure or invokable object.");
        }

        $this->protected->attach($callable);

        return $this->set($key, $callable);
    }

    public function factory($key, $callable): Container
    {
        if (!is_object($callable) || !method_exists($callable, '__invoke')) {
            throw new \InvalidArgumentException("Service is not a Closure or invokable object.");
        }

        $this->factories->attach($callable);

        return $this->set($key, $callable);
    }

    public function extend($key, $callable): Container
    {
        if (!isset($this->keys[$key])) {
            throw new \OutOfBoundsException("Service is not defined: {$key}.");
        }

        if (isset($this->frozen[$key])) {
            throw new \RuntimeException("Service has been frozen: {$key}.");
        }

        if (!is_object($this->values[$key]) || !method_exists($this->values[$key], '__invoke')) {
            throw new \InvalidArgumentException("Service is not a Closure or invokable object: {$key}.");
        }

        if (isset($this->protected[$this->values[$key]])) {
            throw new \LogicException("Service has been protected: {$key}.");
        }

        if (!is_object($callable) || !method_exists($callable, '__invoke')) {
            throw new \InvalidArgumentException("Extension service is not a Closure or invokable object.");
        }

        $factory = $this->values[$key];
        $extended = static function(Container $self) use ($callable, $factory) {
            return $callable($factory($self), $self);
        };

        if (isset($this->factories[$factory])) {
            $this->factories->detach($factory);
            $this->factories->attach($extended);
        }

        return $this->set($key, $extended);
    }

    public function loadFiles(string ...$files): Container
    {
        $load = static function() {
            if (file_exists(func_get_arg(1))) {
                (func_get_arg(0))->setAll((array) require func_get_arg(1));
            }
        };

        foreach ($files as $file) {
            $load($this, $file);
        }

        return $this;
    }
}
