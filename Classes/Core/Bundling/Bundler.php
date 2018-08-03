<?php
namespace byTorsten\React\Core\Bundling;

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
     * @param Bundle $bundle
     * @param string $filename
     * @return Bundle
     */
    protected function stripServerModule(Bundle $bundle, string $filename): Bundle
    {
        $bundle->removeModule(basename($filename));
        return $bundle;
    }

    /**
     * @param string $identifier
     * @return Bundle
     * @throws BundlingException
     */
    public function bundle(string $identifier)
    {
        $clientScriptPath = $this->fileManager->getClientScriptPath($identifier);
        $serverBundle = $this->fileManager->getServerBundle($identifier);
        $meta = $this->fileManager->getBundleMeta($identifier);

        $baseBundle = $this->stripServerModule($serverBundle, $this->fileManager->getServerScriptPath($identifier));

        /** @var Bundle $clientBundle */
        $clientBundle = null;

        $unit = new Unit($this->controllerContext);
        $unit->work(function (App $app) use ($clientScriptPath, $baseBundle, $identifier, $meta, &$clientBundle) {
            $app->call('bundle', [
                'identifier' => $identifier,
                'chunkPath' => $identifier,
                'file' => $clientScriptPath,
                'baseBundle' => $baseBundle->toArray(),
                'baseDirectory' => $meta->getBaseDirectory(),
                'hypotheticalFiles' => $meta->getHypotheticalFiles(),
                'aliases' => array_merge($meta->getAliases(), $baseBundle->getResolvedPaths()),
                'externals' => $meta->getExternals()
            ])->done(function (array $bundleResult) use ($app, $identifier, &$clientBundle) {
                $clientBundle = Bundle::create($bundleResult);
                $this->fileManager->persistClientBundle($identifier, $clientBundle);

                $app->end();
            });
        });

        if ($clientBundle === null) {
            throw new BundlingException('Bundling failed');
        }

        return $clientBundle;
    }
}
