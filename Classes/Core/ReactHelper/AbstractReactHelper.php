<?php
namespace byTorsten\React\Core\ReactHelper;

use Neos\Flow\Mvc\Controller\ControllerContext;

abstract class AbstractReactHelper implements ReactHelperInterface
{
    /**
     * @var ControllerContext
     */
    protected $controllerContext;

    /**
     * @param ControllerContext $controllerContext
     * @return mixed|void
     */
    public function setControllerContext(ControllerContext $controllerContext)
    {
        $this->controllerContext = $controllerContext;
    }
}
