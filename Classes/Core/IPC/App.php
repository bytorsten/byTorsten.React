<?php
namespace byTorsten\React\Core\IPC;

use byTorsten\React\Core\IPC\Process\BaseProcessInterface;
use byTorsten\React\Core\IPC\Process\ProcessInterface;
use byTorsten\React\Core\IPC\Process\ProxyProcessInterface;
use Neos\Flow\Utility\Algorithms;
use Neos\Flow\Mvc\Controller\ControllerContext;
use React\Promise\Deferred;
use React\Promise\Promise;

class App
{
    /**
     * @var Socket
     */
    protected $socket;

    /**
     * @var Deferred[]
     */
    protected $deferrers = [];

    /**
     * @var int
     */
    protected $messageIndex = 0;

    /**
     * @var string
     */
    protected $messagePrefix;

    /**
     * @var ControllerContext
     */
    protected $controllerContext;

    /**
     * @var ProcessInterface
     */
    protected $process;

    /**
     * @param Socket $socket
     * @param ControllerContext $controllerContext
     * @param BaseProcessInterface $process
     */
    public function __construct(Socket $socket, ControllerContext $controllerContext, BaseProcessInterface $process)
    {

        $this->socket = $socket;
        $this->controllerContext = $controllerContext;
        $this->process = $process;
        $this->messagePrefix = Algorithms::generateRandomString(8);
    }

    /**
     * @param string $eventName
     * @param \Closure $callback
     */
    public function on(string $eventName, \Closure $callback)
    {
        $this->socket->on($eventName, $callback);
    }

    /**
     * @return bool
     */
    public function isProxy(): bool
    {
        return $this->process instanceof ProxyProcessInterface;;
    }

    /**
     * @return bool
     */
    public function willSurvive(): bool
    {
        return $this->process->keepAlive();
    }

    /**
     * @param string $command
     * @param $data
     * @return Promise
     */
    public function call(string $command, $data = null): Promise
    {
        $this->messageIndex++;
        $deferred = new Deferred();

        $messageId = 'message_' . $this->messagePrefix . '_' . $this->messageIndex;

        $this->deferrers[$messageId] = $deferred;

        $this->socket->once($messageId, function ($data) use ($deferred, $messageId) {
            $deferred->resolve($data);
            unset($this->deferrers[$messageId]);
        });

        $this->socket->write($command, $data, $messageId);
        return $deferred->promise();
    }

    /**
     * @param \Throwable|null $throwable
     */
    public function cancelAllPromises(\Throwable $throwable = null)
    {
        foreach ($this->deferrers as $type => $deferred) {
            unset($this->deferrers[$type]);
            $deferred->reject($throwable);
        }
    }

    /**
     * @return ControllerContext
     */
    public function getControllerContext(): ControllerContext
    {
        return $this->controllerContext;
    }

    /**
     *
     */
    public function end()
    {
        $this->socket->close();
    }
}
