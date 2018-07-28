<?php
namespace byTorsten\React\Core\IPC\Process;

interface ProxyProcessInterface extends BaseProcessInterface
{
    /**
     * @return bool
     */
    public function isAlive(): bool;
}
