<?php
namespace byTorsten\React\Core\View;

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
use byTorsten\React\Core\Cache\FileManager;
use byTorsten\React\Core\Bundle;
use byTorsten\React\Core\IPC\App;
use byTorsten\React\Core\IPC\Unit;
use byTorsten\React\Core\Rendering\Renderer;
use byTorsten\React\Core\Service\FilePathResolver;
use byTorsten\React\Core\Transpiling\Transpiler;

class ReactView extends AbstractView
{
    const SERVER_FILE_PATTERN = 'resource://@package/Private/React/index.server.js';
    const CLIENT_FILE_PATTERN = 'resource://@package/Private/React/index.client.js';
    const MISSING = '__missing__';

    /**
     * @var array
     */
    protected $supportedOptions = [
        'serverFile' => [self::MISSING, 'JS file responsible for server side rendering', 'string'],
        'clientFile' => [self::MISSING, 'JS file responsible for client side rendering', 'string'],
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
    protected $filePaths;

    /**
     * @var ViewConfiguration
     */
    protected $configuration;

    public function __construct(array $options = [])
    {
        parent::__construct($options);
        $this->configuration = new ViewConfiguration();
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
        $this->configuration->addInternalData('controllerContext', [
            'packageKey' => $request->getControllerPackageKey(),
            'subpackageKey' => $request->getControllerSubpackageKey(),
            'controllerName' => $request->getControllerName(),
            'actionName' => $request->getControllerActionName()
        ]);
    }

    /**
     * @param string $path
     * @param string $code
     */
    public function addHypotheticalFile(string $path, string $code)
    {
        $this->configuration->addHypotheticalFile($path, $code);
    }

    /**
     * @param string $name
     * @param string $path
     */
    public function addAlias(string $name, string $path)
    {
        $this->configuration->addAlias($name, $path);
    }

    /**
     * Adding an additional file dependencies to the generated bundle
     * very useful in development in combination with hypothetical files
     *
     * @param string $path
     */
    public function addAdditionalDependency(string $path)
    {
        $this->configuration->addAdditionalDependency($path);
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
     * @return array
     */
    protected function resolveFilePaths(): array
    {
        if ($this->filePaths !== null) {
            return $this->filePaths;
        }

        $rawServerFile = $this->options['serverFile'];
        $rawClientFile = $this->options['clientFile'];

        if ($rawServerFile === self::MISSING || $rawClientFile === self::MISSING) {

            /** @var ActionRequest $request */
            $request = $this->controllerContext->getRequest();

            /** @var Package $package */
            $package = $this->packageManager->getPackage($request->getControllerPackageKey());

            if ($rawServerFile === self::MISSING) {
                $rawServerFile = str_replace('@package', $package->getPackageKey(), self::SERVER_FILE_PATTERN);
            }

            if ($rawClientFile === self::MISSING) {
                $rawClientFile = str_replace('@package', $package->getPackageKey(), self::CLIENT_FILE_PATTERN);
            }
        }

        $filePathResolver = new FilePathResolver();

        $clientFile = $filePathResolver->resolveFilePath($rawClientFile);
        $serverFile = $filePathResolver->resolveFilePath($rawServerFile);

        return $this->filePaths = [$serverFile, $clientFile];
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function render()
    {
        [ $serverFile, $clientFile ] = $this->resolveFilePaths();

        $identifier = $this->getOption('identifier') ?: md5($serverFile);

        $this->configuration
            ->setIdentifier($identifier)
            ->setServerFile($serverFile)
            ->setClientFile($clientFile)
            ->addInternalData('identifier', $identifier)
            ->addInternalData('clientFile', $clientFile !== null ? 'bundle.js' : null)
            ->addInternalData('clientFileReady', $this->fileManager->hasClientCode($identifier));

        $renderingResult = null;
        $unit = new Unit($this->controllerContext);
        $unit->work(function (App $app) use (&$renderingResult) {

            $transpiler = new Transpiler($app);
            $transpiler->transpile($this->configuration)->done(function (Bundle $bundle) use ($app, &$renderingResult) {

                $renderer = new Renderer($app);

                $renderDeferred = new Deferred();
                $renderPromise = $renderDeferred->promise();

                $renderPromise->done(
                    // shallow rendering yields some results
                    function ($content) use (&$renderingResult, $app) {
                        $renderingResult = $content;
                        $app->end();
                    },
                    // shallow rendering did not work or was not possible, falling back to default rendering method
                    function () use ($renderer, $bundle, $app, &$renderingResult) {
                        $renderer->render($this->configuration, $bundle, $this->variables)->done(function ($content) use ($app, &$renderingResult) {
                            $renderingResult = $content;
                            $app->end();
                        });
                    }
                );

                if ($app->isProxy()) {
                    $renderer->shallowRender($this->configuration, $this->variables)->done(function ($result) use ($renderDeferred) {
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
