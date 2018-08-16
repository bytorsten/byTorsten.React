<?php
namespace byTorsten\React\Core\Cache;

use byTorsten\React\Core\View\ViewConfiguration;
use Neos\Flow\Annotations as Flow;
use Neos\Cache\Frontend\FrontendInterface;
use Neos\Flow\Utility\Algorithms;
use Neos\Flow\Utility\Environment;
use byTorsten\React\Core\Bundle;

/**
 * @Flow\Scope("singleton")
 */
class FileManager
{
    const SERVER_BUNDLE = '[SERVER_BUNDLE]';
    const CLIENT_CODE_FLAG = '[CLIENT_CODE_FLAG]';
    const SERVER_CODE_FLAG = '[SERVER_CODE_FLAG]';
    const TAGS = '[TAGS]';
    const REVISION = '[REVISION]';
    const CONFIGURATION = '[CONFIGURATION]';
    const EXCLUSION = '[EXCLUSION]';

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
     * @param Bundle $bundle
     * @param array $dependencies
     * @param array $excluded
     * @param ViewConfiguration $configuration
     */
    public function persistServerBundle(string $identifier, Bundle $bundle, array $dependencies, array $excluded, ViewConfiguration $configuration)
    {
        $allDependencies = array_merge($dependencies, $configuration->getAdditionalDependency());

        $tags = array_map(function (string $filename) {
            $this->fileMonitor->monitorFile($filename);
            return md5($filename);
        }, $allDependencies);

        $tags[] = $identifier;

        $this->set($identifier, static::REVISION, Algorithms::generateRandomString(10), $tags);
        $this->set($identifier, static::TAGS, $tags, $tags);
        $this->set($identifier, static::SERVER_BUNDLE, $bundle, $tags);
        $this->set($identifier, static::SERVER_CODE_FLAG, true, $tags);
        $this->set($identifier,static::CONFIGURATION, $configuration, $tags);
        $this->set($identifier, static::EXCLUSION, $excluded, $tags);
    }

    /**
     * @param string $identifier
     * @param Bundle $bundle
     */
    public function persistClientBundle(string $identifier, Bundle $bundle)
    {
        $tags = $this->get($identifier, static::TAGS);
        $this->set($identifier, static::CLIENT_CODE_FLAG, true, $tags);

        foreach ($bundle->getModules() as $module) {
            $this->set($identifier, $module->getName(), $module->getCode(), $tags);

            $map = $module->getMap();
            if ($map !== null) {
                $this->set($identifier, $module->getName() . '.map', $map, $tags);
            }
        }
    }

    /**
     * @param string $identifier
     * @return ViewConfiguration|null
     */
    public function getConfiguration(string $identifier): ?ViewConfiguration
    {
        return $this->get($identifier, static::CONFIGURATION);
    }

    /**
     * @param string $identifier
     * @return array|null
     */
    public function getExclusion(string $identifier): ?array
    {
        return $this->get($identifier, static::EXCLUSION);
    }

    /**
     * @param string $identifier
     * @return null|string
     */
    public function getRevision(string $identifier): ?string
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
     * @return bool
     */
    protected function hasFlag(string $identifier, string $key): bool
    {
        $entryIdentifier = $this->sanitizeIdentifier($identifier . $key);
        return $this->cache->has($entryIdentifier);
    }
}
