<?php

namespace Ekok\Web;

class Event
{
    private $isPropagationStopped = false;
    private $data = null;

    public function __construct($data = null)
    {
        $this->setData($data);
    }

    public function isPropagationStopped(): bool
    {
        return $this->isPropagationStopped;
    }

    public function stopPropagation(): Event
    {
        $this->isPropagationStopped = true;

        return $this;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data): Event
    {
        $this->data = $data;

        return $this;
    }
}
