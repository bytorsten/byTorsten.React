<?php
namespace byTorsten\React\Core\Cache;

use Neos\Flow\Annotations as Flow;
use Neos\Cache\Frontend\FrontendInterface;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Utility\Algorithms;
use Neos\Flow\Utility\Environment;
use byTorsten\React\Core\View\BundlerHelper;
use byTorsten\React\Core\Bundle;

/**
 * @Flow\Scope("singleton")
 */
class FileManager
{
    const LEGACY_PREFIX = '_legacy_';
    const ASSET_PREFIX = '_asset_';
    const SERVER_BUNDLE = '[SERVER_BUNDLE]';
    const CLIENT_CODE_FLAG = '[CLIENT_CODE_FLAG]';
    const LEGACY_CLIENT_CODE_FLAG = '[LEGACY_CLIENT_CODE_FLAG]';
    const TAGS = '[TAGS]';
    const CLIENT_SCRIPT_PATH = '[CLIENT_SCRIPT]';
    const SERVER_SCRIPT_PATH = '[SERVER_SCRIPT]';
    const REVISION = '[REVISION]';
    const BUNDLE_META = '[BUNDLE_META]';
    const ASSET_URIS = '[ASSET_URIS]';

    /**
     * @Flow\Inject
     * @var FrontendInterface
     */
    protected $cache;

    /**
     * @Flow\Inject
     * @var FileMonitor
     */
    protected $fileMonitor;

    /**
     * @Flow\Inject
     * @var Environment
     */
    protected $environment;

    /**
     * @param string $identifier
     * @param string $clientScriptPath
     * @param string $serverScriptPath
     * @param Bundle $bundle
     * @param array $dependencies
     */
    public function persistServerBundle(string $identifier, string $clientScriptPath, string $serverScriptPath, Bundle $bundle, array $dependencies)
    {
        $tags = array_map(function (string $filename) {
            $this->fileMonitor->monitorFile($filename);
            return md5($filename);
        }, $dependencies);

        $tags[] = $identifier;

        $this->set($identifier, static::REVISION, Algorithms::generateRandomString(10), $tags);
        $this->set($identifier, static::TAGS, $tags, $tags);
        $this->set($identifier, static::CLIENT_SCRIPT_PATH, $clientScriptPath, $tags);
        $this->set($identifier, static::SERVER_SCRIPT_PATH, $serverScriptPath, $tags);
        $this->set($identifier, static::SERVER_BUNDLE, $bundle, $tags);
    }

    /**
     * @param string $identifier
     * @param Bundle $assetsBundle
     * @param ControllerContext $controllerContext
     */
    public function persistAssets(string $identifier, Bundle $assetsBundle, ControllerContext $controllerContext)
    {
        $tags = $this->get($identifier, static::TAGS);
        $uriBuilder = $controllerContext->getUriBuilder()->reset();
        $assetUris = [];

        foreach ($assetsBundle->getModules() as $filename => $module) {
            $this->setAsset($identifier, $filename, $module->getCode(), $tags);
            $assetUris[$filename] = $uriBuilder->uriFor('asset', ['identifier' => $identifier, 'chunkname' => $filename], 'Chunk', 'byTorsten.React');

            $map = $module->getMap();
            if ($map !== null) {
                $this->setAsset($identifier, $filename . '.map', $map, $tags);
            }
        }

        $this->set($identifier, static::ASSET_URIS, $assetUris, $tags);
    }

    /**
     * @param string $identifier
     * @param Bundle $bundle
     */
    public function persistClientBundle(string $identifier, Bundle $bundle)
    {
        $tags = $this->get($identifier, static::TAGS);
        $this->set($identifier, static::CLIENT_CODE_FLAG, true, $tags);

        foreach ($bundle->getModules() as $filename => $module) {
            $this->set($identifier, $filename, $module->getCode(), $tags);

            $map = $module->getMap();
            if ($map !== null) {
                $this->set($identifier, $filename . '.map', $map, $tags);
            }
        }
    }

    /**
     * @param string $identifier
     * @param BundlerHelper $bundlerHelper
     */
    public function persistBundleMeta(string $identifier, BundlerHelper $bundlerHelper)
    {
        $tags = $this->get($identifier, static::TAGS);
        $this->set($identifier, static::BUNDLE_META, $bundlerHelper, $tags);
    }

