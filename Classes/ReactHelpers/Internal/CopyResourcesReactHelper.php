<?php
namespace byTorsten\React\ReactHelpers\Internal;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\ResourceManagement\ResourceManager;
use byTorsten\React\ResourceManagement\ReactResource;
use byTorsten\React\Core\ReactHelper\AbstractReactHelper;

class CopyResourcesReactHelper extends AbstractReactHelper
{
    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * @param array $resources
     */
    public function evaluate(array $resources): void
    {
        $collection = $this->resourceManager->getCollection('react');
        $target = $collection->getTarget();

        foreach ($resources as $sourcePath) {
            $resource = new ReactResource($sourcePath);
            $target->publishResource($resource, $collection);
        }
    }
}
