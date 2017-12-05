<?php

namespace Tests\Unit;

use JessicaMulein\LaravelProportionalProofOfWork\InvalidHashException;
use JessicaMulein\LaravelProportionalProofOfWork\Work;
use PHPUnit\Framework\TestCase;

// TODO: test parseWork

class PpowTest extends TestCase
{
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
    }

    protected function generateTestBytes($zeroBits)
    {
        // generate a bit string with the right number of 0's and 1's
        $bitStr = str_repeat('0', $zeroBits).str_repeat('1', (Work::HASH_SIZE - $zeroBits));
        // make sure it is the expected length
        $this->assertEquals(Work::HASH_SIZE, strlen($bitStr));
        // convert it to binary
        $byteStr = '';
        for ($i=0;$i<Work::HASH_SIZE; $i+=8) {
            $chunk = substr($bitStr, $i, 8);
            $byteStr .= chr(base_convert($chunk, 2, 10));
        }
        // make sure we have the right number of bytes
        $this->assertEquals(Work::HASH_SIZE/8, strlen($byteStr));

        return $byteStr;
    }

    public function bitsProvider()
    {
        return array_map(function($x) {
            return (array) [$this->generateTestBytes($x), $x];
        }, range(0, 256));
    }

    protected function invokeVerifyBits($testBytes, $expectedZeroes, &$foundZeroes = null)
    {
        // set the verifyBits method to public for testing
        $reflection = new \ReflectionClass(Work::class);
        $method = $reflection->getMethod('verifyBits');
        $method->setAccessible(true);

        // invoke the normally protected builtin method
        return $method->invokeArgs(null, [$testBytes, $expectedZeroes, &$foundZeroes]);
    }

    /**
     * @dataProvider bitsProvider
     */
    public function test_it_verifies_bits($testBytes, $expectedZeroes)
    {
        $foundZeroes = null;
        $result = $this->invokeVerifyBits($testBytes, $expectedZeroes, $foundZeroes);
        $this->assertEquals($expectedZeroes, $foundZeroes);
        $this->assertTrue($result);
    }

    public function test_it_verifies_excess_bits()
    {
        $foundZeroes = null;
        // generate a 255-byte left zero
        $testBytes = $this->generateTestBytes(255);
        // verify only 35
        $result = $this->invokeVerifyBits($testBytes, 35, $foundZeroes);
        // ensure we found the 255
        $this->assertEquals(255, $foundZeroes);
        // and still passed
        $this->assertTrue($result);
    }

    public function test_it_fails_to_verify_with_wrong_bit_count_255()
    {
        // generate 256 0's
        $testBytes = $this->generateTestBytes(256);
        // drop it to 31 bytes
        $testBytes = substr($testBytes, 0, 31);
        $this->assertFalse($this->invokeVerifyBits($testBytes, 1));
    }

    public function test_it_fails_to_verify_with_wrong_bit_count_257()
    {
        // generate 256 0's
        $testBytes = $this->generateTestBytes(256);
        // add one byte
        $testBytes = $testBytes.chr(255);
        $this->assertFalse($this->invokeVerifyBits($testBytes, 1));
    }

    public function test_it_fails_to_work_with_bits_0()
    {
        $this->expectException(InvalidHashException::class);
        $work = Work::fromString('0.1:0:123456:this is a test resource:abcdefg=/:abcdefg=/');
    }

    public function test_it_fails_to_work_with_bits_256()
    {
        $this->expectException(InvalidHashException::class);
        $work = Work::fromString('0.1:256:123456:this is a test resource:abcdefg=/:abcdefg=/');
    }

    /**
     * @dataProvider workProvider
     */
    public function test_it_works($bits)
    {
        $work = new Work($bits, 'this is a test resource-'.md5(random_bytes(10)));
        $work->work();
        $this->assertTrue($work->isValid());
    }

    public function workProvider()
    {
        $start = 1;
        $end = 16; // much longer than this and it takes too long (16 takes up to ~14s, 17 takes up to ~52s, 18 takes up to 1m20s)

        return array_map(function($x) { return (array) $x; }, range($start, $end));
    }

    public function test_it_fails_with_incorrect_hash_bits()
    {
        // do some work with 10 bits
        $work = new Work(10, 'this is a test resource-'.md5(random_bytes(10)));
        $completedWork = $work->work();
        // change the initial value
        $parts = explode(':', $completedWork);
        $parts[1] = 11; // expect 11 bits now instead
        // reassemble
        $newWork = Work::fromString(implode(':', $parts));
        $this->assertFalse($newWork->isValid());
    }

    public function test_it_fails_with_incorrect_hash_resource()
    {
        // do some work with 10 bits
        $work = new Work(10, 'this is a test resource-'.md5(random_bytes(10)));
        $completedWork = $work->work();
        // change the initial value
        $parts = explode(':', $completedWork);
        $parts[3] = 'this is a test resource-'.md5(random_bytes(10)); // regenerate the random data
        $newWork = Work::fromString(implode(':', $parts));
        $this->assertFalse($newWork->isValid());
    }
}
