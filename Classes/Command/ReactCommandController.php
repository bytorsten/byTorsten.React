<?php
namespace byTorsten\React\Command;

use byTorsten\React\Core\IPC\ProcessManager;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cache\CacheManager;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Http\Request;
use Neos\Flow\Http\Response;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Controller\Arguments;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Flow\Package\Package;
use Neos\Flow\Package\PackageManagerInterface;
use byTorsten\React\Core\Bundling\Bundler;
use byTorsten\React\Core\IPC\App;
use byTorsten\React\Core\IPC\Unit;
use byTorsten\React\Core\Service\FilePathResolver;
use byTorsten\React\Core\Transpiling\Transpiler;
use React\ChildProcess\Process;
use React\EventLoop\Factory;

/**
 * @Flow\Scope("singleton")
 */
class ReactCommandController extends CommandController
{
    /**
     * @var string
     */
    protected $reactServerFilePattern = '@packageResourcesPath/Private/React/index.server.js';

    /**
     * @var string
     */
    protected $reactClientFilePattern = '@packageResourcesPath/Private/React/index.client.js';

    /**
     * @Flow\Inject
     * @var PackageManagerInterface
     */
    protected $packageManager;

    /**
     * @Flow\Inject
     * @var CacheManager
     */
    protected $cacheManager;

    /**
     * @Flow\Inject
     * @var ProcessManager
     */
    protected $processManager;

    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * Prepares a react bundle for a specific package
     *
     * @param string $packageKey
     * @param string|null $serverScript
     * @param string|null $clientScript
     */
    public function bundleCommand(string $packageKey, string $serverScript = null, string $clientScript = null)
    {
        $start = microtime(true);
        $filePathResolver = new FilePathResolver();
        /** @var Package $package */
        $package = $this->packageManager->getPackage($packageKey);
        if ($serverScript !== null) {
            $serverScript = $filePathResolver->resolveFilePath($serverScript);
        } else {
            $serverScript = str_replace('@packageResourcesPath', rtrim($package->getResourcesPath(), '/'), $this->reactServerFilePattern);
        }

        if ($clientScript !== null) {
            $clientScript = $filePathResolver->resolveFilePath($clientScript);
        } else {
            $clientScript = str_replace('@packageResourcesPath', rtrim($package->getResourcesPath(), '/'), $this->reactClientFilePattern);
        }

        $identifier = md5($serverScript);
        $unit = new Unit();

        $request = new ActionRequest(Request::createFromEnvironment());
        $uriBuilder = new UriBuilder();
        $uriBuilder->setRequest($request);

        $controllerContext = new ControllerContext($request, new Response(), new Arguments(), $uriBuilder);

        $unit->work(function (App $app) use ($clientScript, $serverScript, $identifier) {
            $this->outputLine();
            $this->output('Transpiling... ');

            $transpiler = new Transpiler($app);
            $transpiler->transpile($identifier, $serverScript, $clientScript)->done(function () use ($app) {
                $this->outputLine('<success>done</success>');
                $app->end();
            });
        });

        $bundler = new Bundler($controllerContext);
        $this->output('Bundling module... ');
        $bundler->bundle($identifier);
        $this->outputLine('<success>done</success>');

        $this->output('Bundling legacy... ');
        $bundler->bundle($identifier, true);
        $this->outputLine('<success>done</success>');

        $this->outputLine();
        $elapsed = round(microtime(true) - $start, 2);
        $this->outputLine('Bundled <success>%s</success> in <success>%s seconds</success>.', [$package->getPackageKey(), $elapsed]);
        $this->outputLine();
    }

    /**
     * Stops all background processes
     *
     * @param bool $force (if true, use SIG_KILL, otherwise use SIG_TERM)
     */
    public function restartProcessesCommand(bool $force = false)
    {
        $count = $this->processManager->killAllProcesses($force);
        $this->outputLine();

        if ($force === true) {
            $this->outputFormatted('<comment>force stopped %s</comment>', [
                $count === 1 ? '1 process' : $count . ' processes'
            ]);
        } else {
            $this->outputFormatted('<success>stopped %s</success>', [
                $count === 1 ? '1 process' : $count . ' processes'
            ]);
        }

        $this->outputLine();
        $this->outputFormatted('The processes will automatically restart during the next request');
        $this->outputLine();
    }
}