    /**
     * @param string $identifier
     * @param Bundle $bundle
     */
    public function persistLegacyClientBundle(string $identifier, Bundle $bundle)
    {
        $tags = $this->get($identifier, static::TAGS);
        $this->set($identifier, static::LEGACY_CLIENT_CODE_FLAG, true, $tags);

        foreach ($bundle->getModules() as $filename => $module) {
            $this->setLegacy($identifier, $filename, $module->getCode(), $tags);

            $map = $module->getMap();
            if ($map !== null) {
                $this->setLegacy($identifier, $filename . '.map', $module->getMap(), $tags);
            }
        }
    }

    /**
     * @param string $identifier
     * @return BundlerHelper
     */
    public function getBundleMeta(string $identifier): BundlerHelper
    {
        return $this->get($identifier, static::BUNDLE_META) ?: new BundlerHelper();
    }

    /**
     * @param string $identifier
     * @return null|string
     */
    public function getClientScriptPath(string $identifier): string
    {
        return $this->get($identifier, static::CLIENT_SCRIPT_PATH);
    }

    /**
     * @param string $identifier
     * @return string
     */
    public function getServerScriptPath(string $identifier): string
    {
        return $this->get($identifier, static::SERVER_SCRIPT_PATH);
    }

    /**
     * @param string $identifier
     * @return string
     */
    public function getRevision(string $identifier): string
    {
        return $this->get($identifier, static::REVISION);
    }

    /**
     * @param string $identifier
     * @return Bundle
     */
    public function getServerBundle(string $identifier): Bundle
    {
        return $this->get($identifier, static::SERVER_BUNDLE);
    }

    /**
     * @param string $identifier
     * @return bool
     */
    public function hasServerCode(string $identifier): bool
    {
        return $this->hasFlag($identifier, static::SERVER_BUNDLE);
    }

    /**
     * @param string $identifier
     * @return bool
     */
    public function hasClientCode(string $identifier): bool
    {
        return $this->hasFlag($identifier, static::CLIENT_CODE_FLAG);
    }

    /**
     * @param string $identifier
     * @return bool
     */
    public function hasLegacyClientCode(string $identifier): bool
    {
        return $this->hasFlag($identifier, static::LEGACY_CLIENT_CODE_FLAG);
    }

    /**
     * @param string $identifier
     * @return string
     */
    protected function sanitizeIdentifier(string $identifier): string
    {
        return md5($identifier);
    }

    /**
     * @param string $identifier
     * @param string $key
     * @return mixed|null
     */
    public function get(string $identifier, string $key)
    {
        $entryIdentifier = $this->sanitizeIdentifier($identifier . $key);

        if ($this->cache->has($entryIdentifier)) {
            return $this->cache->get($entryIdentifier);
        }

        return null;
    }

    /**
     * @param string $identifier
     * @param string $key
     * @return null|string
     */
    public function getLegacy(string $identifier, string $key): ?string
    {
        return $this->get($identifier, static::LEGACY_PREFIX . $key);
    }

    /**
     * @param string $identifier
     * @param string $key
     * @return null|string
     */
    public function getAsset(string $identifier, string $key): ?string
    {
        return $this->get($identifier, static::ASSET_PREFIX . $key);
    }

    /**
     * @param string $identifier
     * @return array
     */
    public function getAssetUris(string $identifier): array
    {
        $entryIdentifier = $this->sanitizeIdentifier($identifier.  static::ASSET_URIS);
        if ($this->cache->has($entryIdentifier)) {
            return $this->cache->get($entryIdentifier);
        }

        return [];
    }

    /**
     * @param string $identifier
     * @param string $key
     * @param mixed $content
     * @param array $tags
     */
    protected function set(string $identifier, string $key, $content, array $tags)
    {
        if ($content !== null) {
            $entryIdentifier = $this->sanitizeIdentifier($identifier . $key);
            $this->cache->set($entryIdentifier, $content, $tags);
        }
    }

    /**
     * @param string $identifier
     * @param string $key
     * @param $content
     * @param array $tags
     */
    protected function setLegacy(string $identifier, string $key, $content, array $tags)
    {
        $this->set($identifier, static::LEGACY_PREFIX . $key, $content, $tags);
    }

    /**
     * @param string $identifier
     * @param string $key
     * @param $content
     * @param array $tags
     */
    protected function setAsset(string $identifier, string $key, $content, array $tags)
    {
        $this->set($identifier, static::ASSET_PREFIX . $key, $content, $tags);
    }

    /**
     * @param string $identifier
     * @param string $key
     * @return bool
     */
    protected function hasFlag(string $identifier, string $key): bool
    {
        $entryIdentifier = $this->sanitizeIdentifier($identifier . $key);
        return $this->cache->has($entryIdentifier);
    }
}
