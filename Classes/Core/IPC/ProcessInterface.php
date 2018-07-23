<?php
namespace byTorsten\React\Core\IPC;

use Evenement\EventEmitterInterface;
use React\EventLoop\LoopInterface;
use React\Promise\ExtendedPromiseInterface;

interface ProcessInterface extends EventEmitterInterface
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
}
