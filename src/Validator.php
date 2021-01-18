<?php

namespace Ekok\Web;

class Validator
{
    const MESSAGE_DEFAULT = 'This value is not valid.';

    protected $rules = array();
    protected $messages = array(
        'accepted' => 'This value should be accepted.',
        'after' => 'This value should be after {argument_0}.',
        'after_or_equal' => 'This value should be after or equal to {argument_0}.',
        'alpha' => 'This value should be alpha characters.',
        'alnum' => 'This value should be alpha or numeric characters.',
        'array' => 'This value should be an array.',
        'before' => 'This value should be before {argument_0}.',
        'before_or_equal' => 'This should be before or equal to {argument_0}.',
        'between' => 'This value should between {argument_0} and {argument_1}.',
        'boolean' => 'This value should be boolean.',
        'confirmed' => 'This value should be confirmed.',
        'date' => 'This value should be a valid date.',
        'date_equals' => 'This value should be equal to date {argument_0}.',
        'date_format' => 'This value is not valid date format.',
        'different' => 'This value should be different with {argument_0}.',
        'digits' => 'This value should be digits characters.',
        'digits_between' => 'This value should between {argument_0} and {argument_1} in length.',
        'distinct' => 'This value is not unique.',
        'email' => 'This value is not a valid email.',
        'ends_with' => 'This value should ends with {arguments}.',
        'gt' => 'This value should greater than {argument_0}.',
        'gte' => 'This value should greater than or equals {argument_0}.',
        'in' => 'This value is not an option.',
        'in_array' => 'This value should be in {argument_0}.',
        'integer' => 'This value should be an integer.',
        'ip' => 'This value should be a valid IP address.',
        'ip4' => 'This value should be a valid IP4 address.',
        'ip6' => 'This value should be a valid IP6 address.',
        'json' => 'This value should be a valid json.',
        'lt' => 'This value should be less than {argument_0}.',
        'lte' => 'This value should be less than or equals {argument_0}.',
        'match' => 'This value should match with expected pattern.',
        'max' => 'This value should not greater than {argument_0}.',
        'min' => 'This value should not less than {argument_0}.',
        'not_in' => 'This value is not an option.',
        'not_match' => 'This value should match with expected pattern.',
        'numeric' => 'This value should be numeric.',
        'required' => 'This value should not be blank.',
        'required_if' => 'This value should not be blank.',
        'required_unless' => 'This value should not be blank.',
        'same' => 'This value should same with {argument_0}.',
        'size' => 'This value should be {argument_0} in size.',
        'starts_with' => 'This value should starts with {arguments}.',
        'string' => 'This value should be a string.',
        'url' => 'This value should be an URL.',
    );

    public function __construct(array $rules = null, array $messages = null)
    {
        $this->setMessages($messages ?? array());
        $this->setRules($rules ?? array());
    }

    public function getMessages(): array
    {
        return $this->messages;
    }

    public function setMessage(string $rule, string $message): Validator
    {
        $this->messages[$rule] = $message;

        return $this;
    }

    public function setMessages(array $messages): Validator
    {
        foreach ($messages as $rule => $message) {
            $this->setMessage($rule, $message);
        }

        return $this;
    }

    public function getRules(): array
    {
        return $this->rules;
    }

    public function setRule(string $rule, callable $callable, string $message = null): Validator
    {
        $this->rules[$rule] = $callable;

        if ($message) {
            $this->setMessage($rule, $message);
        }

        return $this;
    }

    public function setRules(array $rules): Validator
    {
        foreach ($rules as $rule => $callable) {
            $this->setRule($rule, $callable);
        }

        return $this;
    }

    public function validate(array $rules, array $data = null, array $options = null): array
    {
        $violations = array();
        $validated = null;
        $useData = $data ?? array();
        $messages = $options['messages'] ?? null;
        $skipOnError = $options['skipOnError'] ?? false;

        foreach ($rules as $field => $fieldRules) {
            list($useField, $suffix, $positional) = $this->splitField($field);

            $customMessage = $messages[$field] ?? null;
            $context = new ValidatorContext($useField, Common::getDataValue($useData, $useField), $data, $validated, array(
                'suffix' => $suffix,
                'positional' => $positional,
            ));
            $fieldViolations = $this->validateField($fieldRules, $context, $customMessage);

            if ($fieldViolations) {
                $violations = array_merge_recursive($violations, $fieldViolations);

                if ($skipOnError) {
                    break;
                } else {
                    continue;
                }
            }

            if (!$context->isExcluded()) {
                $validated[$context->getField()] = $context->getValue();
            }
        }

        return array(
            'success' => !$violations,
            'data' => $validated,
            'violations' => $violations,
        );
    }

