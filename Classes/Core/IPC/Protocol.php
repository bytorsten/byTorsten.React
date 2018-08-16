<?php
namespace byTorsten\React\Core\IPC;

use Evenement\EventEmitter;

class Protocol extends EventEmitter
{

    /**
     * @var string
     */
    protected $message = null;

    /**
     * @var int
     */
    protected $expectedLength = -1;

    /**
     * @param string $data
     * @throws ProtocolException
     */
    public function add(string $data): void
    {
        if ($this->message === null) {
            if (substr($data,0, 3) === '<[[') {
                $data = substr($data, 3);
                $expectedLength = '';

                while (is_numeric($data[0])) {
                    $expectedLength .= $data[0];
                    $data = substr($data, 1);
                }

                if (substr($data, 0, 3) === ']]>') {
                    $this->expectedLength = (int) $expectedLength;
                    $this->message = substr($data, 3);
                }
            }

            if ($this->message === null) {
                throw new ProtocolException('Malformed message: "' . $data . '"');
            }
        } else {
            $this->message .= $data;
        }

        if ($this->message !== null && strlen($this->message) >= $this->expectedLength) {
            $message = substr($this->message, 0, $this->expectedLength);
            $decodedMessage = json_decode($message, true);

            if ($decodedMessage === null) {
                throw new ProtocolException('Message is not json decodable: "' . $decodedMessage . '"');
            }

            $this->emit('message', [$decodedMessage]);

            $left = substr($this->message, $this->expectedLength);
            $this->message = null;
            $this->expectedLength = -1;

            if (strlen($left) > 0) {
                $this->add($left);
            }
        }
    }

    /**
     * @param mixed $data
     * @return string
     */
    public function format($data): string
    {
        $payload = json_encode($data);
        return '<[[' . strlen($payload) . ']]>' . $payload;
    }
}
