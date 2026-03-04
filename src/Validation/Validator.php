<?php

namespace Atom\Validation;

use Atom\Validation\Exception\ValidationException;

trait Validator
{
    static array $errors = [];
    static array $inputRules = [];
    static array $attributes = [];
    static mixed $input = null;

    /**
     * Excute validation
     * @param  array  $input
     * @param  array  $rules
     * @param  array  $messages
     * @return void
     */
    public static function execute(array $input, array $rules, array $messages = [])
    {
        static::checkRules($rules);
        static::checkMessages($messages);
        static::setInput($input);
        $messages = empty($messages) ? static::messages() : $messages;
        foreach ($rules as $attribute => $rule) {
            $rule = stripSpace($rule);
            preg_match('~\\brequired\\b~i', $rule, $required);
            preg_match('/required_if:(.+)\|/', $rule, $requiredIf);
            if (empty($required) && empty($input[$attribute])) {
                continue;
            }
            if ($requiredIf && !isset($input[$requiredIf[1]]) && empty($input[$attribute])) {
                continue;
            }
            static::$errors[$attribute] = static::checkValidation($input[$attribute], $rule, $attribute, $messages);
        }
    }

    /**
     * Set input
     * @param array $input
     */
    public static function setInput(array $input)
    {
        static::$input = $input;
    }

    /**
     * Get input
     */
    public static function getInput()
    {
        return static::$input;
    }

    /**
     * Get errors
     * @return array
     */
    public static function errors()
    {
        $errors = array_filter(static::$errors);
        if (empty($errors)) {
            return [];
        }
        return $errors;
    }

    /**
     * Check Validation
     * @param  mixed $value
     * @param  string $rules
     * @param  string $attribute
     * @param  array $messages
     * @return array
     */
    public static function checkValidation($value, $rules, $attribute, $messages)
    {
        $rules = array_filter(explode('|', $rules));
        $errors = [];
        foreach ($rules as $rule) {
            [$rule, $params] = static::getRule($rule);
            $error = call_user_func_array([__NAMESPACE__.'\Validator', $rule], [$value, $attribute, $messages[$rule], $params]);
            if (!empty($error)) {
                $errors[] = $error;
            }
        }
        return $errors;
    }

    /**
     * Check Rules
     * @param  array  $rules
     * @return \Exception
     */
    public static function checkRules(array $rules)
    {
        foreach ($rules as $inputKey => $rule) {
            $inputKey = stripSpace($inputKey);
            $rule = stripSpace($rule);
            array_push(static::$attributes, $inputKey);
            static::splitRules($rule);
        }
        if (array_diff(static::getInputRules(), static::rules())) {
            throw new ValidationException(ValidationException::ERR_MSG_INVALID_RULES);
        }
    }

    /**
     * Check Message
     * @param  array  $messages
     * @return \Exception
     */
    public static function checkMessages(array $messages = [])
    {
        $messages = empty($messages) ? static::messages() : $messages;
        if (array_diff(static::getInputRules(), array_keys($messages))) {
            throw new ValidationException(ValidationException::ERR_MSG_NO_MESSAGES);
        }
    }

    /**
     * Split Rules
     * @param  string $rules
     * @return void
     */
    public static function splitRules(string $rules)
    {
        $rules = array_filter(explode('|', $rules));
        foreach ($rules as $key => $rule) {
            [$inputRule, $params] = static::getRule($rule);
            static::setInputRules($inputRule);
        }
    }

    /**
     * Set input rules
     * @param string $inputRule
     */
    public static function setInputRules(string $inputRule)
    {
        array_push(static::$inputRules, $inputRule);
    }

    /**
     * Get input rules
     */
    public static function getInputRules()
    {
        return static::$inputRules;
    }

    /**
     * Get Rule
     * @param  string $rule
     * @return array
     */
    public static function getRule(string $rule)
    {
        preg_match("/(.+)\:(.+)/", $rule, $output);
        return $output && $output[1] ? [$output[1], $output[2]]: [$rule, null];
    }

    /**
     * Default messages
     * @param  string $rule
     * @return array|string
     */
    public static function messages($rule = null)
    {
        $messages = [
            'required' => 'This %s must be required.',
            'string' => 'This %s must be string.',
            'email' => 'This %s is invalid format.',
            'integer' => 'This %s must be integer.',
            'between' => 'This %s must be between %s and %s',
            'in_array' => 'This %s is invalid value',
            'max' => 'This %s is greater than %s',
            'min' => 'This %s is smaller than %s',
            'array' => 'This %s must be array',
            'date' => 'This %s must be format of date time',
            'image' => 'This %s must be image',
            'after' => 'This %s must be after %s',
            'before' => 'This %s must be before %s',
            'required_if' => 'This %s is required',
            'date_format' => 'This %s must be presented as %s',
        ];
        return is_null($rule) ? $messages : $messages[$rule];
    }

