<?php
namespace byTorsten\React\Controller;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use byTorsten\React\Core\ReactHelper\ReactHelperManager;

class RpcController extends ActionController
{
    /**
     * @Flow\Inject
     * @var ReactHelperManager
     */
    protected $reactHelperManager;

    /**
     * @param string $helper
     * @param array $data
     * @return string
     */
    public function indexAction(string $helper, array $data = [])
    {
        $result = $this->reactHelperManager->invokeHelper($this->controllerContext, $helper, $data);
        return json_encode($result);
    }
}
