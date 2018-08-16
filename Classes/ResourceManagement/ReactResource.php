<?php
namespace byTorsten\React\ResourceManagement;

use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Utility\Unicode\Functions as UnicodeFunctions;

class ReactResource extends PersistentResource
{
    /**
     * @var string
     */
    protected $sourcePath;

    /**
     * @param string $relativeRequest
     * @param string $absoluteRequest
     */
    public function __construct(string $relativeRequest, string $absoluteRequest)
    {
        $this->sourcePath = $absoluteRequest;

        $pathInfo = UnicodeFunctions::pathinfo($relativeRequest);
        $this->filename = $pathInfo['basename'];
        $this->relativePublicationPath = md5($pathInfo['dirname']) . '/';
        $this->collectionName = 'react';
    }

    /**
     * @return bool|resource
     */
    public function getStream()
    {
        return fopen($this->sourcePath, 'r');
    }
}
