<?php
namespace byTorsten\React;

use byTorsten\React\Core\IPC\ProcessManager;
use byTorsten\React\Log\ReactLoggerInterface;
use Neos\Flow\Cache\CacheManager;
use Neos\Flow\Core\Booting\Sequence;
use Neos\Flow\Core\Booting\Step;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Mvc\Dispatcher;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Package\Package as BasePackage;
use byTorsten\React\Core\Cache\FileMonitor;
use Neos\Flow\Monitor\FileMonitor as FlowFileMonitor;

class Package extends BasePackage
{
    /**
     * @param Bootstrap $bootstrap
     * @return void
     */
    public function boot(Bootstrap $bootstrap)
    {

        if ($bootstrap->getContext()->isDevelopment()) {
            $dispatcher = $bootstrap->getSignalSlotDispatcher();
            $dispatcher->connect(FileMonitor::class, 'fileHasChanged', function (string $filename) use ($bootstrap) {
                $objectManager = $bootstrap->getObjectManager();

                /** @var CacheManager $cacheManager */
                $cacheManager = $objectManager->get(CacheManager::class);
                $tag = md5($filename);

                $fileCache = $cacheManager->getCache('byTorsten_React_File');
                $fileCache->flushByTag($tag);
            });

            $dispatcher->connect(Dispatcher::class, 'beforeControllerInvocation', function () use ($bootstrap) {
                $objectManager = $bootstrap->getObjectManager();
                /** @var FileMonitor $fileMonitor */
                $fileMonitor = $objectManager->get(FileMonitor::class);
                $fileMonitor->detectChanges();
            });

            $dispatcher->connect(FlowFileMonitor::class, 'filesHaveChanged', function (string $identifier, array $filenames) use ($bootstrap, $dispatcher) {
                if ($identifier === 'Flow_ConfigurationFiles') {
                    $stopProcess = false;
                    $configurationPath = $this->getConfigurationPath();

                    foreach ($filenames as $filename => $_) {
                        if (strpos($filename, $configurationPath) === 0) {
                            $stopProcess = true;
                            break;
                        }
                    }

                    if ($stopProcess === true) {
                        $dispatcher->connect(Dispatcher::class, 'beforeControllerInvocation', function () use ($bootstrap) {
                            $objectManager = $bootstrap->getObjectManager()->get(ObjectManagerInterface::class);

                            /** @var ProcessManager $processManager */
                            $processManager = $objectManager->get(ProcessManager::class);
                            $count = $processManager->killAllProcesses();

                            /** @var ReactLoggerInterface $logger */
                            $logger = $objectManager->get(ReactLoggerInterface::class);
                            $logger->info('Stopped ' . ($count === 1 ? '1 process' : $count . ' processes') . ' due to configuration file change');
                        });
                    }
                }
            });
        }
    }
}
