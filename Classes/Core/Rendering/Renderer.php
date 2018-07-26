<?php
namespace byTorsten\React\Core\Rendering;

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
