<?php
namespace Aws\Test\Crypto;

use Aws\Crypto\MetadataEnvelope;
use Aws\Test\Polyfill\PHPUnit\PHPUnitCompatTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers Aws\Crypto\MetadataEnvelope
 */
class MetadataEnvelopeTest extends TestCase
{
    use PHPUnitCompatTrait;
    use UsesMetadataEnvelopeTrait;

    /**
     * @dataProvider getIndividualMetadataFields
     */
    public function testSetsValidFields($field, $value)
    {
        $envelope = new MetadataEnvelope();
        $envelope[$field] = $value;

        $this->assertEquals($value, $envelope[$field]);
    }

    /**
     * @dataProvider getMetadataFields
     */
    public function testSetsAllFields($allValidFields)
    {
        $envelope = new MetadataEnvelope();
        foreach ($allValidFields as $field => $value) {
            $envelope[$field] = $value;
        }

        $this->assertEquals(
            json_encode($allValidFields),
            json_encode($envelope)
        );
    }

    /**
     * @dataProvider getIndividualInvalidMetadataFields
     */
    public function testThrowsOnInvalidMetadataField($field, $value)
    {
        $this->expectException(\InvalidArgumentException::class);
        $envelope = new MetadataEnvelope();
        $envelope[$field] = $value;
    }
}
