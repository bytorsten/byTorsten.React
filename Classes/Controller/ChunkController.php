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
     * @throws StopActionException
     */
    protected function compareETag(string $identifier)
    {
        $revision = $this->fileManager->getRevision($identifier);
        if ($revision === null) {
            return;
        }

        $serverETag = 'W/' . $revision;
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
     * @return string
     * @throws StopActionException
     */
    public function indexAction(string $identifier, string $chunkname): string
    {
        $this->compareETag($identifier);
        $this->response->setHeader('Content-Type', 'text/javascript');

        $content = $this->fileManager->get($identifier, $chunkname);

        if ($content === null) {
            if ($this->fileManager->hasServerCode($identifier) === true && $this->fileManager->hasClientCode($identifier) === false) {
                $bundler = new Bundler($this->controllerContext);
                $clientBundle = $bundler->bundle($identifier);

                return $clientBundle->getModule($chunkname)->getCode();
            }
        }

        if ($content === null) {
            $this->response->setStatus(404);
            throw new StopActionException();
        }

        return $content;
    }
}
