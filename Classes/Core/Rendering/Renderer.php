<?php
namespace byTorsten\React\Core\Rendering;

use byTorsten\React\Core\View\ViewConfiguration;
use React\Promise\ExtendedPromiseInterface;
use byTorsten\React\Core\Bundle;
use byTorsten\React\Core\IPC\App;

class Renderer
{
    /**
     * @var App
     */
    protected $app;


    /**
     * @param App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * @param ViewConfiguration $configuration
     * @param Bundle $bundle
     * @param array $context
     * @return ExtendedPromiseInterface
     */
    public function render(ViewConfiguration $configuration, Bundle $bundle, array $context = []): ExtendedPromiseInterface
    {
        return $this->app->call('render', [
            'identifier' => $configuration->getIdentifier(),
            'bundle' => $bundle->toArray(),
            'context' => $context,
            'internalData' => $configuration->getInternalData()
        ]);
    }

    /**
     * @param ViewConfiguration $configuration
     * @param array $context
     * @return ExtendedPromiseInterface
     */
    public function shallowRender(ViewConfiguration $configuration, array $context = []): ExtendedPromiseInterface
    {
        return $this->app->call('shallowRender', [
            'identifier' => $configuration->getIdentifier(),
            'context' => $context,
            'internalData' => $configuration->getInternalData()
        ]);
    }
}
