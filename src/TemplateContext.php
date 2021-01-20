<?php

namespace Ekok\Web;

class TemplateContext
{
    protected $engine;
    protected $parent;
    protected $name;
    protected $filepath;
    protected $data = array();
    protected $search = array();
    protected $sections = array();
    protected $sectioning;
    protected $sectioningMode;

    public function __construct(Template $engine, string $name, array $data = null)
    {
        $this->engine = $engine;
        $this->name = $name;
        $this->data = $data ?? array();
    }

    public function __call($name, $arguments)
    {
        return $this->engine->$name(...$arguments);
    }

    public function getEngine(): Template
    {
        return $this->engine;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFilepath(): string
    {
        return $this->filepath ?? ($this->filepath = $this->engine->findPath($this->name));
    }

    public function render(): string
    {
        $_ = $this;
        $level = ob_get_level();
        $load = static function() use ($_) {
            extract($_->getEngine()->getGlobals());
            extract(func_get_arg(1));
            require func_get_arg(0);
        };
        $clean = static function() use ($level) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
        };

        try {
            ob_start();
            $load($this->getFilepath(), $this->data);
            $content = ob_get_clean();

            if ($this->parent) {
                list($template, $data, $filepath) = $this->parent;
                $parent = $this->engine->createTemplate($template, $data);
                $parent->addData($this->data);
                $parent->merge(compact('content') + $this->sections);
                $parent->filepath = $filepath;
                $content = $parent->render();

                return $this->search ? $parent->replace($content, $this->search) : $content;
            }

            return $content;
        } catch (\Throwable $error) {
            $clean();

            throw $error;
        }
    }

    public function load(string $view, array $data = null): string
    {
        $template = $this->engine->createTemplate($view, $data);
        $template->addData($this->data);
        $template->filepath = '.' === $view[0] ? $this->resolveRelative($view) : null;

        if ($template->getFilepath() === $this->getFilepath()) {
            throw new \LogicException("Recursive view rendering is not supported.");
        }

        return $template->render();
    }

    public function loadIfExists(string $view, array $data = null, string $default = null): ?string
    {
        try {
            return $this->load($view, $data);
        } catch (\Exception $error) {
            return $default;
        }
    }

    public function addData(array $data): TemplateContext
    {
        foreach ($data as $key => $value) {
            $this->data[$key] = $value;
        }

        return $this;
    }

    public function extend(string $parent, array $data = null): void
    {
        $this->parent = array(
            $parent,
            $data ?? array(),
            '.' === $parent[0] ? $this->resolveRelative($parent) : null,
        );
    }

    public function parent(): void
    {
        if (!$this->sectioning) {
            throw new \LogicException("Calling parent when not in section context is forbidden.");
        }

        echo '__parent__';
    }

    public function insert(string $sectionName, string $prefix = 'section__'): void
    {
        $key = $prefix . mt_rand(100, 999) . '_' . mt_rand(100, 999);
        $this->search[$key] = $sectionName;
        echo $key;
    }

    public function exists(string $sectionName = 'content'): bool
    {
        return isset($this->sections[$sectionName]);
    }

    public function merge(array $sections): void
    {
        foreach ($sections as $sectionName => $content) {
            $this->sections[$sectionName] = $content;
        }
    }

    public function section(string $sectionName = 'content', string $default = null): ?string
    {
        return $this->sections[$sectionName] ?? $default;
    }

    public function start(string $sectionName): void
    {
        if ('content' === $sectionName) {
            throw new \LogicException("Section name is reserved: {$sectionName}.");
        }

        if ($this->sectioning) {
            throw new \LogicException("Nested section is not supported.");
        }

        $this->sectioning = $sectionName;

        ob_start();
    }

    public function end(bool $flush = false): void
    {
        if (!$this->sectioning) {
            throw new \LogicException("No section has been started.");
        }

        if (isset($this->sections[$this->sectioning])) {
            $this->sections[$this->sectioning] = strtr($this->sections[$this->sectioning], array(
                '__parent__' => ob_get_clean(),
            ));
        } else {
            $this->sections[$this->sectioning] = ob_get_clean();
        }

        if ($flush) {
            echo $this->sections[$this->sectioning];
        }

        $this->sectioning = null;
    }

    public function endFlush(): void
    {
        $this->end(true);
    }

    protected function replace(string $content, array $search): string
    {
        $replaces = array();

        foreach ($search as $key => $sectionName) {
            $replaces[$key] = $this->sections[$sectionName] ?? null;
        }

        return strtr($content, $replaces);
    }

    protected function resolveRelative(string $view): string
    {
        $relative = dirname($this->getFilepath()) . '/' . $view;

        if (
            !($realpath = realpath($relative))
            && !($realpath = realpath($relative . '.' . $this->engine->getOptions()['extension']))
        ) {
            throw new \LogicException("Relative view not found: '{$view}'.");
        }

        return $realpath;
    }
}
