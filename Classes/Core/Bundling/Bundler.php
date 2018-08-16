<?php
namespace byTorsten\React\Core\Bundling;

use byTorsten\React\Core\ReactHelper\ReactHelperManager;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Utility\Environment;
use byTorsten\React\Core\Bundle;
use byTorsten\React\Core\Cache\FileManager;
use byTorsten\React\Core\IPC\App;
use byTorsten\React\Core\IPC\Unit;

class Bundler
{
    /**
     * @Flow\InjectConfiguration("scriptParameter")
     * @var array
     */
    protected $scriptParameter;

    /**
     * @Flow\Inject
     * @var Environment
     */
    protected $environment;

    /**
     * @Flow\Inject
     * @var FileManager
     */
    protected $fileManager;

    /**
     * @Flow\Inject
     * @var ReactHelperManager
     */
    protected $reactHelperManager;

    /**
     * @var ControllerContext
     */
    protected $controllerContext;

    /**
     * @param ControllerContext $controllerContext
     */
    public function __construct(ControllerContext $controllerContext)
    {
        $this->controllerContext = $controllerContext;
    }

    /**
     * @param string $identifier
     * @return Bundle
     * @throws BundlingException
     */
    public function bundle(string $identifier)
    {
        $configuration = $this->fileManager->getConfiguration($identifier);

        /** @var Bundle $clientBundle */
        $clientBundle = null;

        $unit = new Unit($this->controllerContext);
        $unit->work(function (App $app) use ($configuration, &$clientBundle) {
            $app->call('bundle', $configuration->toBundlerConfiguration())->done(function (array $bundleResult) use ($app, $configuration, &$clientBundle) {
                $clientBundle = Bundle::create($bundleResult);
                $this->fileManager->persistClientBundle($configuration->getIdentifier(), $clientBundle);
                $app->end();
            });
        });

        if ($clientBundle === null) {
            throw new BundlingException('Bundling failed');
        }

        return $clientBundle;
    }
}
