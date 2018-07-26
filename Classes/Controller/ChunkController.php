<?php
namespace byTorsten\React\Controller;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Mvc\Exception\StopActionException;
use byTorsten\React\Core\Bundling\Bundler;
use byTorsten\React\Core\Cache\FileManager;

class ChunkController extends ActionController
{
    /**
     * @Flow\Inject
     * @var FileManager
     */
    protected $fileManager;

    /**
     * @Flow\InjectConfiguration("bundleNotification")
     * @var string|bool
     */
    protected $bundleNotificationPath;

    /**
     * @param string $identifier
     * @param string $chunkname
     * @param bool $legacy
     * @return string
     */
    protected function renderBundleNotification(string $identifier, string $chunkname, bool $legacy = false): string
    {
        $scriptUrl = $this->uriBuilder->uriFor($legacy ? 'legacy' : 'index', [
            'identifier' => $identifier,
            'chunkname' => $chunkname,
            'build' => true
        ]);

        if ($this->bundleNotificationPath === false) {
            $this->redirectToUri($scriptUrl);
        }

        $this->response->getHeaders()->remove('ETag');
        $this->response->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate');
        $this->response->setHeader('Pragma', 'no-cache');
        $this->response->setHeader('Expires', '0');

        $template = file_get_contents($this->bundleNotificationPath);

        return str_replace(
            ['%SCRIPT_URL%', '%LEGACY%', '%IDENTIFIER%'],
            [$scriptUrl, $legacy ? 'true' : 'false', $identifier],
            $template
        );
    }

    /**
     * @param string $identifier
     * @throws StopActionException
     */
    protected function compareETag(string $identifier)
    {
        $serverETag = 'W/' . $this->fileManager->getRevision($identifier);
        $httpRequest = $this->request->getHttpRequest();
        if ($httpRequest->hasHeader('If-None-Match')) {
            $clientETag = $httpRequest->getHeader('If-None-Match');
            if ($serverETag === $clientETag) {
                $this->response->setStatus(304);
                throw new StopActionException();
            }
        }

        $this->response->setHeader('ETag', $serverETag);
    }

    /**
     * @param string $identifier
     * @param string $chunkname
     * @param bool $build
     * @return string
     * @throws StopActionException
     */
    public function indexAction(string $identifier, string $chunkname, bool $build = false): string
    {
        $this->compareETag($identifier);
        $this->response->setHeader('Content-Type', 'text/javascript');

        $content = $this->fileManager->get($identifier, $chunkname);

        if ($content === null) {
            if ($this->fileManager->hasServerCode($identifier) === true && $this->fileManager->hasClientCode($identifier) === false) {
                $shouldShowBundleNotification = $this->fileManager->getBundleMeta($identifier)->shouldShowBundleNotification();

                if ($build === true || $shouldShowBundleNotification === false) {
                    $bundler = new Bundler($this->controllerContext);
                    $clientBundle = $bundler->bundle($identifier);

                    return $clientBundle->getModule($chunkname)->getCode();
                }

                return $this->renderBundleNotification($identifier, $chunkname);
            }
        }

        if ($content === null) {
            $this->response->setStatus(404);
            throw new StopActionException();
        }

        return $content;
    }

    /**
     * @param string $identifier
     * @param string $chunkname
     * @param bool $build
     * @return string
     * @throws StopActionException
     */
    public function legacyAction(string $identifier, string $chunkname, bool $build = false): string
    {
        $this->compareETag($identifier);
        $this->response->setHeader('Content-Type', 'text/javascript');

        $content = $this->fileManager->getLegacy($identifier, $chunkname);

        if ($content === null) {
            if ($this->fileManager->hasServerCode($identifier) === true && $this->fileManager->hasLegacyClientCode($identifier) === false) {
                if ($build === true) {
                    $bundler = new Bundler($this->controllerContext);
                    $legacyClientBundle = $bundler->bundle($identifier, true);
                    return $legacyClientBundle->getModule($chunkname)->getCode();
                }

                return $this->renderBundleNotification($identifier, $chunkname, true);
            }
        }

        if ($content === null) {
            $this->response->setStatus(404);
            throw new StopActionException();
        }

        return $content;
    }
}
