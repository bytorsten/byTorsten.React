<?php
namespace byTorsten\React\Core\Rendering;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ControllerContext;
use React\Promise\ExtendedPromiseInterface;
use byTorsten\React\Core\Bundle;
use byTorsten\React\Core\IPC\App;
use byTorsten\React\Core\ReactHelper\ReactHelperManager;

class Renderer
{
    /**
     * @Flow\Inject
     * @var ReactHelperManager
     */
    protected $reactHelperManager;

    /**
     * @var App
     */
    protected $app;

    /**
     * @var ControllerContext
     */
    protected $controllerContext;

    /**
     * Renderer constructor.
     * @param App $app
     * @param ControllerContext $controllerContext
     */
    public function __construct(App $app, ControllerContext $controllerContext)
    {
        $this->app = $app;
        $this->controllerContext = $controllerContext;

        $this->app->on('rpc', function ($data, \Closure $reply) use ($controllerContext) {
            $helper = $data['helper'];
            unset($data['helper']);
            $response = $this->reactHelperManager->invokeHelper($controllerContext, $helper, $data);
            $reply($response);
        });
    }

    /**
     * @param string $identifier
     * @param string $filePath
     * @param Bundle $bundle
     * @param string|null $baseDirectory
     * @param array $context
     * @param array $internalData
     * @return ExtendedPromiseInterface
     */
    public function render(string $identifier, string $filePath, Bundle $bundle, string $baseDirectory = null, array $context = [], array $internalData = []): ExtendedPromiseInterface
    {
        return $this->app->call('render', [
            'identifier' => $identifier,
            'file' => $filePath,
            'bundle' => $bundle->toArray(),
            'context' => $context,
            'internalData' => $internalData,
            'resolvedPaths' => $bundle->getResolvedPaths(),
            'baseDirectory' => $baseDirectory
        ]);
    }

    /**
     * @param string $identifier
     * @param array $context
     * @param array $internalData
     * @return ExtendedPromiseInterface
     */
    public function shallowRender(string $identifier, array $context = [], array $internalData = []): ExtendedPromiseInterface
    {
        return $this->app->call('shallowRender', [
            'identifier' => $identifier,
            'context' => $context,
            'internalData' => $internalData
        ]);
    }
}
