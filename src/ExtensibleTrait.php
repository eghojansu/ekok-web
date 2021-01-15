<?php

namespace Ekok\Web;

trait ExtensibleTrait
{
    protected $extensions = array();
    protected $extensionsMap = array();

    public function __call($name, $arguments)
    {
        if (!$this->checkExtension($name, $extension)) {
            throw new \BadMethodCallException("Extension method not exists: {$name}.");
        }

        list($call, $wantThis) = $this->extensions[$extension];

        return $wantThis ? $call($this, ...$arguments) : $call(...$arguments);
    }

    public function getExtensions(): array
    {
        return $this->extensions;
    }

    public function checkExtension(string $extension, string &$fixed = null): bool
    {
        if (isset($this->extensions[$extension])) {
            $fixed = $extension;

            return true;
        }

        $matches = preg_grep('/^' . preg_quote($extension, '/') . '$/i', array_keys($this->extensions));
        $fixed = end($matches) ?: null;

        return $fixed && isset($this->extensions[$fixed]);
    }

    public function extend(string $name, callable $function, bool $wantThis = false)
    {
        $this->extensions[$name] = array($function, $wantThis);

        return $this;
    }
}