    /**
     * Default Rules
     * @return array
     */
    public static function rules()
    {
        return [
            'array', 'between', 'date', 'email', 'image', 'in_array', 'integer', 'max', 'min', 'required', 'string', 'after', 'before', 'required_if', 'date_format',
        ];
    }

    /**
     * Date_format Validation
     * @return string
     */
    public static function date_format(mixed $value, string $attribute, string $message, mixed $params = null): string
    {
        return date_create_from_format($params, $value) !== false ? '' : vsprintf($message, [$attribute, $params]);
    }

    /**
     * Required_if Validation
     * @return string
     */
    public static function required_if(mixed $value, string $attribute, string $message, mixed $params = null): string
    {
        $input = static::getInput();
        [$field, $fielValue] = explode(',', $params);
        return (!isset($input[$field])) ? '' : vsprintf($message, [$attribute, $params]);
    }

    /**
     * After Validation
     * @return string
     */
    public static function after(mixed $value, string $attribute, string $message, mixed $params = null): string
    {
        return (strtotime($value) !== false) && (strtotime($value) > strtotime($params)) ? '' : vsprintf($message, [$attribute, $params]);
    }

    /**
     * Before Validation
     * @return string
     */
    public static function before(mixed $value, string $attribute, string $message, mixed $params = null): string
    {
        return (strtotime($value) !== false) && (strtotime($value) < strtotime($params)) ? '' : vsprintf($message, [$attribute, $params]);
    }

    /**
     * Image Validation
     * @return string
     */
    public static function image(mixed $value, string $attribute, string $message, mixed $params = null): string
    {
        $info = pathinfo($value['name']);
        return in_array($info["extension"], ['jpeg', 'png', 'bmp', 'gif', 'svg']) ? '' : vsprintf($message, [$attribute, $params]);
    }

    /**
     * Date Validation
     * @return string
     */
    public static function date(mixed $value, string $attribute, string $message, mixed $params = null): string
    {
        return strtotime($value) !== false ? '' : vsprintf($message, [$attribute, $params]);
    }

    /**
     * Array Validation
     * @return string
     */
    public static function array(mixed $value, string $attribute, string $message, mixed $params = null): string
    {
        return is_array($value) ? '' : vsprintf($message, [$attribute, $params]);
    }

    /**
     * Min Validation
     * @return string
     */
    public static function min(mixed $value, string $attribute, string $message, mixed $params = null): string
    {
        return (float)$value > (float)$params ? '' : vsprintf($message, [$attribute, $params]);
    }

    /**
     * Max Validation
     * @return string
     */
    public static function max(mixed $value, string $attribute, string $message, mixed $params = null): string
    {
        return (float)$value < (float)$params ? '' : vsprintf($message, [$attribute, $params]);
    }

    /**
     * In_array Validation
     * @return string
     */
    public static function in_array(mixed $value, string $attribute, string $message, mixed $params = null): string
    {
        return in_array($value, explode(',', $params)) ? '' : vsprintf($message, [$attribute, $params]);
    }

    /**
     * Between Validation
     * @return string
     */
    public static function between(mixed $value, string $attribute, string $message, mixed $params = null): string
    {
        [$min, $max] = explode(',', $params);
        return (float)$value >= (float)$min && (float)$value <= (float)$max ? '' : vsprintf($message, [$attribute, $min, $max]);
    }

    /**
     * Required Validation
     * @return string
     */
    public static function required(mixed $value, string $attribute, string $message, mixed $params = null): string
    {
        return !empty($value) ? '' : vsprintf($message, [$attribute]);
    }

    /**
     * String Validation
     * @return string
     */
    public static function string(mixed $value, string $attribute, string $message, mixed $params = null): string
    {
        return is_string($value) ? '' : vsprintf($message, [$attribute]);
    }

    /**
     * Email Validation
     * @return string
     */
    public static function email(mixed $value, string $attribute, string $message, mixed $params = null): string
    {
        return (bool) filter_var($value, FILTER_VALIDATE_EMAIL) ? '' : vsprintf($message, [$attribute]);
    }

    /**
     * Integer Validation
     * @return string
     */
    public static function integer(mixed $value, string $attribute, string $message, mixed $params = null): string
    {
        return filter_var($value, FILTER_VALIDATE_INT) ? '' : vsprintf($message, [$attribute]);
    }
}