    protected function validateField($fieldRules, ValidatorContext $context, string $customMessage = null): array
    {
        $rules = is_array($fieldRules) ? $fieldRules : Common::parseExpression($fieldRules);
        $violations = array();

        if ($context->getSuffix() || $context->isPositional()) {
            $data = (array) ($context->getValue() ?: array(null));
            $values = array();
            $field = null;
            $prefix = $context->getPath();
            $suffix = null;

            if ($context->getSuffix()) {
                list($field, $suffix) = $this->splitField($context->getSuffix());
            }

            foreach ($data as $position => $value) {
                $useField = $field;
                $useValue = $value;
                $usePosition = $position;

                if (!$useField) {
                    $useField = "{$position}";
                    $useValue = $data;
                    $usePosition = null;
                }

                $newContext = $context->duplicate($useField, Common::getDataValue((array) $useValue, $useField), $data, $values, array(
                    'prefix' => $prefix,
                    'suffix' => $suffix,
                    'position' => $usePosition,
                ));
                $subViolations = $this->validateField($fieldRules, $newContext, $customMessage);

                if ($subViolations) {
                    $violations = array_merge_recursive($violations, $subViolations);
                } elseif (!$newContext->isExcluded()) {
                    if ($field) {
                        $values[$position][$newContext->getField()] = $newContext->getValue();
                    } else {
                        $values[$position] = $newContext->getValue();
                    }
                }
            }

            if (!$violations) {
                $context->setValue($values);
            }

            return $violations;
        }

        foreach ($rules as $rule => $arguments) {
            $result = $this->execute($rule, $arguments, $context->freeValueSet());

            if ($context->isSkipped()) {
                break;
            }

            if (false === $result || !$context->valid()) {
                $message =& Common::getDataRef($violations, $context->getPath());
                $message[] = $context->getMessage() ?? $this->buildMessage($customMessage, $rule, $arguments, $context);

                break;
            }

            if (true !== $result && !$context->isValueSet()) {
                $context->setValue($result);
            }
        }

        return $violations;
    }

    protected function splitField(string $field): array
    {
        if (false === $pos = strpos($field, '*')) {
            return array($field, null, false);
        }

        return array(substr($field, 0, $pos - 1), substr($field, $pos + 2), '*' === substr($field, -1));
    }

    protected function execute(string $rule, ?array $arguments, ValidatorContext $context)
    {
        $call = $this->rules[$rule] ?? array($this, "_rule_{$rule}");

        if (!is_callable($call)) {
            throw new \LogicException("Rule not exists: {$rule}.");
        }

        return $arguments ? $call($context, ...$arguments) : $call($context);
    }

    protected function buildMessage(?string $customMessage, string $rule, ?array $arguments, ValidatorContext $context): string
    {
        $message = $customMessage ?? $this->messages[strtolower($rule)] ?? self::MESSAGE_DEFAULT;

        if (false === strpos($message, '{')) {
            return $message;
        }

        $data = array(
            '{position}' => $context->getPosition(),
            '{field}' => $context->getField(),
            '{prefix}' => $context->getPrefix(),
            '{suffix}' => $context->getSuffix(),
            '{path}' => $context->getPath(),
        );

        if (false !== strpos($message, '{value}')) {
            $data['{value}'] = Common::stringify($context->getValue());
        }

        if (false !== strpos($message, '{arguments}')) {
            $data['{arguments}'] = Common::stringify($arguments);
        }

        if (false !== strpos($message, '{argument_') && $arguments) {
            foreach ($arguments as $key => $value) {
                $data['{argument_'.$key.'}'] = Common::stringify($value);
            }
        }

        return strtr($message, $data);
    }

    protected function _rule_accepted(ValidatorContext $context): bool
    {
        return in_array($context->getValue(), array('yes', 'on', 1, '1', true), true);
    }

    protected function _rule_after(ValidatorContext $context, $date, string $format = null, string $timezone = null): bool
    {
        return $context->compareDate($date, $format, $timezone) == 1;
    }

    protected function _rule_after_or_equal(ValidatorContext $context, $date, string $format = null, string $timezone = null): bool
    {
        return $context->compareDate($date, $format, $timezone) >= 1;
    }

