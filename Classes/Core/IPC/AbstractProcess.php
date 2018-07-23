<?php
namespace byTorsten\React\Core\IPC;

use Neos\Flow\Annotations as Flow;
use byTorsten\React\Log\ReactLoggerInterface;
use Evenement\EventEmitter;

abstract class AbstractProcess extends EventEmitter implements ProcessInterface
{
    const READY_FLAG = '[[READY]]';

    const SIGKILL = 9;
    const SIGTERM = 15;

    /**
     * @Flow\Inject
     * @var ReactLoggerInterface
     */
    protected $logger;

    /**
     * @var array
     */
    protected $errors = [];

    /**
     * @return bool
     */
    public function emitErrors(): bool
    {
        $errors = $this->errors;
        $mapErrors = function () use (&$errors, &$mapErrors) {
            if (count($errors) === 0) {
                return null;
            }

            $error = array_pop($errors);
            $lines = array_map(function ($line) {
                return trim($line);
            }, explode(PHP_EOL, $error));

            $message = implode(PHP_EOL, array_filter($lines));
            return new ProcessException($message, 1531141393, $mapErrors());
        };

        $exception = $mapErrors();

        if ($exception) {
            $this->emit('error', [$exception]);
            return true;
        }

        return false;
    }
}
