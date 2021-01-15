<?php

namespace Ekok\Web;

trait ExtensibleTrait
{
    protected $extensions = array();
    protected $extensionsMap = array();

    public function __call($name, $arguments)
    {
        $extensions = $this->extensions[$name] ?? null;

        if (!$extensions) {
            throw new \BadMethodCallException("Extension method not exists: {$name}.");
        }

        list($call, $wantThis) = $extensions;

        return $wantThis ? $call($this, ...$arguments) : $call(...$arguments);
    }

    public function getExtensions(): array
    {
        return $this->extensions;
    }

    public function extend(string $name, callable $function, bool $wantThis = false): Fw
    {
        $this->extensions[$name] = array($function, $wantThis);

        return $this;
    }
}
