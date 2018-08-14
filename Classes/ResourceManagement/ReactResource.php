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
     * @param string $sourcePath
     */
    public function __construct(string $sourcePath)
    {
        $this->sourcePath = $sourcePath;

        $pathInfo = UnicodeFunctions::pathinfo($sourcePath);
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
