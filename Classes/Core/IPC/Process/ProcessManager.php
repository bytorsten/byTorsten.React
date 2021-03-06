<?php
namespace byTorsten\React\Core\IPC\Process;

use Neos\Flow\Annotations as Flow;
use Neos\Cache\Frontend\FrontendInterface;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;

/**
 * @Flow\Scope("singleton")
 */
class ProcessManager
{

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @Flow\Inject
     * @var FrontendInterface
     */
    protected $cache;

    /**
     * @var BaseProcessInterface[]
     */
    protected $activeProcesses = [];

    /**
     *
     */
    public function initializeObject()
    {
        \register_shutdown_function([$this, 'killAllActiveProcesses']);
    }

    /**
     * @param array $parameters
     * @return BaseProcessInterface
     */
    public function getProcess(array $parameters = []): BaseProcessInterface
    {
        $identifier = md5(implode($parameters));

        if ($this->cache->has($identifier)) {
            ['pid' => $pid, 'pipePaths' => $pipePaths, 'address' => $address] = $this->cache->get($identifier);
            /** @var ProxyProcessInterface $proxyProcess */
            $proxyProcess = $this->objectManager->get(ProxyProcessInterface::class, $pid, $pipePaths, $address);

            if ($proxyProcess->isAlive() === true) {
                return $proxyProcess;
            }

            $proxyProcess->stop();
            $this->cache->remove($identifier);
        }

        /** @var ProcessInterface $process */
        $process = $this->objectManager->get(ProcessInterface::class, $identifier, $parameters);
        $process->ready()->then(function () use ($process, $identifier, $parameters) {

            $this->cache->set($identifier, [
                'identifier' => $identifier,
                'pid' => $process->getPid(),
                'pipePaths' => $process->getPipePaths(),
                'address' => $process->getAddress()
            ], ['process']);
        });

        $this->registerProcess($process);

        return $process;
    }

    /**
     * @param ProcessInterface $process
     */
    public function registerProcess(ProcessInterface $process)
    {
        if (!in_array($process, $this->activeProcesses)) {
            $this->activeProcesses[] = $process;

            $process->on('exit', function () use ($process) {
                $this->unregisterProcess($process);
            });
        }
    }

    /**
     * @param ProcessInterface $process
     */
    public function unregisterProcess(ProcessInterface $process)
    {
        $this->activeProcesses = array_filter($this->activeProcesses, function ($currentProcess) use ($process) {
            return $currentProcess !== $process;
        });
    }

    /**
     * @param bool $force
     * @return int
     */
    public function killAllProcesses(bool $force = false): int
    {
        $count = 0;
        $processInfos = $this->cache->getByTag('process');
        foreach ($processInfos as ['identifier' => $identifier, 'pid' => $pid, 'pipePaths' => $pipePaths, 'address' => $address]) {
            /** @var ProxyProcessInterface $proxyProcess */
            $proxyProcess = $this->objectManager->get(ProxyProcessInterface::class, $pid, $pipePaths, $address);

            if ($proxyProcess->isAlive()) {
                $count++;
            }

            $proxyProcess->stop($force);
            $this->cache->remove($identifier);
        }

        return $count;
    }

    /**
     *
     */
    public function killAllActiveProcesses()
    {
        foreach ($this->activeProcesses as $process) {
            if ($process->keepAlive() === false) {
                $process->stop();
            }
        }
    }
}
