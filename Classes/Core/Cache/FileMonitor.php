<?php
namespace byTorsten\React\Core\Cache;

use Neos\Flow\Annotations as Flow;
use Neos\Cache\Frontend\StringFrontend;
use Neos\Flow\Monitor\ChangeDetectionStrategy\ChangeDetectionStrategyInterface;
use Neos\Flow\SignalSlot\Dispatcher as SignalDispatcher;
use Neos\Flow\Monitor\FileMonitor as FlowFileMonitor;

class FileMonitor
{
    const FILE_MONITOR_IDENTIFIER = 'byTorsten_React_Files';

    /**
     * @Flow\Inject
     * @var StringFrontend
     */
    protected $cache;

    /**
     * @Flow\Inject
     * @var SignalDispatcher
     */
    protected $signalDispatcher;

    /**
     * Detects react file changes
     */
    public function detectChanges()
    {
        $this->signalDispatcher->connect(FlowFileMonitor::class, 'filesHaveChanged', function (string $fileMonitorIdentifier, array $changedFiles) {
            if ($fileMonitorIdentifier === static::FILE_MONITOR_IDENTIFIER) {
                foreach ($changedFiles as $changedFile => $status) {
                    if ($status === ChangeDetectionStrategyInterface::STATUS_DELETED) {
                        $this->unmonitorFile($changedFile);
                        $this->emitFileHasChanged($changedFile);
                    }

                    if ($status === ChangeDetectionStrategyInterface::STATUS_CHANGED) {
                        $this->emitFileHasChanged($changedFile);
                    }
                }
            }
        });

        $fileMonitor = new FlowFileMonitor(static::FILE_MONITOR_IDENTIFIER);
        $files = $this->cache->getByTag('file');

        foreach ($files as $file) {
            $fileMonitor->monitorFile($file);
        }

        $fileMonitor->detectChanges();
        $fileMonitor->shutdownObject();
    }

    /**
     * @param string $filename
     */
    public function monitorFile(string $filename)
    {
        $this->cache->set(md5($filename), $filename, ['file']);
    }

    /**
     * @param string $filename
     */
    protected function unmonitorFile(string $filename)
    {
        $this->cache->remove(md5($filename));
    }

    /**
     * @Flow\Signal
     * @param string $filename
     */
    public function emitFileHasChanged(string $filename)
    {
    }
}