    protected function _rule_alpha(ValidatorContext $context): bool
    {
        return ctype_alpha($context->getValue());
    }

    protected function _rule_alnum(ValidatorContext $context): bool
    {
        return ctype_alnum($context->getValue());
    }

    protected function _rule_array(ValidatorContext $context): bool
    {
        return is_array($context->getValue());
    }

    protected function _rule_before(ValidatorContext $context, $date, string $format = null, string $timezone = null): bool
    {
        return $context->compareDate($date, $format, $timezone) == -1;
    }

    protected function _rule_before_or_equal(ValidatorContext $context, $date, string $format = null, string $timezone = null): bool
    {
        return $context->compareDate($date, $format, $timezone) <= 0;
    }

    protected function _rule_between(ValidatorContext $context, $min, $max): bool
    {
        $length = $context->getSize();

        return $length >= $min && $length <= $max;
    }

    protected function _rule_boolean(ValidatorContext $context): bool
    {
        $check = is_bool($context->getValue()) || in_array($context->getValue(), array('true', 'false', 'TRUE', 'FALSE', 1, '1', 0, '0'), true);

        if ($check) {
            $context->setValue((bool) $context->getValue());
        }

        return $check;
    }

    protected function _rule_confirmed(ValidatorContext $context, string $field = null): bool
    {
        return $context->checkOther($against = $field ?? "{$context->getField()}_confirmation") && $context->getValue() === $context->getOther($against);
    }

    protected function _rule_date(ValidatorContext $context, bool $convert = false, string $format = null, string $timezone = null)
    {
        $value = $context->getDate(null, $format, $timezone);

        return $convert ? $value : null != $value;
    }

    protected function _rule_date_equals(ValidatorContext $context, $date, string $format = null, string $timezone = null)
    {
        return $context->compareDate($date, $format, $timezone) == 0;
    }

    protected function _rule_date_format(ValidatorContext $context, string $format, string $timezone = null)
    {
        return null != $context->getDate(null, $format, $timezone);
    }

    protected function _rule_different(ValidatorContext $context, string $field): bool
    {
        return !$context->checkOther($field) || $context->getValue() !== $context->getOther($field);
    }

    protected function _rule_digits(ValidatorContext $context): bool
    {
        return ctype_digit($context->getValue());
    }

    protected function _rule_digits_between(ValidatorContext $context, $min, $max): bool
    {
        $value = $context->getValue();
        $length = strlen($value);

        return is_numeric($value) && $length >= $min && $length <= $max;
    }

    protected function _rule_distinct(ValidatorContext $context, bool $ignoreCase = false): bool
    {
        $data = $context->getData();
        $field = $context->getField();
        $values = array_column($data, $field) ?: ($data[$field] ?? $context->getValue());
        $unique = array_unique($ignoreCase ? array_map('strtolower', $values) : $values);

        return $values && count($values) == count($unique);
    }

    protected function _rule_email(ValidatorContext $context): bool
    {
        return (bool) filter_var($context->getValue(), FILTER_VALIDATE_EMAIL);
    }

    protected function _rule_ends_with(ValidatorContext $context, string ...$suffixes): bool
    {
        $value = strtolower($context->getValue());

        foreach ($suffixes as $suffix) {
            $isTrue = substr($value, -strlen($suffix)) === strtolower($suffix);

            if ($isTrue) {
                return true;
            }
        }

        return false;
    }

    protected function _rule_exclude(ValidatorContext $context): bool
    {
        $context->exclude();

        return true;
    }

    protected function _rule_exclude_if(ValidatorContext $context, $anotherField, $value = null): bool
    {
        if (
            ($anotherField instanceof \Closure && $anotherField($context))
            || (is_string($anotherField) && $context->getOther($anotherField) === $value)
         ) {
            $context->exclude();
        }

        return true;
    }

    protected function _rule_exclude_unless(ValidatorContext $context, $anotherField, $value = null): bool
    {
        if (
            ($anotherField instanceof \Closure && $anotherField($context))
            || (is_string($anotherField) && $context->getOther($anotherField) === $value)
        ) {
            return true;
        }

        $context->exclude();

        return true;
    }

    protected function _rule_gt(ValidatorContext $context, string $field): bool
    {
        return $context->getSize() > $context->getSize($field);
    }

