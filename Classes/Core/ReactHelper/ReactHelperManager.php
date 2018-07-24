<?php
namespace byTorsten\React\Core\ReactHelper;

use Neos\Flow\Annotations as Flow;
use Neos\Eel\Package;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Package\PackageManagerInterface;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Utility\Arrays;
use Neos\Utility\Files;
use byTorsten\React\Core\Service\FilePathResolver;

/**
 * @Flow\Scope("singleton")
 */
class ReactHelperManager
{
    const EXTENSION_KEY = '__extension';

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @param ControllerContext $controllerContext
     * @param string $helper
     * @param array $data
     * @return mixed
     * @throws ReactHelperException
     */
    public function invokeHelper(ControllerContext $controllerContext, string $helper, array $data)
    {
        $helperClassNames = static::getAllReactHelperImplementations($this->objectManager);
        $helperInfo = Arrays::getValueByPath($helperClassNames, $helper);

        if ($helperInfo === null) {
            throw new ReactHelperException(sprintf('Cannot find react helper with name "%s".', $helper));
        }

        $helperClassName = $helperInfo['className'];

        /** @var ReactHelperInterface $helper */
        $helper = new $helperClassName();
        $helper->setControllerContext($controllerContext);

        $parameters = $helperInfo['parameters'];

        $arguments = [];
        foreach ($parameters as $parameterName => $parameterConfiguration) {
            if (!isset($data[$parameterName]) &&  $parameterConfiguration['optional'] === false) {
                throw new ReactHelperException(sprintf('Missing prop "%s" on helper "%s"', $parameterName, $helperClassName));
            }

            $arguments[] = $data[$parameterName] ?? $parameterConfiguration['defaultValue'];
        }

        return call_user_func_array([$helper, 'evaluate'], $arguments);
    }

    /**
     * @return array
     */
    public function generateHelperInfos(): array
    {
        $reactHelperImplementations = static::getAllReactHelperImplementations($this->objectManager);
        return array_reduce(array_keys($reactHelperImplementations), function (array $groups, string $groupName) use ($reactHelperImplementations) {
            $group = $reactHelperImplementations[$groupName];
            $groups[$groupName] = array_reduce(array_keys($group), function (array $helpers, string $helperName) use ($group) {
                $helpers[$helperName] = $helperName === static::EXTENSION_KEY ? $group[static::EXTENSION_KEY]['filePath'] : true;
                return $helpers;
            }, []);
            return $groups;
        }, []);
    }

    /**
     * @param string $packageKey
     * @return string
     */
    protected static function buildJsPackageName(string $packageKey): string
    {
        $parts = explode('.', $packageKey);
        return '@' . strtolower(array_shift($parts) . '/' . implode('-', $parts));
    }

    /**
     * @Flow\CompileStatic
     * @param ObjectManagerInterface $objectManager
     * @return array
     * @throws ReactHelperException
     */
    public static function getAllReactHelperImplementations(ObjectManagerInterface $objectManager): array
    {
        $reflectionService = $objectManager->get(ReflectionService::class);
        $implementations = $reflectionService->getAllImplementationClassNamesForInterface(ReactHelperInterface::class);

        $configurationManager = $objectManager->get(ConfigurationManager::class);
        $packageManager = $objectManager->get(PackageManagerInterface::class);
        $autoInclude = $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'byTorsten.React.autoInclude');

        $helpers = [];

        foreach ($implementations as $className) {
            $packageKey = $objectManager->getPackageKeyByObjectName($className);

            if (!isset($autoInclude[$packageKey]) || $autoInclude[$packageKey] === false) {
                continue;
            }

            $namespace = str_replace('.', '\\', $packageKey) . '\ReactHelpers';

            if (strpos($className, $namespace) === false) {
                throw new ReactHelperException(sprintf('"%s" needs to be within the "%s" namespace.', $className, $namespace));
            }

            if (strrpos($className, 'ReactHelper') !== strlen($className) - strlen('ReactHelper')) {
                throw new ReactHelperException(sprintf('"%s" needs to end with "ReactHelper"', $className));
            }

            if (!method_exists($className, 'evaluate')) {
                throw new ReactHelperException(sprintf('"%s" does not implement evaluate', $className));
            }

            $packageName = static::buildJsPackageName($packageKey);

            $helperName = substr($className, strlen($namespace) + 1);
            $helperName = substr($helperName, 0, strlen($helperName) - strlen('ReactHelper'));

            if (strpos($helperName, '\\') !== false) {
                $paths = explode('\\', $helperName);
                $helperName = array_pop($paths);
                $packageName .= '/' . strtolower(implode('/', $paths));
            }

            $helpers[$packageName][$helperName] = [
                'className' => $className,
                'parameters' => $reflectionService->getMethodParameters($className, 'evaluate')
            ];
        }

        foreach ($autoInclude as $packageKey => $path) {
            if ($path === false) {
                continue;
            }

            /** @var Package $package */
            $package = $packageManager->getPackage($packageKey);
            $shouldThrow = false;
            if (is_string($path)) {
                $filePath = FilePathResolver::earlyResolveFilePath($path, $packageManager);
                $shouldThrow = true;
            } else {
                $filePath = Files::concatenatePaths([$package->getResourcesPath(), 'Private', 'React', 'index.js']);
            }

            if (@file_exists($filePath)) {
                $packageName = static::buildJsPackageName($packageKey);
                $helpers[$packageName][static::EXTENSION_KEY] = [
                    'filePath' => $filePath
                ];
            } else if ($shouldThrow) {
                throw new ReactHelperException(sprintf('Specified file "%s" does not exists for package "%s"', $path, $packageKey));
            }
        }

        return $helpers;
    }
}
