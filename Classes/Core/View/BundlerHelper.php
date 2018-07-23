<?php
namespace byTorsten\React\Core\View;

class BundlerHelper
{
    /**
     * @var array
     */
    protected $aliases = [];


    /**
     * @var array
     */
    protected $hypotheticalFiles = [];

    /**
     * @var bool
     */
    protected $shouldShowBundleNotification = true;

    /**
     * @var string
     */
    protected $baseDirectory;

    /**
     * @param string $baseDirectory
     */
    public function setBaseDirectory(string $baseDirectory): void
    {
        $this->baseDirectory = $baseDirectory;
    }

    /**
     * @param string $path
     * @param string $code
     */
    public function addHypotheticalFile(string $path, string $code): void
    {
        $this->hypotheticalFiles[$path] = $code;
    }

    /**
     * @param string $name
     * @param string $path
     */
    public function addAlias(string $name, string $path): void
    {
        $this->aliases[$name] = $path;
    }

    /**
     * @param bool $showBundleNotification
     */
    public function showBundleNotification(bool $showBundleNotification): void
    {
        $this->shouldShowBundleNotification = $showBundleNotification;
    }

    /**
     * @return array
     */
    public function getAliases(): array
    {
        return $this->aliases;
    }

    /**
     * @return array
     */
    public function getHypotheticalFiles(): array
    {
        return $this->hypotheticalFiles;
    }

    /**
     * @return bool
     */
    public function shouldShowBundleNotification(): bool
    {
        return $this->shouldShowBundleNotification;
    }

    /**
     * @return string
     */
    public function getBaseDirectory(): ?string
    {
        return $this->baseDirectory;
    }
}
