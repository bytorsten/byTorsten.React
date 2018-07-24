<?php
namespace byTorsten\React\Core\IPC\Process;

interface ProcessInterface extends BaseProcessInterface
{
    /**
     * @return int|null
     */
    public function getPid(): ?int;

    /**
     * @return array
     */
    public function getPipePaths(): array;
}
