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
     * @return Bundle
     */
    public static function create(array $rawModules)
    {
        $bundle = new Bundle();
        foreach ($rawModules as $filename => ['code' => $code, 'map' => $map, 'initial' => $initial, 'order' => $order]) {
            $bundle->modules[] = Module::create($filename, $code, $map, $initial, $order);
        }

        usort($bundle->modules, function (Module $moduleA, Module $moduleB) {
            if ($moduleA->getOrder() === $moduleB->getOrder()) {
                return 0;
            }

            return ($moduleA->getOrder() < $moduleB->getOrder()) ? -1 : 1;
        });

        return $bundle;
    }

    /**
     * @param string $name
     * @return Module|null
     */
    public function getModule(string $name): ?Module
    {
        foreach ($this->modules as $module) {
            if ($module->getName() === $name) {
                return $module;
            }
        }

        return null;
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
                'name' => $module->getName(),
                'code' => $module->getCode(),
                'map' => $module->getMap(),
                'initial' => $module->isInitial()
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
