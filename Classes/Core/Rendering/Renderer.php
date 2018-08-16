<?php
namespace byTorsten\React\Core\Rendering;

use Neos\Flow\Annotations as Flow;
use React\Promise\ExtendedPromiseInterface;
use byTorsten\React\Core\Cache\FileManager;
use byTorsten\React\Core\View\ViewConfiguration;
use byTorsten\React\Core\Bundle;
use byTorsten\React\Core\IPC\App;

class Renderer
{
    /**
     * @Flow\Inject
     * @var FileManager
     */
    protected $fileManager;

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
        $renderConfiguration = $configuration->toRendererConfiguration();
        $renderConfiguration['bundle'] = $bundle->toArray();
        $renderConfiguration['context'] = $context;
        $renderConfiguration['excluded'] = $this->fileManager->getExclusion($configuration->getIdentifier());

        return $this->app->call('render', $renderConfiguration);
    }

    /**
     * @param ViewConfiguration $configuration
     * @param array $context
     * @return ExtendedPromiseInterface
     */
    public function shallowRender(ViewConfiguration $configuration, array $context = []): ExtendedPromiseInterface
    {
        $renderConfiguration = $configuration->toRendererConfiguration();
        $renderConfiguration['context'] = $context;

        return $this->app->call('shallowRender', $renderConfiguration);
    }
}
