<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace HyperfTest;

use App\Application\ModelGateway\Official\DelightfulAccessToken;
use Hyperf\Context\ApplicationContext;
use Hyperf\Testing;
use Mockery;
use PHPUnit\Framework\TestCase;

use function Hyperf\Support\make;

/**
 * Class HttpTestCase.
 * @method get($uri, $data = [], $headers = [])
 * @method post($uri, $data = [], $headers = [])
 * @method delete($uri, $data = [], $headers = [])
 * @method put($uri, $data = [], $headers = [])
 * @method json($uri, $data = [], $headers = [])
 * @method file($uri, $data = [], $headers = [])
 */
abstract class HttpTestCase extends TestCase
{
    /**
     * @var Testing\Client
     */
    protected $client;

    public function __construct(string $name)
    {
        parent::__construct($name);
        $this->client = make(Testing\Client::class);
        // $this->client = make(Testing\HttpClient::class, ['baseUri' => 'http://127.0.0.1:9764']);
        DelightfulAccessToken::init();
    }

    public function __call($name, $arguments)
    {
        return $this->client->{$name}(...$arguments);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    protected function getCommonHeaders(): array
    {
        return [
            'organization-code' => env('TEST_ORGANIZATION_CODE'),
            // Replace with your own token
            'Authorization' => env('TEST_TOKEN'),
        ];
    }

    /**
     * Assert that two arrays have matching value types.
     * Verifies array structure and types are aligned.
     *
     * @param array $expected Expected array
     * @param array $actual Actual array
     * @param string $message Error message when assertion fails
     * @param bool $checkKeys Whether to check keys
     */
    protected function assertArrayValueTypesEquals(array $expected, array $actual, string $message = '', bool $checkKeys = true): void
    {
        // First ensure the actual array has all expected keys
        if ($checkKeys) {
            foreach (array_keys($expected) as $key) {
                $this->assertArrayHasKey($key, $actual, $message . sprintf(' - key "%s" is missing', $key));
            }
        }

        // Recursively check each value type
        foreach ($expected as $key => $expectedValue) {
            // Ensure the key exists
            if (! array_key_exists($key, $actual)) {
                if ($checkKeys) {
                    $this->fail($message . sprintf(' - key "%s" is missing', $key));
                }
                continue;
            }

            $actualValue = $actual[$key];
            $expectedType = gettype($expectedValue);
            $actualType = gettype($actualValue);

            // If the expected value is null, allow any actual type
            if ($expectedValue === null) {
                continue;
            }

            // Check for type match
            if ($expectedType !== $actualType && ! ($expectedType === 'double' && $actualType === 'integer')) {
                $this->assertEquals(
                    $expectedType,
                    $actualType,
                    $message . sprintf(' - key "%s" expected type %s, got %s', $key, $expectedType, $actualType)
                );
                continue;
            }

            // Handle string types specially
            if (is_string($expectedValue) && $expectedValue === 'NOT_EMPTY') {
                $this->assertNotEmpty($actualValue, $message . sprintf(' - key "%s" should not be empty', $key));
            }

            // Recursively handle array types
            if (is_array($expectedValue)) {
                // If the expected array is empty, only check type
                if (empty($expectedValue)) {
                    continue;
                }

                // Check whether the array is an indexed list
                $isIndexedArray = array_keys($expectedValue) === range(0, count($expectedValue) - 1);

                if ($isIndexedArray && ! empty($actualValue)) {
                    // For indexed arrays, check the first element type
                    $firstExpectedItem = reset($expectedValue);
                    $firstActualItem = reset($actualValue);

                    if (is_array($firstExpectedItem)) {
                        $this->assertIsArray($firstActualItem, $message . sprintf(' - key "%s" array elements should be arrays', $key));
                        $this->assertArrayValueTypesEquals(
                            $firstExpectedItem,
                            $firstActualItem,
                            $message . sprintf(' - key "%s" array element', $key),
                            $checkKeys
                        );
                    } else {
                        $expectedItemType = gettype($firstExpectedItem);
                        $actualItemType = gettype($firstActualItem);
                        $this->assertEquals(
                            $expectedItemType,
                            $actualItemType,
                            $message . sprintf(' - key "%s" array element type expected %s, got %s', $key, $expectedItemType, $actualItemType)
                        );
                    }
                } elseif (! $isIndexedArray) {
                    // For associative arrays, validate recursively
                    $this->assertArrayValueTypesEquals(
                        $expectedValue,
                        $actualValue,
                        $message . sprintf(' - key "%s" sub-array', $key),
                        $checkKeys
                    );
                }
            }
        }
    }

    /**
     * Assert that two arrays have identical values.
     * Verifies the exact values match.
     *
     * @param array $expected Expected array
     * @param array $actual Actual array
     * @param string $message Error message when assertion fails
     * @param bool $strict Whether to use strict comparison (===)
     * @param bool $checkKeys Whether to check keys
     */
    protected function assertArrayEquals(array $expected, array $actual, string $message = '', bool $strict = true, bool $checkKeys = true): void
    {
        // First ensure the actual array has all expected keys
        if ($checkKeys) {
            $expectedKeys = array_keys($expected);
            $actualKeys = array_keys($actual);
            $missingKeys = array_diff($expectedKeys, $actualKeys);

            if (! empty($missingKeys)) {
                $this->fail($message . sprintf(' - missing keys: "%s"', implode('", "', $missingKeys)));
            }
        }

        // Recursively check each value
        foreach ($expected as $key => $expectedValue) {
            // Ensure the key exists
            if (! array_key_exists($key, $actual)) {
                if ($checkKeys) {
                    $this->fail($message . sprintf(' - key "%s" is missing', $key));
                }
                continue;
            }

            $actualValue = $actual[$key];

            // Handle arrays recursively
            if (is_array($expectedValue) && is_array($actualValue)) {
                $this->assertArrayEquals(
                    $expectedValue,
                    $actualValue,
                    $message . sprintf(' - key "%s" sub-array', $key),
                    $strict,
                    $checkKeys
                );
            } else {
                // For non-array values, compare directly
                if ($strict) {
                    $this->assertSame(
                        $expectedValue,
                        $actualValue,
                        $message . sprintf(' - key "%s" value mismatch', $key)
                    );
                } else {
                    $this->assertEquals(
                        $expectedValue,
                        $actualValue,
                        $message . sprintf(' - key "%s" value mismatch', $key)
                    );
                }
            }
        }
    }

    /**
     * Get the Hyperf DI container instance.
     */
    protected function getContainer()
    {
        return ApplicationContext::getContainer();
    }
}
