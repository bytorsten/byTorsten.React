<?php
namespace byTorsten\React\Core\IPC;

use Neos\Flow\Annotations as Flow;
use Neos\Cache\Frontend\FrontendInterface;

/**
 * @Flow\Scope("singleton")
 */
class ProcessManager
{

    /**
     * @Flow\InjectConfiguration("script.keepAlive")
     * @var bool
     */
    protected $keepAlive;

    /**
     * @Flow\Inject
     * @var FrontendInterface
     */
    protected $cache;

    /**
     * @var ProcessInterface[]
     */
    protected $activeProcesses = [];

    /**
     * @var array
     */
    protected $lastParameters;

    /**
     *
     */
    public function initializeObject()
    {
        if ($this->keepAlive !== true) {
            \register_shutdown_function([$this, 'killAllActiveProcesses']);
        }
    }

    /**
     * @param string $script
     * @param array $parameters
     * @return ProcessInterface
     */
    public function getProcess(string $script, array $parameters = []): ProcessInterface
    {
        $identifier = md5($script);

        if ($this->cache->has($identifier)) {
            ['pid' => $pid, 'pipes' => $pipes, 'parameters' => $lastParameters] = $this->cache->get($identifier);

            if (posix_getpgid($pid) !== false) {
                $process = new ProxyProcess($pid, $pipes);
                $this->lastParameters = $lastParameters;
                return $process;
            }

            $this->cache->remove($identifier);
        }

        $process = new Process($script, $parameters);
        $process->ready()->then(function () use ($process, $identifier, $parameters) {

            $this->cache->set($identifier, [
                'identifier' => $identifier,
                'pid' => $process->getPid(),
                'pipes' => $process->getPipeNames(),
                'parameters' => $parameters
            ], ['process']);
        });

        $this->lastParameters = $parameters;
        $this->registerProcess($process);

        return $process;
    }

    /**
     * @return array|null
     */
    public function getLastParameters(): ?array
    {
        return $this->lastParameters;
    }

    /**
     * @param Process $process
     */
    public function registerProcess(Process $process)
    {
        if (!in_array($process, $this->activeProcesses)) {
            $this->activeProcesses[] = $process;

            $process->on('exit', function () use ($process) {
                $this->unregisterProcess($process);
            });
        }
    }

    /**
     * @param Process $process
     */
    public function unregisterProcess(Process $process)
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
        foreach ($processInfos as ['identifier' => $identifier, 'pid' => $pid, 'pipes' => $pipes]) {
            if (posix_getpgid($pid) !== false) {
                $count ++;
                $process = new ProxyProcess($pid, $pipes);
                $process->stop($force);
            }

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
            $process->stop(true);
        }
    }
}
