<?php
namespace byTorsten\React\Core;

class Bundle
{

    /**
     * @var Module[]
     */
    protected $modules = [];

    /**
     * @var array
     */
    protected $resolvedPaths = [];

    /**
     * @param array $rawModules
     * @param array $resolvedPaths
     * @return Bundle
     */
    public static function create(array $rawModules, array $resolvedPaths = [])
    {
        $bundle = new Bundle();
        $bundle->resolvedPaths = $resolvedPaths;
        foreach ($rawModules as $filename => $content) {
            if (is_array($content)) {
                $bundle->modules[$filename] = Module::create($content['code'], $content['map']);
            } else if (is_string($content)) {
                $bundle->modules[$filename] = Module::create($content, null);
            }
        }

        return $bundle;
    }

    /**
     * @param string $filename
     * @return null|Module
     */
    public function getModule(string $filename): ?Module
    {
        return $this->modules[$filename] ?? null;
    }

    /**
     * @param string $filename
     */
    public function removeModule(string $filename)
    {
        unset($this->modules[$filename]);
    }

    /**
     * @return Module[]
     */
    public function getModules(): array
    {
        return $this->modules;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return array_map(function (Module $module) {
            return [
                'code' => $module->getCode(),
                'map' => $module->getMap()
            ];
        }, $this->modules);
    }

    /**
     * @return array
     */
    public function getResolvedPaths(): array
    {
        return $this->resolvedPaths;
    }
}
