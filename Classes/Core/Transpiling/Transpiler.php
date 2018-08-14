<?php
namespace byTorsten\React\Core\Transpiling;

use byTorsten\React\Core\View\ViewConfiguration;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Utility\Environment;
use React\Promise\ExtendedPromiseInterface;
use React\Promise\FulfilledPromise;
use byTorsten\React\Core\Cache\FileManager;
use byTorsten\React\Core\ReactHelper\ReactHelperManager;
use byTorsten\React\Core\IPC\App;
use byTorsten\React\Core\Bundle;

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
     * @param string $identifier
     * @return string
     */
    protected function getPublicPath(string $identifier): string
    {
        $uriBuilder = $this->app->getControllerContext()->getUriBuilder();
        $dummyUri = $uriBuilder->uriFor('index', ['identifier' => $identifier, 'chunkname' => 'dummy.tmp'], 'Chunk', 'byTorsten.React');
        $pathInfo = pathinfo($dummyUri);
        return rtrim($pathInfo['dirname'], '/') . '/';
    }

    /**
     * @param ViewConfiguration $configuration
     * @return ExtendedPromiseInterface
     */
    public function transpile(ViewConfiguration $configuration): ExtendedPromiseInterface
    {
        $identifier = $configuration->getIdentifier();

        if ($this->fileManager->hasServerCode($identifier)) {
            return new FulfilledPromise($this->fileManager->getServerBundle($identifier));
        }

        $configuration->setHelperInfos($this->reactHelperManager->generateHelperInfos());
        $configuration->setPublicPath($this->getPublicPath($identifier));

        $transpileConfiguration = $configuration->toArray();
        $transpileConfiguration['extractDependencies'] = $this->environment->getContext()->isDevelopment();
        $transpileConfiguration['prepareClientBundle'] = $this->app->willSurvive();

        return $this->app->call('transpile', $transpileConfiguration)->then(function (array $transpileResult) use ($identifier, $configuration) {
            ['bundle' => $rawBundle, 'dependencies' => $dependencies] = $transpileResult;
            $bundle = Bundle::create($rawBundle);
            $this->fileManager->persistServerBundle($identifier, $bundle, $dependencies, $configuration);

            return $bundle;
        });
    }
}
