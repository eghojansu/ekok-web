<?php

namespace Ekok\Web;

class ValidatorContext
{
    protected $field;
    protected $prefix;
    protected $suffix;
    protected $position;
    protected $message;
    protected $data;
    protected $validated;
    protected $value;
    protected $valueSet = false;
    protected $valid = false;
    protected $skipped = false;
    protected $excluded = false;
    protected $numeric = false;
    protected $positional = false;

    public function __construct(string $field, $value, array $data = null, array $validated = null, array $options = null)
    {
        $this->field = $field;
        $this->data = $data;
        $this->validated = $validated;
        $this->setValue($value);
        $this->applyOptions($options);
    }

    public function duplicate(string $field, $value, array $data = null, array $validated = null, array $options = null): ValidatorContext
    {
        $clone = clone $this;

        $clone->field = $field;
        $clone->data = $data;
        $clone->validated = $validated;
        $clone->setValue($value);
        $clone->applyOptions($options);

        return $clone;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    public function getSuffix(): ?string
    {
        return $this->suffix;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function isPositional(): bool
    {
        return $this->positional;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function getValidated(): ?array
    {
        return $this->validated;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value): ValidatorContext
    {
        $this->value = $value;
        $this->valueSet = true;
        $this->valid = true;

        return $this;
    }

    public function isValueSet(): bool
    {
        return $this->valueSet;
    }

    public function freeValueSet(): ValidatorContext
    {
        $this->valueSet = false;
        $this->valid = false;
        $this->skipped = false;
        $this->message = null;

        return $this;
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function valid(): ValidatorContext
    {
        $this->valid = true;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): ValidatorContext
    {
        $this->message = $message;

        return $this;
    }

    public function isSkipped(): bool
    {
        return $this->skipped;
    }

    public function skip(): ValidatorContext
    {
        $this->skipped = true;

        return $this;
    }

    public function isExcluded(): bool
    {
        return $this->excluded;
    }

    public function exclude(): ValidatorContext
    {
        $this->excluded = true;

        return $this;
    }

    public function isNumeric(): bool
    {
        return $this->numeric;
    }

    public function setNumeric(bool $numeric): ValidatorContext
    {
        $this->numeric = $numeric;

        return $this;
    }

    public function checkOther(string $field): bool
    {
        if (null !== $this->position) {
            return ($this->validated && (isset($this->validated[$this->position][$field]) || array_key_exists($field, $this->validated[$this->position])))
                || ($this->data && (isset($this->data[$this->position][$field]) || array_key_exists($field, $this->data[$this->position])));
        }

        return ($this->validated && (isset($this->validated[$field]) || array_key_exists($field, $this->validated)))
            || ($this->data && (isset($this->data[$field]) || array_key_exists($field, $this->data)));
    }

    public function getOther(string $field, $default = null)
    {
        if (null !== $this->position) {
            return $this->validated[$this->position][$field] ?? $this->data[$this->position][$field] ?? $default;
        }

        return $this->validated[$field] ?? $this->data[$field] ?? $default;
    }

    public function getPath(): string
    {
        $elements = array();

        if ($this->prefix) {
            $elements[] = $this->prefix;
        }

        if (null !== $this->position) {
            $elements[] = $this->position;
        }

        $elements[] = $this->field;

        return implode('.', $elements);
    }

    public function getDate($field = null, string $format = null, string $timezone = null): ?\DateTimeInterface
    {
        $toDate = $field ? (is_string($field) && $this->checkOther($field) ? $this->getOther($field) : $field) : $this->getValue();

        if ($toDate instanceof \DateTimeInterface) {
            return $toDate;
        }

        try {
            $toTimezone = $timezone ? new \DateTimeZone($timezone) : null;
            $timestamp = strtotime($toDate);
            $date = $timestamp ? (new \DateTime('now', $toTimezone))->setTimestamp($timestamp) : ($format ? \DateTime::createFromFormat($format, $toDate, $toTimezone) : new \DateTime($toDate, $toTimezone));
        } catch (\Throwable $e) {
            $date = null;
        }

        return $date ?: null;
    }

    public function compareDate($against = null, string $format = null, string $timezone = null): int
    {
        $compareDate = $this->getDate(null, $format, $timezone);
        $againstDate = $against ? $this->getDate($against, $format, $timezone) : new \DateTime('now', $timezone ? new \DateTimeZone($timezone) : null);

        if (null === $compareDate || null === $againstDate) {
            throw new \LogicException("Both date should be valid date: {$this->getPath()}.");
        }

        return $compareDate <=> $againstDate;
    }

    public function getSize(string $field = null)
    {
        $value = $field ? $this->getOther($field) : $this->getValue();

        if (is_array($value)) {
            return count($value);
        }

        return $this->numeric ? 0 + $value : strlen((string) $value);
    }

    protected function applyOptions(array $options = null): void
    {
        $this->prefix = $options['prefix'] ?? null;
        $this->suffix = $options['suffix'] ?? null;
        $this->position = $options['position'] ?? null;
        $this->positional = $options['positional'] ?? false;
    }
}
