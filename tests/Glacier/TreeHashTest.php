<?php
namespace Aws\Test\Glacier;

use Aws\Glacier\TreeHash;
use Aws\Test\Polyfill\PHPUnit\PHPUnitCompatTrait;
use PHPUnit\Framework\TestCase;

class TreeHashTest extends TestCase
{
    use PHPUnitCompatTrait;

    /**
     * @covers Aws\Glacier\TreeHash::__construct
     * @covers Aws\Glacier\TreeHash::update
     * @covers Aws\Glacier\TreeHash::addChecksum
     * @covers Aws\Glacier\TreeHash::complete
     */
    public function testHashingIsHappeningCorrectly()
    {
        $chunks = [
            str_repeat('x', 1024 * 1024),
            str_repeat('x', 1024 * 1024 - 2),
            '1234567890',
            'foobar',
        ];

        $hash = new TreeHash('sha256');
        $hash->addChecksum(hash('sha256', $chunks[0]));
        $hash->update($chunks[1]);
        $hash->update($chunks[2]);
        $hash->update($chunks[3]);

        // Build expected tree hash
        $leaf1 = hash('sha256', $chunks[0], true);
        $leaf2 = hash('sha256', $chunks[1] . substr($chunks[2], 0, 2), true);
        $leaf3 = hash('sha256', substr($chunks[2], 2) . $chunks[3], true);
        $leaf1 = hash('sha256', $leaf1 . $leaf2, true);
        $expectedTreeHash = hash('sha256', $leaf1 . $leaf3, true);

        $this->assertSame($expectedTreeHash, $hash->complete());
    }

    /**
     * @covers Aws\Glacier\TreeHash::update
     */
    public function testCannotUpdateAfterHashCalculation()
    {
        $this->expectException(\LogicException::class);
        $hash = new TreeHash('sha256');
        $hash->update('foo');
        $hash->complete();

        $hash->update('bar');
    }

    /**
     * @covers Aws\Glacier\TreeHash::addChecksum
     */
    public function testCannotAddChecksumsAfterHashCalculation()
    {
        $this->expectException(\LogicException::class);
        $hash = new TreeHash('sha256');
        $hash->update('foo');
        $hash->complete();

        $hash->addChecksum('bar');
    }

    /**
     * @covers Aws\Glacier\TreeHash::reset
     */
    public function testCanResetHash()
    {
        $hash = new TreeHash('sha256');
        $hash->update('foo');
        $hash->reset();
        $hash->update('foo');

        $this->assertSame(hash('sha256', 'foo', true), $hash->complete());
    }

    /**
     * @covers Aws\Glacier\TreeHash::complete
     */
    public function testCanCalculateEmptyHash()
    {
        $hash = new TreeHash('sha256');

        $this->assertSame(TreeHash::EMPTY_HASH, bin2hex($hash->complete()));
    }

    /**
     * @covers Aws\Glacier\TreeHash::complete
     */
    public function testCanCalculateHashForSingleZero()
    {
        $data = "0";

        $hash = new TreeHash('sha256');
        $hash->update($data);

        $this->assertSame(hash('sha256', $data, true), $hash->complete());
    }
}
