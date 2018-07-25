<?php
namespace byTorsten\React\Core\Transpiling;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Utility\Environment;
use React\Promise\ExtendedPromiseInterface;
use React\Promise\FulfilledPromise;
use byTorsten\React\Core\Cache\FileManager;
use byTorsten\React\Core\ReactHelper\ReactHelperManager;
use byTorsten\React\Core\IPC\App;
use byTorsten\React\Core\Bundle;
use byTorsten\React\Core\View\BundlerHelper;

class Transpiler
{

    /**
     * @Flow\Inject
     * @var Environment
     */
    protected $environment;

    /**
     * @Flow\Inject
     * @var ReactHelperManager
     */
    protected $reactHelperManager;

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
     * @param Bundle $bundle
     * @param string $filename
     * @return Bundle
     */
    protected function stripClientModule(Bundle $bundle, string $filename): Bundle
    {
        $bundle->removeModule(basename($filename));
        return $bundle;
    }

    /**
     * @param string $identifier
     * @param Bundle $assetBundle
     * @return Bundle
     */
    public function addAssetSourceMapUrls(string $identifier, Bundle $assetBundle): Bundle
    {
        if ($this->environment->getContext()->isProduction()) {
            return $assetBundle;
        }

        $uriBuilder = $this->app->getControllerContext()->getUriBuilder();
        $dummyUri = $uriBuilder->uriFor('asset', ['identifier' => $identifier, 'chunkname' => '__filename__.map'], 'Chunk', 'byTorsten.React');

        foreach ($assetBundle->getModules() as $filename => $module) {
            if ($module->getMap() !== null) {
                $sourceMapUrl = str_replace('__filename__', $filename, $dummyUri);
                $module->appendCode(sprintf('/*# sourceMappingURL=%s */', $sourceMapUrl) . PHP_EOL);
            }
        }

        return $assetBundle;
    }

    /**
     * @param string $identifier
     * @param string $serverScript
     * @param string $clientScript
     * @param array $hypotheticalFiles
     * @param array $aliases
     * @param array $additionalDependencies
     * @param BundlerHelper|null $bundleHelper
     * @return ExtendedPromiseInterface
     */
    public function transpile(string $identifier, string $serverScript, string $clientScript, array $hypotheticalFiles = [], array $aliases = [], array $additionalDependencies = [], BundlerHelper $bundleHelper = null): ExtendedPromiseInterface
    {
        if ($this->fileManager->hasServerCode($identifier)) {
            $bundle = $this->stripClientModule($this->fileManager->getServerBundle($identifier), $clientScript);
            return new FulfilledPromise($bundle);
        }

        return $this->app->call('transpile', [
            'identifier' => $identifier,
            'serverFile' => $serverScript,
            'clientFile' => $clientScript,
            'helpers' => $this->reactHelperManager->generateHelperInfos(),
            'extractDependencies' => $this->environment->getContext()->isDevelopment(),
            'hypotheticalFiles' => $hypotheticalFiles,
            'aliases' => $aliases
        ])->then(function (array $transpileResult) use ($identifier, $serverScript, $clientScript, $additionalDependencies, $bundleHelper) {
            ['bundle' => $rawBundle, 'dependencies' => $dependencies, 'resolvedPaths' => $resolvedPaths, 'assets' => $assets] = $transpileResult;

            $allDependencies = array_merge($dependencies, $additionalDependencies);
            $bundle = Bundle::create($rawBundle, $resolvedPaths);

            $this->fileManager->persistServerBundle($identifier, $clientScript, $serverScript, $bundle, $allDependencies);
            if ($bundleHelper !== null) {
                $this->fileManager->persistBundleMeta($identifier, $bundleHelper);
            }

            $assetsBundle = $this->addAssetSourceMapUrls($identifier, Bundle::create($assets));
            $this->fileManager->persistAssets($identifier, $assetsBundle, $this->app->getControllerContext());

            return $this->stripClientModule($bundle, $clientScript);
        });
    }
}
