<?php
namespace byTorsten\React\Tests\Functional;

use byTorsten\React\Core\IPC\Protocol;
use byTorsten\React\Core\IPC\ProtocolException;
use Neos\Flow\Tests\UnitTestCase;

class ProtocolTest extends UnitTestCase
{
    /**
     * @test
     */
    public function throwMalformed()
    {
        $protocol = new Protocol();
        $this->expectException(ProtocolException::class);
        $protocol->add('malformed');
    }

    /**
     * @test
     */
    public function throwNotDecodable()
    {
        $protocol = new Protocol();
        $this->expectException(ProtocolException::class);
        $protocol->add('<3>abc');
    }

    /**
     * @test
     */
    public function decode()
    {
        $protocol = new Protocol();
        $receivedMessage = null;
        $protocol->on('message', function ($message) use (&$receivedMessage) {
            $receivedMessage = $message;
        });

        $protocol->add('<[[10]]>1234567890');

        $this->assertEquals(getType($receivedMessage), 'integer');
        $this->assertEquals($receivedMessage, 1234567890);
    }

    /**
     * @test
     */
    public function decodeMulti()
    {
        $protocol = new Protocol();
        $receiveMessages = [];
        $protocol->on('message', function ($message) use (&$receiveMessages) {
            $receiveMessages []= $message;
        });

        $protocol->add('<[[5]]>123');
        $protocol->add('45<[[3]]>12');
        $protocol->add('3');

        $this->assertEquals($receiveMessages[0], 12345);
        $this->assertEquals($receiveMessages[1], 123);
    }

    /**
     * @test
     */
    public function formatSimple()
    {
        $protocol = new Protocol();
        $formattedMessage = $protocol->format('this is a test');

        $this->assertEquals($formattedMessage, '<[[16]]>"this is a test"');
    }

    /**
     * @test
     */
    public function formatComplex()
    {
        $protocol = new Protocol();
        $payload = [ 'complex' => [ 'structure' => 123 ] ];
        $receivedPayload = null;
        $protocol->on('message', function ($message) use (&$receivedPayload) {
            $receivedPayload = $message;
        });

        $protocol->add($protocol->format($payload));

        $this->assertSame($payload, $receivedPayload);
    }
}
