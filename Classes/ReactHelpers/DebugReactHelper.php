<?php
namespace byTorsten\React\ReactHelpers;

use byTorsten\React\Core\ReactHelper\AbstractReactHelper;

class DebugReactHelper extends AbstractReactHelper
{
    /**
     * @param array $messages
     */
    public function evaluate(array $messages): void
    {
        foreach ($messages as $message) {
            \Neos\Flow\var_dump($message);
        }
    }
}
