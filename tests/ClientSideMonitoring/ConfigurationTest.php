<?php

namespace Aws\Test\ClientSideMonitoring;

use Aws\ClientSideMonitoring\Configuration;
use Aws\Test\Polyfill\PHPUnit\PHPUnitCompatTrait;
use PHPUnit\Framework\TestCase;
use Psr\Log\InvalidArgumentException;


/**
 * @covers \Aws\ClientSideMonitoring\Configuration
 */
class ConfigurationTest extends TestCase
{
    use PHPUnitCompatTrait;

    public function testGetsCorrectValues()
    {
        $config = new Configuration(true, 'FooHost', 888, 'FooApp');
        $this->assertTrue($config->isEnabled());
        $this->assertSame('FooHost', $config->getHost());
        $this->assertSame(888, $config->getPort());
        $this->assertSame('FooApp', $config->getClientId());
    }

    public function testToArray()
    {
        $config = new Configuration(true, 'FooHost', 888, 'FooApp');
        $expected = [
            'enabled' => true,
            'host' => 'FooHost',
            'port' => 888,
            'client_id' => 'FooApp'
        ];
        $this->assertEquals($expected, $config->toArray());
    }

    public function testHandlesInvalidPort()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Configuration(true, 'invalidport', 'FooApp');
    }

    public function testHandlesInvalidEnabled()
    {
        $config = new Configuration('invalidvalue', 'FooHost', 123, 'FooApp');
        $this->assertFalse($config->isEnabled());
    }
}
