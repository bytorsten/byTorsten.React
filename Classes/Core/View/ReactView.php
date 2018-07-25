<?php
namespace byTorsten\React\Core\View;

use byTorsten\React\Core\Cache\FileManager;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Exception;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Mvc\View\AbstractView;
use Neos\Flow\Mvc\View\ViewInterface;
use Neos\Flow\Package;
use Neos\Flow\Package\PackageManagerInterface;
use Neos\Utility\TypeHandling;
use React\Promise\Deferred;
use byTorsten\React\Core\Bundle;
use byTorsten\React\Core\IPC\App;
use byTorsten\React\Core\IPC\Unit;
use byTorsten\React\Core\Rendering\Renderer;
use byTorsten\React\Core\Service\FilePathResolver;
use byTorsten\React\Core\Transpiling\Transpiler;

class ReactView extends AbstractView
{
    /**
     * @var array
     */
    protected $supportedOptions = [
        'reactServerFilePattern' => ['resource://@package/Private/React/index.server.js', 'JS file responsible for server side rendering', 'string'],
        'reactClientFilePattern' => ['resource://@package/Private/React/index.client.js', 'JS file responsible for client side rendering', 'string'],
        'identifier' => [null, 'Unique identifier for creating the client bundle. If not given, is automatically derived from the server file path.', 'string']
    ];

    /**
     * @Flow\Inject
     * @var PackageManagerInterface
     */
    protected $packageManager;

    /**
     * @Flow\Inject
     * @var FileManager
     */
    protected $fileManager;

    /**
     * @var array
     */
    protected $internalData = [];

    /**
     * @var array
     */
    protected $hypotheticalFiles = [];

    /**
     * @var array
     */
    protected $aliases = [];

    /**
     * @var array
     */
    protected $additionalDependencies = [];

    /**
     * @var BundlerHelper
     */
    protected $bundleHelper;

    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);
        $this->bundleHelper = new BundlerHelper();
    }

    /**
     * @param ControllerContext $controllerContext
     * @return void
     */
    public function setControllerContext(ControllerContext $controllerContext)
    {
        parent::setControllerContext($controllerContext);

        /** @var ActionRequest $request */
        $request = $this->controllerContext->getRequest();
        $this->internalData['controllerContext'] = [
            'packageKey' => $request->getControllerPackageKey(),
            'subpackageKey' => $request->getControllerSubpackageKey(),
            'controllerName' => $request->getControllerName(),
            'actionName' => $request->getControllerActionName()
        ];
    }

    /**
     * @param string $path
     * @param string $code
     */
    public function addHypotheticalFile(string $path, string $code)
    {
        $this->hypotheticalFiles[$path] = $code;
    }

    /**
     * @param string $name
     * @param string $path
     */
    public function addAlias(string $name, string $path)
    {
        $this->aliases[$name] = $path;
    }

    /**
     * Adding an additional file dependencies to the generated bundle
     * very useful in development in combination with hypothetical files
     *
     * @param string $path
     */
    public function addAdditionalDependency(string $path)
    {
        $this->additionalDependencies[] = $path;
    }

    /**
     * @return string
     */
    public function getScriptName(): string
    {
        return basename($this->getOption('reactClientFilePattern'));
    }

    /**
     * @return BundlerHelper
     */
    public function client(): BundlerHelper
    {
        return $this->bundleHelper;
    }

    /**
     * @param string $baseDirectory
     */
    public function setBaseDirectory(string $baseDirectory): void
    {
        $filePathResolver = new FilePathResolver();
        $this->bundleHelper->setBaseDirectory($filePathResolver->resolveFilePath($baseDirectory));
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return $this|ViewInterface
     */
    public function assign($key, $value)
    {
        if (TypeHandling::isSimpleType(gettype($value)) || ($value instanceof \Serializable)) {
            parent::assign($key, $value);
        } else {
            throw new \InvalidArgumentException(sprintf('Context variable "%s" is not serializable', $key));
        }

        return $this;
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function render()
    {
        $filePathResolver = new FilePathResolver();

        /** @var ActionRequest $request */
        $request = $this->controllerContext->getRequest();

        /** @var Package $package */
        $package = $this->packageManager->getPackage($request->getControllerPackageKey());

        $rawServerScript = str_replace('@package', $package->getPackageKey(), $this->getOption('reactServerFilePattern'));
        $serverScript = $filePathResolver->resolveFilePath($rawServerScript);

        $rawClientScript = str_replace('@package', $package->getPackageKey(), $this->getOption('reactClientFilePattern'));
        $clientScript = $filePathResolver->resolveFilePath($rawClientScript);

        $identifier = $this->getOption('identifier') ?: md5($serverScript);
        $this->internalData['identifier'] = $identifier;
        $this->internalData['clientChunkName'] = $this->getScriptName();

        $renderingResult = null;
        $unit = new Unit($this->controllerContext);
        $unit->work(function (App $app) use ($identifier, $serverScript, $clientScript, &$renderingResult) {

            $transpiler = new Transpiler($app);
            $transpiler->transpile($identifier, $serverScript, $clientScript, $this->hypotheticalFiles, $this->aliases, $this->additionalDependencies)->done(function (Bundle $serverBundle) use ($identifier, $serverScript, $app, &$renderingResult) {

                $this->fileManager->persistBundleMeta($identifier, $this->bundleHelper);

                $renderer = new Renderer($app, $this->controllerContext);

                $renderDeferred = new Deferred();
                $renderPromise = $renderDeferred->promise();

                $renderPromise->done(
                    // shallow rendering yields some results
                    function ($content) use (&$renderingResult, $app) {
                        $renderingResult = $content;
                        $app->end();
                    },
                    // shallow rendering did not work or was not possible, falling back to default rendering method
                    function () use ($identifier, $renderer, $serverScript, $serverBundle, $app, &$renderingResult) {
                        $renderer->render($identifier, $serverScript, $serverBundle, $this->bundleHelper->getBaseDirectory(), $this->variables, $this->internalData)->done(function ($content) use ($app, &$renderingResult) {
                            $renderingResult = $content;
                            $app->end();
                        });
                    }
                );

                if ($app->isProxy()) {
                    $renderer->shallowRender($identifier, $this->variables, $this->internalData)->done(function ($result) use ($renderDeferred) {
                        if ($result !== null) {
                            $renderDeferred->resolve($result);
                        } else {
                            $renderDeferred->reject();
                        }
                    });
                } else {
                    $renderDeferred->reject();
                }
            });
        });

        if ($renderingResult === null) {
            throw new Exception('Rendering didn\'t yield any result');
        }

        return $renderingResult;
    }
}
