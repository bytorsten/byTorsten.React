<?php
namespace byTorsten\React\Core\ReactHelper;

use Neos\Flow\Mvc\Controller\ControllerContext;

interface ReactHelperInterface
{
    /**
     * @param ControllerContext $controllerContext
     * @return mixed
     */
    public function setControllerContext(ControllerContext $controllerContext);
}
