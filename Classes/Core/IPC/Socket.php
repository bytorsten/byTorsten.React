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
    protected $address;

    /**
     * @var Protocol
     */
    protected $protocol;

    /**
     * @param LoopInterface $loop
     * @param string $address
     */
    public function __construct(LoopInterface $loop, string $address)
    {
        $this->loop = $loop;
        $this->address = $address;
        $this->protocol = new Protocol();

        $this->protocol->on('message', function ($message) {
            $arguments = [$message['data']];

            if (isset($message['messageId'])) {
                $arguments[] = function ($data) use ($message) {
                    $this->write($message['messageId'], $data);
                };
            }

            try {
                $this->emit($message['command'], $arguments);
            } catch (\Throwable $throwable) {
                $this->close();
                throw $throwable;
            }
        });
    }

    /**
     * @return Promise
     */
    public function connect(): Promise
    {
        $connector = new Connector($this->loop);
        $deferred = new Deferred();

        $connector->connect($this->address)->then(function (ConnectionInterface $connection) use ($deferred) {
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
     * @throws ProtocolException
     */
    protected function handleData(string $chunk)
    {
        try {
            $this->protocol->add($chunk);
        } catch (ProtocolException $exception) {
            $this->close();
            throw $exception;
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

        $this->connection->write($this->protocol->format($payload));
    }
}
