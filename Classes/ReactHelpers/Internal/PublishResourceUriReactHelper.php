<?php
namespace byTorsten\React\ReactHelpers\Internal;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\ResourceManagement\ResourceManager;
use byTorsten\React\ResourceManagement\ReactResource;
use byTorsten\React\Core\ReactHelper\AbstractReactHelper;

class PublishResourceUriReactHelper extends AbstractReactHelper
{
    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * @param string $relativeRequest
     * @param string $absoluteRequest
     * @return string
     */
    public function evaluate(string $relativeRequest, string $absoluteRequest): string
    {
        $collection = $this->resourceManager->getCollection('react');
        $target = $collection->getTarget();
        $resource = new ReactResource($relativeRequest, $absoluteRequest);
        $target->publishResource($resource, $collection);
        return $target->getPublicPersistentResourceUri($resource);
    }
}
