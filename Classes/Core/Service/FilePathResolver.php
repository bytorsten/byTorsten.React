<?php
namespace byTorsten\React\Core\Service;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Package;
use Neos\Flow\Package\PackageManagerInterface;
use Neos\Flow\ResourceManagement\Streams\ResourceStreamWrapper;
use Neos\Utility\Files;
use Neos\Utility\Unicode\Functions;

/**
 *
 */
class FilePathResolver
{

    /**
     * @flow\Inject
     * @var PackageManagerInterface
     */
    protected $packageManager;

    /**
     * @param string $path
     * @return string
     */
    public function resolveFilePath(string $path): string
    {
        return static::earlyResolveFilePath($path, $this->packageManager);
    }

    /**
     * @param string $path
     * @param PackageManagerInterface $packageManager
     * @return string
     */
    public static function earlyResolveFilePath(string $path, PackageManagerInterface $packageManager): string
    {
        $uriParts = Functions::parse_url($path);
        if (isset($uriParts['scheme']) && $uriParts['scheme'] === ResourceStreamWrapper::SCHEME) {
            /** @var Package $package */
            $package = $packageManager->getPackage($uriParts['host']);
            return Files::concatenatePaths([$package->getResourcesPath(), $uriParts['path']]);
        }

        return $path;
    }
}
