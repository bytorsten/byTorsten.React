<?php
namespace byTorsten\React\ReactHelpers;

use Neos\Flow\Annotations as Flow;
use byTorsten\React\Core\ReactHelper\AbstractReactHelper;
use byTorsten\React\Core\ReactHelper\ReactHelperException;
use Neos\Flow\ResourceManagement\ResourceManager;

class ResourceUriReactHelper extends AbstractReactHelper
{
    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * @param string $path
     * @return string
     * @throws ReactHelperException
     */
    public function evaluate(string $path): string
    {
        if (strpos($path, 'resource://') !== 0) {
            throw new ReactHelperException(sprintf('resource path "%s" does not start with "resource://"', $path));
        }

        if (preg_match('#^resource://([^/]+)/Public/(.*)#', $path, $matches) !== 1) {
            throw new ReactHelperException(sprintf('The specified path "%s" does not point to a public resource.', $path));
        }

        $package = $matches[1];
        $path = $matches[2];

        return $this->resourceManager->getPublicPackageResourceUri($package, $path);
    }
}
