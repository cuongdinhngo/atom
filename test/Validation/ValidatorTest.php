<?php

namespace Atom\Test\Validation;

use Atom\Test\TestCase;
use Atom\Validation\Exception\ValidationException;

/**
 * Concrete class using the Validator trait for testing
 */
class ValidatorStub
{
    use \Atom\Validation\Validator;
}

class ValidatorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        ValidatorStub::$errors = [];
        ValidatorStub::$inputRules = [];
        ValidatorStub::$attributes = [];
        ValidatorStub::$input = null;
    }

    // --- Rule Parsing ---

    public function testGetRuleWithoutParams()
    {
        $result = ValidatorStub::getRule('required');
        $this->assertEquals(['required'], $result);
    }

    public function testGetRuleWithParams()
    {
        $result = ValidatorStub::getRule('min:5');
        $this->assertEquals(['min', '5'], $result);
    }

    public function testGetRuleWithMultipleParams()
    {
        $result = ValidatorStub::getRule('between:1,10');
        $this->assertEquals(['between', '1,10'], $result);
    }

    public function testGetRuleInArray()
    {
        $result = ValidatorStub::getRule('in_array:admin,editor,viewer');
        $this->assertEquals(['in_array', 'admin,editor,viewer'], $result);
    }

    public function testGetRuleDateFormat()
    {
        $result = ValidatorStub::getRule('date_format:Y-m-d');
        $this->assertEquals(['date_format', 'Y-m-d'], $result);
    }

    // --- splitRules ---
    // NOTE: splitRules has a PHP 8 bug: getRule() returns 1-element array for
    // rules without params, but splitRules does list($inputRule, $params) which
    // fails on undefined array key 1. This will be fixed during PHP 8 upgrade.

    public function testSplitRulesWithParams()
    {
        ValidatorStub::splitRules('min:5|max:10');
        $rules = ValidatorStub::getInputRules();
        $this->assertContains('min', $rules);
        $this->assertContains('max', $rules);
    }

    // --- Messages ---

    public function testMessagesReturnsAllMessages()
    {
        $messages = ValidatorStub::messages();
        $this->assertIsArray($messages);
        $this->assertArrayHasKey('required', $messages);
        $this->assertArrayHasKey('email', $messages);
        $this->assertArrayHasKey('integer', $messages);
        $this->assertArrayHasKey('between', $messages);
        $this->assertArrayHasKey('min', $messages);
        $this->assertArrayHasKey('max', $messages);
    }

    public function testMessagesReturnsSingleMessage()
    {
        $message = ValidatorStub::messages('required');
        $this->assertIsString($message);
        $this->assertStringContainsString('required', $message);
    }

    // --- Rules ---

    public function testRulesReturnsArray()
    {
        $rules = ValidatorStub::rules();
        $this->assertIsArray($rules);
        $this->assertContains('required', $rules);
        $this->assertContains('email', $rules);
        $this->assertContains('integer', $rules);
        $this->assertContains('string', $rules);
        $this->assertContains('min', $rules);
        $this->assertContains('max', $rules);
        $this->assertContains('between', $rules);
        $this->assertContains('date', $rules);
        $this->assertContains('array', $rules);
        $this->assertContains('image', $rules);
        $this->assertContains('in_array', $rules);
    }

    // --- Individual rule methods (call directly, bypassing execute() pipeline
    //     which has the PHP 8 list() bug on rules without params) ---

    public function testRequiredPasses()
    {
        $result = ValidatorStub::required('John', 'name', 'This %s must be required.', null);
        $this->assertEquals('', $result);
    }

    public function testRequiredFailsOnEmpty()
    {
        $result = ValidatorStub::required('', 'name', 'This %s must be required.', null);
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('name', $result);
    }

    public function testStringPasses()
    {
        $result = ValidatorStub::string('hello', 'name', 'This %s must be string.', null);
        $this->assertEquals('', $result);
    }

    public function testStringFails()
    {
        $result = ValidatorStub::string(123, 'name', 'This %s must be string.', null);
        $this->assertNotEmpty($result);
    }

    public function testEmailPasses()
    {
        $result = ValidatorStub::email('test@example.com', 'email', 'This %s is invalid format.', null);
        $this->assertEquals('', $result);
    }

    public function testEmailFails()
    {
        $result = ValidatorStub::email('not-an-email', 'email', 'This %s is invalid format.', null);
        $this->assertNotEmpty($result);
    }

    public function testIntegerPasses()
    {
        $result = ValidatorStub::integer('25', 'age', 'This %s must be integer.', null);
        $this->assertEquals('', $result);
    }

    public function testIntegerFails()
    {
        $result = ValidatorStub::integer('abc', 'age', 'This %s must be integer.', null);
        $this->assertNotEmpty($result);
    }

    public function testMinPasses()
    {
        $result = ValidatorStub::min('20', 'age', 'This %s is smaller than %s', '10');
        $this->assertEquals('', $result);
    }

    public function testMinFails()
    {
        $result = ValidatorStub::min('5', 'age', 'This %s is smaller than %s', '10');
        $this->assertNotEmpty($result);
    }

    public function testMaxPasses()
    {
        $result = ValidatorStub::max('5', 'age', 'This %s is greater than %s', '10');
        $this->assertEquals('', $result);
    }

    public function testMaxFails()
    {
        $result = ValidatorStub::max('20', 'age', 'This %s is greater than %s', '10');
        $this->assertNotEmpty($result);
    }

    public function testBetweenPasses()
    {
        $result = ValidatorStub::between('5', 'age', 'This %s must be between %s and %s', '1,10');
        $this->assertEquals('', $result);
    }

    public function testBetweenFails()
    {
        $result = ValidatorStub::between('15', 'age', 'This %s must be between %s and %s', '1,10');
        $this->assertNotEmpty($result);
    }

    public function testBetweenEdgeMin()
    {
        $result = ValidatorStub::between('1', 'age', 'This %s must be between %s and %s', '1,10');
        $this->assertEquals('', $result);
    }

    public function testBetweenEdgeMax()
    {
        $result = ValidatorStub::between('10', 'age', 'This %s must be between %s and %s', '1,10');
        $this->assertEquals('', $result);
    }

    public function testInArrayPasses()
    {
        $result = ValidatorStub::in_array('admin', 'role', 'This %s is invalid value', 'admin,editor,viewer');
        $this->assertEquals('', $result);
    }

    public function testInArrayFails()
    {
        $result = ValidatorStub::in_array('superadmin', 'role', 'This %s is invalid value', 'admin,editor,viewer');
        $this->assertNotEmpty($result);
    }

    public function testDatePasses()
    {
        $result = ValidatorStub::date('2024-01-15', 'birthday', 'This %s must be format of date time', null);
        $this->assertEquals('', $result);
    }

    public function testDateFails()
    {
        $result = ValidatorStub::date('not-a-date', 'birthday', 'This %s must be format of date time', null);
        $this->assertNotEmpty($result);
    }

    public function testArrayPasses()
    {
        $result = ValidatorStub::array(['a', 'b'], 'items', 'This %s must be array', null);
        $this->assertEquals('', $result);
    }

    public function testArrayFails()
    {
        $result = ValidatorStub::array('not-array', 'items', 'This %s must be array', null);
        $this->assertNotEmpty($result);
    }

    public function testDateFormatPasses()
    {
        $result = ValidatorStub::date_format('2024-01-15', 'date', 'This %s must be presented as %s', 'Y-m-d');
        $this->assertEquals('', $result);
    }

    public function testDateFormatFails()
    {
        $result = ValidatorStub::date_format('15/01/2024', 'date', 'This %s must be presented as %s', 'Y-m-d');
        $this->assertNotEmpty($result);
    }

    public function testAfterPasses()
    {
        $result = ValidatorStub::after('2030-01-01', 'date', 'This %s must be after %s', '2025-01-01');
        $this->assertEquals('', $result);
    }

    public function testAfterFails()
    {
        $result = ValidatorStub::after('2020-01-01', 'date', 'This %s must be after %s', '2025-01-01');
        $this->assertNotEmpty($result);
    }

    public function testBeforePasses()
    {
        $result = ValidatorStub::before('2020-01-01', 'date', 'This %s must be before %s', '2025-01-01');
        $this->assertEquals('', $result);
    }

    public function testBeforeFails()
    {
        $result = ValidatorStub::before('2030-01-01', 'date', 'This %s must be before %s', '2025-01-01');
        $this->assertNotEmpty($result);
    }

    public function testImagePasses()
    {
        $result = ValidatorStub::image(['name' => 'photo.png'], 'photo', 'This %s must be image', null);
        $this->assertEquals('', $result);
    }

    public function testImageAcceptsJpeg()
    {
        $result = ValidatorStub::image(['name' => 'photo.jpeg'], 'photo', 'This %s must be image', null);
        $this->assertEquals('', $result);
    }

    public function testImageFails()
    {
        $result = ValidatorStub::image(['name' => 'file.pdf'], 'photo', 'This %s must be image', null);
        $this->assertNotEmpty($result);
    }

    // --- Set/Get Input ---

    public function testSetAndGetInput()
    {
        $input = ['name' => 'John', 'email' => 'john@example.com'];
        ValidatorStub::setInput($input);
        $this->assertEquals($input, ValidatorStub::getInput());
    }

    // --- Errors ---

    public function testErrorsEmptyByDefault()
    {
        $this->assertEmpty(ValidatorStub::errors());
    }

    // --- Required If ---

    public function testRequiredIfWhenFieldNotSet()
    {
        ValidatorStub::setInput(['name' => 'John']);
        $result = ValidatorStub::required_if('John', 'name', 'This %s is required', 'role,admin');
        $this->assertEquals('', $result);
    }

    public function testRequiredIfWhenFieldSet()
    {
        ValidatorStub::setInput(['name' => 'John', 'role' => 'admin']);
        $result = ValidatorStub::required_if('John', 'name', 'This %s is required', 'role,admin');
        $this->assertNotEmpty($result);
    }
}
