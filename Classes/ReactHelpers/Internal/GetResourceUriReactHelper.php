<?php
namespace byTorsten\React\ReactHelpers\Internal;

use byTorsten\React\ResourceManagement\ReactResource;
use Neos\Flow\Annotations as Flow;
use byTorsten\React\Core\ReactHelper\AbstractReactHelper;
use Neos\Flow\ResourceManagement\ResourceManager;

class GetResourceUriReactHelper extends AbstractReactHelper
{
    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * @param string $sourcePath
     * @return string
     */
    public function evaluate(string $sourcePath): string
    {
        $collection = $this->resourceManager->getCollection('react');
        $target = $collection->getTarget();
        $resource = new ReactResource($sourcePath);
        return $target->getPublicPersistentResourceUri($resource);
    }
}
