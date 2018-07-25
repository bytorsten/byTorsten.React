<?php
namespace byTorsten\React\Core\IPC;

use Neos\Flow\Annotations as Flow;
use Evenement\EventEmitter;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Promise\Promise;
use React\Socket\ConnectionInterface;
use React\Socket\Connector;

class Socket extends EventEmitter
{
    /**
     * @Flow\InjectConfiguration("script")
     * @var array
     */
    protected $configuration;

    /**
     * @var LoopInterface
     */
    protected $loop;

    /**
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * @var string
     */
    protected $domainSocketPath;

    /**
     * @var string
     */
    protected $storage;

    /**
     * @var int
     */
    protected $expectedLength = -1;

    /**
     * @param LoopInterface $loop
     * @param string $domainSocketPath
     */
    public function __construct(LoopInterface $loop, string $domainSocketPath)
    {
        $this->loop = $loop;
        $this->domainSocketPath = $domainSocketPath;
    }

    /**
     * @return Promise
     */
    public function connect(): Promise
    {
        $connector = new Connector($this->loop);
        $serverAddress = 'unix://' . $this->domainSocketPath;

        $deferred = new Deferred();
        $connector->connect($serverAddress)->then(function (ConnectionInterface $connection) use ($deferred) {
            $socketTimeout = $this->configuration['socketTimeout'];

            if ($socketTimeout > 0) {
                $timer = $this->loop->addTimer($socketTimeout, function () use ($connection, $socketTimeout) {
                    $connection->end();
                    throw new SocketException(sprintf('Socket was unable to process request in %s seconds', $socketTimeout));
                });

                $connection->on('close', function () use ($timer) {
                    $this->loop->cancelTimer($timer);
                    $this->emit('close');
                });
            } else {
                $connection->on('close', function () {
                    $this->emit('close');
                });
            }

            $connection->on('data', function ($chunk) {
                $this->handleData($chunk);
            });

            $this->connection = $connection;
            $deferred->resolve();
        })->otherwise(function (\Throwable $throwable) use ($deferred) {
            $deferred->reject($throwable);
        });

        return $deferred->promise();
    }

    /**
     * @param string $chunk
     * @throws SocketException
     * @throws \Throwable
     */
    protected function handleData(string $chunk)
    {
        if ($this->storage === null) {
            preg_match('/<\[\[([0-9]+)]]>(.+)/', $chunk, $matches);

            if ($matches === null) {
                $this->close();
                throw new SocketException('Malformed message: "' . $chunk . '"');
            }

            $this->expectedLength = $matches[1];
            $this->storage = $matches[2];
        } else {
            $this->storage .= $chunk;
        }

        if ($this->storage !== null && mb_strlen($this->storage) >= $this->expectedLength) {
            $message = mb_substr($this->storage, 0, $this->expectedLength);
            $left = mb_substr($this->storage, $this->expectedLength);

            $this->storage = null;
            $this->expectedLength = -1;

            $decodedMessage = json_decode($message, true);
            if ($decodedMessage === null) {
                $this->close();
                throw new SocketException('Message is not json decodable: "' . $message . '"');
            }

            if (!isset($decodedMessage['command'])) {
                throw new SocketException('Malformed message: "' . $message . '"');
            }

            $arguments = [$decodedMessage['data']];

            if (isset($decodedMessage['messageId'])) {
                $arguments[] = function ($data) use ($decodedMessage) {
                    $this->write($decodedMessage['messageId'], $data);
                };
            }

            try {
                $this->emit($decodedMessage['command'], $arguments);
            } catch (\Throwable $throwable) {
                $this->close();
                throw $throwable;
            }

            if (strlen($left) > 0) {
                $this->handleData($left);
            }
        }
    }

    /**
     *
     */
    public function close()
    {
        if ($this->connection !== null) {
            $this->connection->close();
            $this->connection = null;
        }
    }

    /**
     * @param string $command
     * @param null $data
     * @param string|null $messageId
     */
    public function write(string $command, $data = null, string $messageId = null)
    {
        $payload = [
            'command' => $command,
            'messageId' => $messageId,
            'data' => $data
        ];

        $encodedPayload = json_encode($payload);
        $message = '<[[' . mb_strlen($encodedPayload) . ']]>' . $encodedPayload;

        $this->connection->write($message);
    }
}
