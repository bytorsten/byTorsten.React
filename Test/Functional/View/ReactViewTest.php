<?php
namespace byTorsten\React\Tests\Functional;

use byTorsten\React\Core\IPC\Process\ProcessManager;
use byTorsten\React\Core\View\ReactView;
use Neos\Flow\Http\Request;
use Neos\Flow\Http\Response;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Controller\Arguments;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Flow\Tests\FunctionalTestCase;

class ReactViewTest extends FunctionalTestCase
{
    /**
     * @var ProcessManager
     */
    protected $processManager;

    /**
     *
     */
    public function setUp()
    {
        parent::setUp();
        $this->processManager = $this->objectManager->get(ProcessManager::class);
    }

    /**
     *
     */
    public function tearDown()
    {
        parent::tearDown();
        $this->processManager->killAllProcesses();
    }

    /**
     * @param array $options
     * @return ReactView
     */
    protected function buildView(array $options = []): ReactView
    {
        $view = new ReactView($options);

        $httpRequest = Request::createFromEnvironment();
        $request = new ActionRequest($httpRequest);

        $uriBuilder = new UriBuilder();
        $uriBuilder->setRequest($request);

        $controllerContext = new ControllerContext($request, new Response(), new Arguments(), $uriBuilder);
        $view->setControllerContext($controllerContext);


        return $view;
    }

    /**
     * @test
     */
    public function normalRender()
    {
        $view = $this->buildView();
        $view->setScriptPaths(__dir__ . '/Fixtures/simple.server.js');
        $result = $view->render();
        $this->assertEquals($result, 'works');
    }
}
