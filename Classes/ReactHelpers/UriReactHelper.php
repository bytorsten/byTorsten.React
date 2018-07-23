<?php
namespace byTorsten\React\ReactHelpers;

use byTorsten\React\Core\ReactHelper\AbstractReactHelper;
use byTorsten\React\Core\ReactHelper\ReactHelperException;

class UriReactHelper extends AbstractReactHelper
{
    /**
     * @param string $action
     * @param array $arguments
     * @param string|null $controller
     * @param string|null $package
     * @param string|null $subpackage
     * @param string|null $format
     * @param array|null $additionalParams
     * @param array|null $argumentsToBeExcludedFromQueryString
     * @param bool $absolute
     * @param string|null $section
     * @param bool $addQueryString
     * @return string
     * @throws ReactHelperException
     */
    public function evaluate(
        string $action,
        array $arguments = [],
        string $controller = null,
        string $package = null,
        string $subpackage = null,
        string $format = null,
        array $additionalParams = null,
        array $argumentsToBeExcludedFromQueryString = null,
        bool $absolute = false,
        string $section = null,
        bool $addQueryString = false
    ): string {
        $uriBuilder = $this->controllerContext->getUriBuilder();

        if ($format !== null) {
            $uriBuilder->setFormat($format);
        }

        if ($additionalParams !== null) {
            $uriBuilder->setArguments($additionalParams);
        }

        if ($argumentsToBeExcludedFromQueryString !== null) {
            $uriBuilder->setArgumentsToBeExcludedFromQueryString($argumentsToBeExcludedFromQueryString);
        }

        if ($absolute === true) {
            $uriBuilder->setCreateAbsoluteUri(true);
        }

        if ($section !== null) {
            $uriBuilder->setSection($section);
        }

        if ($addQueryString === true) {
            $uriBuilder->setAddQueryString(true);
        }

        try {
            return $uriBuilder->uriFor(
                $action,
                $arguments,
                $controller,
                $package,
                $subpackage
            );
        } catch (\Exception $exception) {
            throw new ReactHelperException('Could not resolve a route', 1529164250, $exception);
        }
    }
}
