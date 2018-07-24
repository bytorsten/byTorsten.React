<?php
namespace byTorsten\React\ReactHelpers;

use byTorsten\React\Core\ReactHelper\AbstractReactHelper;

class DebugReactHelper extends AbstractReactHelper
{
    /**
     * @param mixed $message
     * @param string|null $title
     */
    public function evaluate($message, string $title = null): void
    {
        \Neos\Flow\var_dump($message, $title);
    }
}
