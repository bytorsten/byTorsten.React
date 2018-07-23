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
     *
     */
    protected function __construct()
    {
    }

    /**
     * @param array $rawModules
     * @param array $resolvedPaths
     * @return Bundle
     */
    public static function create(array $rawModules, array $resolvedPaths = [])
    {
        $bundle = new Bundle();
        $bundle->resolvedPaths = $resolvedPaths;
        foreach ($rawModules as $filename => ['code' => $code, 'map' => $map]) {
            $module = Module::create($code, $map);
            $bundle->modules[$filename] = $module;
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
