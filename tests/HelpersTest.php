<?php

namespace Berkan\Tests;

class HelpersTest extends TestCase
{
    public function test_resolve_returns_instance_from_container(): void
    {
        $result = resolve(\Symfony\Component\Console\Output\ConsoleOutput::class);

        $this->assertInstanceOf(\Symfony\Component\Console\Output\ConsoleOutput::class, $result);
    }

    public function test_swap_replaces_container_instance(): void
    {
        $mock = new \stdClass();
        swap('test.instance', $mock);

        $this->assertSame($mock, resolve('test.instance'));
    }

    public function test_user_returns_current_user(): void
    {
        $originalSudoUser = $_SERVER['SUDO_USER'] ?? null;
        unset($_SERVER['SUDO_USER']);

        $_SERVER['USER'] = 'testuser';
        $this->assertEquals('testuser', user());

        if ($originalSudoUser !== null) {
            $_SERVER['SUDO_USER'] = $originalSudoUser;
        }
    }

    public function test_user_returns_sudo_user_when_set(): void
    {
        $_SERVER['SUDO_USER'] = 'rootuser';
        $_SERVER['USER'] = 'regularuser';

        $this->assertEquals('rootuser', user());

        unset($_SERVER['SUDO_USER']);
    }

    public function test_should_be_sudo_throws_when_not_sudo(): void
    {
        $original = $_SERVER['SUDO_USER'] ?? null;
        unset($_SERVER['SUDO_USER']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('This command must be run with sudo.');

        should_be_sudo();

        if ($original !== null) {
            $_SERVER['SUDO_USER'] = $original;
        }
    }

    public function test_should_be_sudo_does_not_throw_when_sudo(): void
    {
        $_SERVER['SUDO_USER'] = 'testuser';

        should_be_sudo();

        $this->assertTrue(true); // No exception thrown

        unset($_SERVER['SUDO_USER']);
    }

    public function test_retry_succeeds_on_first_try(): void
    {
        $result = retry(3, function () {
            return 'success';
        });

        $this->assertEquals('success', $result);
    }

    public function test_retry_retries_on_failure(): void
    {
        $attempts = 0;

        $result = retry(3, function () use (&$attempts) {
            $attempts++;
            if ($attempts < 3) {
                throw new \Exception('fail');
            }
            return 'success';
        });

        $this->assertEquals('success', $result);
        $this->assertEquals(3, $attempts);
    }

    public function test_retry_throws_after_exhausting_retries(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('always fails');

        retry(2, function () {
            throw new \Exception('always fails');
        });
    }

    public function test_tap_returns_original_value(): void
    {
        $result = tap('hello', function ($value) {
            // modify something else
        });

        $this->assertEquals('hello', $result);
    }

    public function test_tap_calls_callback(): void
    {
        $called = false;

        tap('hello', function ($value) use (&$called) {
            $called = true;
            $this->assertEquals('hello', $value);
        });

        $this->assertTrue($called);
    }

    public function test_starts_with(): void
    {
        $this->assertTrue(starts_with('hello world', 'hello'));
        $this->assertFalse(starts_with('hello world', 'world'));
        $this->assertTrue(starts_with('hello world', ['world', 'hello']));
        $this->assertFalse(starts_with('hello world', ''));
    }

    public function test_ends_with(): void
    {
        $this->assertTrue(ends_with('hello world', 'world'));
        $this->assertFalse(ends_with('hello world', 'hello'));
        $this->assertTrue(ends_with('hello world', ['hello', 'world']));
        $this->assertFalse(ends_with('hello world', ''));
    }
}
