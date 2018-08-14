<?php
namespace byTorsten\React\Core\IPC;

use byTorsten\React\Core\ReactHelper\ReactHelperManager;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Utility\Environment;
use React\EventLoop\Factory as EventLoopFactory;
use byTorsten\React\Core\IPC\Process\ProcessException;
use byTorsten\React\Core\IPC\Process\ProxyProcessInterface;
use byTorsten\React\Core\IPC\Process\ProcessManager;

class Unit
{

    /**
     * @Flow\Inject
     * @var ProcessManager
     */
    protected $processManager;

    /**
     * @Flow\Inject
     * @var Environment
     */
    protected $environment;

    /**
     * @Flow\Inject
     * @var ReactHelperManager
     */
    protected $reactHelperManager;

    /**
     * @var ControllerContext
     */
    protected $controllerContext;


    /**
     * @param ControllerContext $controllerContext
     */
    public function __construct(ControllerContext $controllerContext)
    {
        $this->controllerContext = $controllerContext;
    }

    /**
     * @param \Closure $processor
     * @return mixed
     * @throws \Exception
     */
    public function work(\Closure $processor)
    {
        $process = $this->processManager->getProcess([
            'production' => $this->environment->getContext()->isProduction()
        ]);

        $loop = EventLoopFactory::create();

        $process->start($loop);

        /** @var \Exception $throwable */
        $throwable = null;
        $result = null;

        $process->on('error', function (\Throwable $error) use (&$throwable) {
            $throwable = $error;
        });

        $process->ready()->done(function () use ($process, $loop, $processor, &$result, &$throwable) {

            $socket = new Socket($loop, $process->getAddress());
            $app = new App($socket, $this->controllerContext, $process);

            $app->on('rpc', function ($data, \Closure $reply) use ($app) {
                $helper = $data['helper'];
                unset($data['helper']);
                $response = $this->reactHelperManager->invokeHelper($app->getControllerContext(), $helper, $data);
                $reply($response);
            });

            $process->on('error', function (ProcessException $error) use ($app, $socket, $loop, &$throwable) {
                $app->cancelAllPromises($error);
                $socket->close();
                $loop->stop();
                $throwable = $error;
            });

            $socket->on('close', function () use ($process, $loop) {
                if ($process->emitErrors()) {
                    return;
                }

                if ($process->keepAlive() === true) {
                    // stopping the loop without terminating the process will keep it alive
                    $process->detach();
                    $loop->stop();
                } else {
                    $process->stop();
                }
            });

            $socket->connect()->then(function () use ($processor, $app, &$result) {
                $result = $processor($app);
            })->otherwise(function (\Throwable $error) use ($process, $app, $socket, &$throwable) {
                $throwable = $error;
                $socket->close();
                $app->cancelAllPromises($error);
                $process->stop();
            });
        });

        $loop->run();

        if ($throwable !== null) {
            // when something bad happens, the process needs to be stopped, even when it is configured to keep alive.
            $process->stop();

            throw $throwable;
        }

        return $result;
    }
}
