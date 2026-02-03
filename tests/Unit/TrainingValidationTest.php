<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Core\Validator;

/**
 * Test to ensure the Validator correctly handles the 'array' rule.
 */
class TrainingValidationTest extends TestCase
{
    private $validator;

    protected function setUp(): void
    {
        $this->validator = new Validator();
    }

    public function testArrayRulePassesWithValidArray()
    {
        $postData = [
            'units' => ['soldier' => '10', 'spy' => 5]
        ];
        $rules = [
            'units' => 'required|array'
        ];

        $validation = $this->validator->make($postData, $rules);

        $this->assertTrue($validation->passes());
        $this->assertFalse($validation->fails());
    }

    public function testArrayRuleFailsWithString()
    {
        $postData = [
            'units' => 'not-an-array'
        ];
        $rules = [
            'units' => 'required|array'
        ];

        $validation = $this->validator->make($postData, $rules);

        $this->assertTrue($validation->fails());
        $this->assertEquals('Units must be a collection of items.', $validation->errors()['units']);
    }

    public function testValidatedDataForArrayIsCorrect()
    {
        $postData = [
            'units' => ['soldier' => '10', 'spy' => '5']
        ];
        $rules = [
            'units' => 'required|array'
        ];

        $validation = $this->validator->make($postData, $rules);
        $validatedData = $validation->validated();

        $this->assertTrue($validation->passes());
        $this->assertIsArray($validatedData['units']);
        $this->assertEquals(['soldier' => '10', 'spy' => '5'], $validatedData['units']);
    }

    public function testSanitizationOfArrayKeysAndValues()
    {
        $postData = [
            'units' => [
                '<script>alert("xss")</script>' => ' 10 ',
                'spy' => '<p>5</p>'
            ]
        ];
        $rules = [
            'units' => 'required|array'
        ];

        $validation = $this->validator->make($postData, $rules);
        $validatedData = $validation->validated();

        $expected = [
            '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;' => '10',
            'spy' => '&lt;p&gt;5&lt;/p&gt;'
        ];

        $this->assertTrue($validation->passes());
        $this->assertIsArray($validatedData['units']);
        $this->assertEquals($expected, $validatedData['units']);
    }
}
