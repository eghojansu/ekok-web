<?php

namespace Ekok\Web;

class Container implements \ArrayAccess
{
    protected $values = array();
    protected $keys = array();
    protected $raw = array();
    protected $frozen = array();
    protected $aliases = array();
    protected $simples = array();
    protected $factories;
    protected $protected;

    public function __construct(array $initial = null)
    {
        $this->factories = new \SplObjectStorage();
        $this->protected = new \SplObjectStorage();

        $this->merge($initial ?? array());
    }

    public function offsetExists($key)
    {
        return isset($this->keys[$key]) || (isset($this->aliases[$key]) && isset($this->keys[$this->aliases[$key]]));
    }

    public function offsetGet($key)
    {
        if (!isset($this[$key])) {
            throw new \LogicException("Value not found: $key.");
        }

        $useKey = $this->aliases[$key] ?? $key;

        if (
            isset($this->raw[$useKey])
            || !is_object($this->values[$useKey])
            || isset($this->protected[$this->values[$useKey]])
            || !method_exists($this->values[$useKey], '__invoke')
        ) {
            return $this->values[$useKey];
        }

        if (isset($this->factories[$this->values[$useKey]])) {
            return ($this->values[$useKey])($this);
        }

        $raw = $this->values[$useKey];
        $this->values[$useKey] = $raw($this);
        $this->raw[$useKey] = $raw;
        $this->frozen[$useKey] = true;

        return $this->values[$useKey];
    }

    public function offsetSet($key, $value)
    {
        $useKey = $this->aliases[$key] ?? $key;

        if (isset($this->frozen[$useKey])) {
            throw new \LogicException("Service has been frozen: {$key}.");
        }

        $this->values[$useKey] = $value;
        $this->keys[$useKey] = true;
    }

    public function offsetUnset($key)
    {
        if (isset($this[$key])) {
            $useKey = $this->aliases[$key] ?? $key;

            if (is_object($this->values[$useKey])) {
                unset($this->factories[$this->values[$useKey]], $this->protected[$this->values[$useKey]]);
            }

            unset($this->values[$useKey], $this->frozen[$useKey], $this->raw[$useKey], $this->keys[$useKey]);
        }
    }

    public function alias($key, $alias): Container
    {
        $this->aliases[$alias] = $key;

        return $this;
    }

    public function aliases(): array
    {
        return $this->aliases;
    }

    public function keys(): array
    {
        return array_keys($this->keys);
    }

    public function values(): array
    {
        return $this->values;
    }

    public function raw($key, $default = null)
    {
        return $this->raw[$key] ?? $this->values[$key] ?? $default;
    }

    public function service($key, $callable, $alias = null): Container
    {
        if (!is_object($callable) || !method_exists($callable, '__invoke')) {
            throw new \InvalidArgumentException("Callable is not a Closure or invokable object.");
        }

        $this[$key] = $callable;

        if ($alias) {
            $this->alias($key, $alias);
        }

        return $this;
    }

    public function simple($key, string $class, bool $addAlias = true): Container
    {
        $this[$key] = static function(Container $self) use ($class) {
            return method_exists($class, '__construct') ? new $class($self) : new $class();
        };

        if ($addAlias) {
            $this->alias($key, $class);
        }

        return $this;
    }

    public function protect($callable)
    {
        if (!is_object($callable) || !method_exists($callable, '__invoke')) {
            throw new \InvalidArgumentException("Callable is not a Closure or invokable object.");
        }

        $this->protected->attach($callable);

        return $callable;
    }

    public function factory($callable)
    {
        if (!is_object($callable) || !method_exists($callable, '__invoke')) {
            throw new \InvalidArgumentException("Service is not a Closure or invokable object.");
        }

        $this->factories->attach($callable);

        return $callable;
    }

    public function extend($key, $callable): callable
    {
        if (!isset($this[$key])) {
            throw new \OutOfBoundsException("Service is not defined: {$key}.");
        }

        $useKey = $this->aliases[$key] ?? $key;

        if (isset($this->frozen[$useKey])) {
            throw new \RuntimeException("Service has been frozen: {$key}.");
        }

        if (!is_object($this->values[$useKey]) || !method_exists($this->values[$useKey], '__invoke')) {
            throw new \InvalidArgumentException("Service is not a Closure or invokable object: {$key}.");
        }

        if (isset($this->protected[$this->values[$useKey]])) {
            throw new \LogicException("Service has been protected: {$key}.");
        }

        if (!is_object($callable) || !method_exists($callable, '__invoke')) {
            throw new \InvalidArgumentException("Extension service is not a Closure or invokable object.");
        }

        $factory = $this->values[$useKey];
        $extended = static function (Container $self) use ($callable, $factory) {
            return $callable($factory($self), $self);
        };

        if (isset($this->factories[$factory])) {
            $this->factories->detach($factory);
            $this->factories->attach($extended);
        }

        return $extended;
    }

    public function loadFile(string $file, string $key = null): Container
    {
        if ($key) {
            $this[$key] = Common::loadFile($file);
        } else {
            $this->merge((array) Common::loadFile($file));
        }

        return $this;
    }

    public function loadFiles(array $files): Container
    {
        foreach ($files as $key => $file) {
            $this->loadFile($file, is_numeric($key) ? null : $key);
        }

        return $this;
    }

    public function merge(array $values): Container
    {
        foreach ($values as $key => $value) {
            $this[$key] = $value;
        }

        return $this;
    }
}
