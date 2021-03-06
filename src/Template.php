<?php

namespace Ekok\Web;

class Template
{
    protected $directories = array();
    protected $functions = array();
    protected $internals = array(
        'chain',
        'escape',
        'esc' => 'escape',
        'e' => 'escape',
    );
    protected $globals = array();
    protected $options = array(
        'extension' => 'php',
        'escapeFlags' => ENT_QUOTES|ENT_HTML401|ENT_SUBSTITUTE,
        'escapeEncoding' => 'UTF-8',
    );

    public function __construct(array $directories = null, array $options = null)
    {
        $this->setDirectories($directories ?? array());
        $this->setOptions($options ?? array());
    }

    public function __call($name, $arguments)
    {
        if (isset($this->functions[$name])) {
            return ($this->functions[$name])(...$arguments);
        }

        if (isset($this->internals[$name]) || (false !== $found = array_search($name, $this->internals))) {
            $call = $this->internals[$name] ?? $this->internals[$found];

            return $this->$call(...$arguments);
        }

        if (function_exists($name)) {
            return $name(...$arguments);
        }

        throw new \BadFunctionCallException("Function is not found at any context: {$name}.");
    }

    public function createTemplate(string $template, array $data = null)
    {
        return new TemplateContext($this, $template, $data);
    }

    public function render(string $template, array $data = null)
    {
        return $this->createTemplate($template, $data)->render();
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options): Template
    {
        $this->options = array_merge($this->options, array_intersect_key($options, $this->options));

        return $this;
    }

    public function getDirectories(): array
    {
        return $this->directories;
    }

    public function setDirectory(string $directory, string $name = null): Template
    {
        $this->directories[$name ?? 'default'][] = Common::fixSlashes($directory, true);

        return $this;
    }

    public function setDirectories(array $directories): Template
    {
        foreach ($directories as $name => $directory) {
            $this->setDirectory($directory, is_numeric($name) ? null : $name);
        }

        return $this;
    }

    public function getGlobals(): array
    {
        return $this->globals;
    }

    public function addGlobal(string $name, $value): Template
    {
        $this->globals[$name] = $value;

        return $this;
    }

    public function setGlobals(array $globals): Template
    {
        foreach ($globals as $name => $value) {
            $this->addGlobal($name, $value);
        }

        return $this;
    }

    public function addFunction(string $name, callable $function): Template
    {
        $this->functions[$name] = $function;

        return $this;
    }

    public function findPath(string $template): string
    {
        list($directories, $file) = $this->getTemplateDirectories($template);

        foreach ($directories as $directory) {
            if (
                file_exists($filepath = $directory . $file)
                || file_exists($filepath = $directory . $file . '.' . $this->options['extension'])
            ) {
                return $filepath;
            }
        }

        throw new \LogicException("Template not found: '{$template}'.");
    }

    public function getTemplateDirectories(string $template): array
    {
        if (false === $pos = strpos($template, ':')) {
            $directories = $this->directories['default'];
            $file = $template;
        } else {
            $directories = $this->directories[substr($template, 0, $pos)] ?? null;
            $file = substr($template, $pos + 1);
        }

        if (!$directories) {
            throw new \LogicException("Directory not exists for template: '{$template}'.");
        }

        return array($directories, $file);
    }

    public function chain($value, string $functions)
    {
        $result = $value;

        foreach (Common::parseExpression($functions) as $function => $arguments) {
            if ('chain' === strtolower($function)) {
                throw new \BadFunctionCallException("Recursive chain is not supported.");
            }

            $result = $this->$function($value, ...$arguments);
        }

        return $result;
    }

    public function escape(?string $data, string $functions = null): string
    {
        $useData = $functions ? $this->chain($data, $functions) : $data;

        return htmlspecialchars($useData ?? '', $this->options['escapeFlags'], $this->options['escapeEncoding']);
    }
}
