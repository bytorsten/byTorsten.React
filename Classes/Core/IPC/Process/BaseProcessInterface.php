<?php
namespace byTorsten\React\Core\IPC\Process;

use Evenement\EventEmitterInterface;
use React\EventLoop\LoopInterface;
use React\Promise\ExtendedPromiseInterface;

interface BaseProcessInterface extends EventEmitterInterface
{
    /**
     * @param LoopInterface $loop
     */
    public function start(LoopInterface $loop): void;

    /**
     * @param bool $force
     */
    public function stop(bool $force = false): void;

    /**
     *
     */
    public function detach(): void;

    /**
     * @return bool
     */
    public function emitErrors(): bool;

    /**
     * @return ExtendedPromiseInterface
     */
    public function ready(): ExtendedPromiseInterface;

    /**
     * @return bool
     */
    public function keepAlive(): bool;
}
