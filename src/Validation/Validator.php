<?php

namespace Atom\Validation;

use Atom\Validation\Exception\ValidationException;

trait Validator
{
    static $errors = [];
    static $inputRules = [];
    static $attributes = [];
    static $input;

    /**
     * Excute validation
     * @param  array  $input
     * @param  array  $rules
     * @param  array  $messages
     * @return void
     */
    public static function validate(array $input, array $rules, array $messages = [])
    {
        static::checkRules($rules);
        static::checkMessages();
        static::setInput($input);
        $messages = empty($messages) ? static::messages() : $messages;
        foreach ($rules as $attribute => $rule) {
            $rule = stripSpace($rule);
            preg_match('~\\brequired\\b~i', $rule, $required);
            preg_match('/required_if:(.+)\|/', $rule, $requiredIf);
            if (
                (empty($required) && empty($requiredIf) && !isset($input[$attribute])) ||
                ($requiredIf && !isset($input[$requiredIf[1]]) && empty($input[$attribute]))) {
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
        return array_filter(static::$errors);
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
        $isBail = false;
        foreach ($rules as $rule) {
            if ($rule == 'bail') {
                $isBail = true;
                continue;
            }
            if ($isBail && !empty($errors)) {
                break;
            }
            list($rule, $params) = static::getRule($rule);
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
     * @return \Exception
     */
    public static function checkMessages()
    {
        $inputRules = static::getInputRules();
        $bail = array_search('bail', $inputRules);
        unset($inputRules[$bail]);
        if (array_diff($inputRules, array_keys(static::messages()))) {
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
            list($inputRule, $params) = static::getRule($rule);
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
        return $output && $output[1] ? [$output[1], $output[2]]: [$rule];
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
            'bail', 'array', 'between', 'date', 'email', 'image', 'in_array', 'integer', 'max', 'min', 'required', 'string', 'after', 'before', 'required_if', 'date_format',
        ];
    }

    /**
     * Date_format Validation
     * @return string
     */
    public static function date_format()
    {
        list($value, $attribute, $message, $params) = func_get_args();
        return date_create_from_format($params, $value) !== false ? '' : vsprintf($message, [$attribute, $params]);
    }

    /**
     * Required_if Validation
     * @return string
     */
    public static function required_if()
    {
        list($value, $attribute, $message, $params) = func_get_args();
        $input = static::getInput();
        list($field, $fielValue) = explode(',', $params);
        return (!isset($input[$field])) ? '' : vsprintf($message, [$attribute, $params]);
    }

    /**
     * After Validation
     * @return string
     */
    public static function after()
    {
        list($value, $attribute, $message, $params) = func_get_args();
        return (strtotime($value) !== false) && (strtotime($value) > strtotime($params)) ? '' : vsprintf($message, [$attribute, $params]);
    }

    /**
     * Before Validation
     * @return string
     */
    public static function before()
    {
        list($value, $attribute, $message, $params) = func_get_args();
        return (strtotime($value) !== false) && (strtotime($value) < strtotime($params)) ? '' : vsprintf($message, [$attribute, $params]);
    }	

    /**
     * Image Validation
     * @return string
     */
    public static function image()
    {
        list($value, $attribute, $message, $params) = func_get_args();
        $info = pathinfo($value['name']);
        return in_array($info["extension"], ['jpeg', 'png', 'bmp', 'gif', 'svg']) ? '' : vsprintf($message, [$attribute, $params]);
    }

    /**
     * Date Validation
     * @return string
     */
    public static function date()
    {
        list($value, $attribute, $message, $params) = func_get_args();
        return strtotime($value) !== false ? '' : vsprintf($message, [$attribute, $params]);
	}

    /**
     * Array Validation
     * @return string
     */
    public static function array()
    {
        list($value, $attribute, $message, $params) = func_get_args();
        return is_array($value) ? '' : vsprintf($message, [$attribute, $params]);
    }

    /**
     * Min Validation
     * @return string
     */
    public static function min()
    {
        list($value, $attribute, $message, $params) = func_get_args();
        return $params < $value ? '' : vsprintf($message, [$attribute, $params]);
    }

    /**
     * Max Validation
     * @return string
     */
    public static function max()
    {
        list($value, $attribute, $message, $params) = func_get_args();
        return $params > $value ? '' : vsprintf($message, [$attribute, $params]);
    }

    /**
     * In_array Validation
     * @return string
     */
    public static function in_array()
    {
        list($value, $attribute, $message, $params) = func_get_args();
        return in_array($value, explode(',', $params)) ? '' : vsprintf($message, [$attribute, $params]);
    }

    /**
     * Between Validation
     * @return string
     */
    public static function between()
    {
        list($value, $attribute, $message, $params) = func_get_args();
        list($min, $max) = explode(',', $params);
        return $value >= $min && $value <= $max ? '' : vsprintf($message, [$attribute, $min, $max]);
    }

    /**
     * Required Validation
     * @return string
     */
    public static function required()
    {
        list($value, $attribute, $message, $params) = func_get_args();
        return !empty($value) ? '' : vsprintf($message, [$attribute]);
    }

    /**
     * String Validation
     * @return string
     */
    public static function string()
    {
        list($value, $attribute, $message, $params) = func_get_args();
        return is_string($value) ? '' : vsprintf($message, [$attribute]);
    }

    /**
     * Email Validation
     * @return string
     */
    public static function email()
    {
        list($value, $attribute, $message, $params) = func_get_args();
        return (bool) filter_var($value, FILTER_VALIDATE_EMAIL) ? '' : vsprintf($message, [$attribute]);
    }

    /**
     * Integer Validation
     * @return string
     */
    public static function integer()
    {
        list($value, $attribute, $message, $params) = func_get_args();
        return filter_var($value, FILTER_VALIDATE_INT) ? '' : vsprintf($message, [$attribute]);
    }
}
