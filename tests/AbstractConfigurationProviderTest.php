<?php

namespace Aws\Test;

use Aws\LruArrayCache;
use Aws\Result;
use Aws\ResultInterface;
use Aws\Test\Polyfill\PHPUnit\PHPUnitCompatTrait;
use GuzzleHttp\Promise;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Aws\AbstractConfigurationProvider
 */
class AbstractConfigurationProviderTest extends TestCase
{
    use PHPUnitCompatTrait;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $provider;

    public function __construct()
    {
        parent::__construct();
        $this->provider = $this->getMockForAbstractClass('\Aws\AbstractConfigurationProvider');
    }

    public function testGetsHomeDirectoryForWindowsUsers()
    {
        putenv('HOME=');
        putenv('HOMEDRIVE=C:');
        putenv('HOMEPATH=\\My\\Home');
        $ref = new \ReflectionClass('\Aws\AbstractConfigurationProvider');
        $meth = $ref->getMethod('getHomeDir');
        $meth->setAccessible(true);
        $this->assertSame('C:\\My\\Home', $meth->invoke(null));
    }

    public function testMemoizes()
    {
        $called = 0;
        $expected = ['expected', 'value'];
        $f = function () use (&$called, $expected) {
            $called++;
            return Promise\Create::promiseFor($expected);
        };
        $p = call_user_func([$this->provider, 'memoize'], $f);
        $this->assertSame($expected, $p()->wait());
        $this->assertSame(1, $called);
        $this->assertSame($expected, $p()->wait());
        $this->assertSame(1, $called);
    }

    public function testChainsConfiguration()
    {
        $expected = ['expected', 'value'];
        $a = function () {
            return Promise\Create::rejectionFor(new \Exception('Failure'));
        };
        $b = function () use ($expected) {
            return Promise\Create::promiseFor($expected);
        };
        $c = function () {
            $this->fail('Should not have called');
        };
        $chained = call_user_func([$this->provider, 'chain'], $a, $b, $c);
        $result = $chained()->wait();
        $this->assertSame($expected, $result);
    }

    public function testChainThrowsExceptionOnEmptyArgs()
    {
        $this->expectException(\InvalidArgumentException::class);
        call_user_func([$this->provider, 'chain']);
    }

    public function testsPersistsToCache()
    {
        $cache = new LruArrayCache();
        $expected = new Result(['expected_key' => 'expected_value']);

        // Set interfaceClass property that's normally set by child class
        $ref = new \ReflectionClass('\Aws\AbstractConfigurationProvider');
        $property = $ref->getProperty('interfaceClass');
        $property->setAccessible(true);
        $property->setValue('\Aws\ResultInterface');

        $timesCalled = 0;
        $volatileProvider = function () use ($expected, &$timesCalled) {
            if (0 === $timesCalled) {
                ++$timesCalled;
                return Promise\Create::promiseFor($expected);
            }

            throw new \BadFunctionCallException('I was called too many times!');
        };

        for ($i = 0; $i < 10; $i++) {
            /** @var ResultInterface $result */
            $result = call_user_func(
                call_user_func([$this->provider, 'cache'], $volatileProvider, $cache)
            )->wait();
        }

        $this->assertSame(1, $timesCalled);
        $this->assertCount(1, $cache);
        $this->assertSame($expected->toArray(), $result->toArray());
    }
}