    protected function _rule_gte(ValidatorContext $context, string $field): bool
    {
        return $context->getSize() >= $context->getSize($field);
    }

    protected function _rule_in(ValidatorContext $context, ...$elements): bool
    {
        return in_array($context->getValue(), $elements);
    }

    protected function _rule_in_array(ValidatorContext $context, string $anotherField): bool
    {
        return in_array($context->getValue(), (array) $context->getOther($anotherField));
    }

    protected function _rule_integer(ValidatorContext $context): bool
    {
        $check = is_numeric($context->getValue()) && is_int($context->getValue() + 0);
        $context->setNumeric($check);

        if ($check) {
            $context->setValue(intval($context->getValue()));
        }

        return $check;
    }

    protected function _rule_ip(ValidatorContext $context): bool
    {
        return (bool) filter_var($context->getValue(), FILTER_VALIDATE_IP);
    }

    protected function _rule_ip4(ValidatorContext $context): bool
    {
        return (bool) filter_var($context->getValue(), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
    }

    protected function _rule_ip6(ValidatorContext $context): bool
    {
        return (bool) filter_var($context->getValue(), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
    }

    protected function _rule_json(ValidatorContext $context, bool $convert = false, bool $assoc = true)
    {
        $json = json_decode($context->getValue(), $assoc);

        return $convert ? $json : null !== $json;
    }

    protected function _rule_lt(ValidatorContext $context, string $field): bool
    {
        return $context->getSize() < $context->getSize($field);
    }

    protected function _rule_lte(ValidatorContext $context, string $field): bool
    {
        return $context->getSize() <= $context->getSize($field);
    }

    protected function _rule_match(ValidatorContext $context, string $pattern): bool
    {
        return (bool) preg_match($pattern, $context->getValue());
    }

    protected function _rule_max(ValidatorContext $context, $length): bool
    {
        return $context->getSize() <= $length;
    }

    protected function _rule_min(ValidatorContext $context, $length): bool
    {
        return $context->getSize() >= $length;
    }

    protected function _rule_not_in(ValidatorContext $context, ...$elements): bool
    {
        return !in_array($context->getValue(), $elements);
    }

    protected function _rule_not_match(ValidatorContext $context, string $pattern): bool
    {
        return !preg_match($pattern, $context->getValue());
    }

    protected function _rule_numeric(ValidatorContext $context): bool
    {
        $check = is_numeric($context->getValue());
        $context->setNumeric($check);

        if ($check) {
            $context->setValue(0 + $context->getValue());
        }

        return $check;
    }

    protected function _rule_optional(ValidatorContext $context): bool
    {
        if (in_array($context->getValue(), array('', null), true)) {
            $context->skip();
        }

        return true;
    }

    protected function _rule_required(ValidatorContext $context): bool
    {
        return !in_array($context->getValue(), array('', null), true);
    }

    protected function _rule_required_if(ValidatorContext $context, $anotherField, $value = null): bool
    {
        return (
            ($anotherField instanceof \Closure && $anotherField($context))
            || (is_string($anotherField) && $context->getOther($anotherField) === $value)
        ) && !in_array($context->getValue(), array('', null), true);
    }

    protected function _rule_required_unless(ValidatorContext $context, $anotherField, $value = null): bool
    {
        return !in_array($context->getValue(), array('', null), true) || (
            ($anotherField instanceof \Closure && $anotherField($context)) || (is_string($anotherField) && $context->getOther($anotherField) === $value)
        );
    }

    protected function _rule_same(ValidatorContext $context, $value, bool $strict = true): bool
    {
        return ($strict && $context->getValue() === $value) || $context->getValue() == $value;
    }

    protected function _rule_size(ValidatorContext $context, $length): bool
    {
        return $context->getSize() === $length;
    }

    protected function _rule_starts_with(ValidatorContext $context, string ...$prefixes): bool
    {
        $value = strtolower($context->getValue());

        foreach ($prefixes as $prefix) {
            $isTrue = substr($value, 0, strlen($prefix)) === strtolower($prefix);

            if ($isTrue) {
                return true;
            }
        }

        return false;
    }

    protected function _rule_string(ValidatorContext $context): bool
    {
        return is_string($context->getValue());
    }

    protected function _rule_trim(ValidatorContext $context)
    {
        return trim($context->getValue());
    }

    protected function _rule_url(ValidatorContext $context): bool
    {
        return (bool) filter_var($context->getValue(), FILTER_VALIDATE_URL);
    }
}
