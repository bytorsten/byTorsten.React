<?php
namespace byTorsten\React\Core\View;

class ViewConfiguration
{
    /**
     * @var string
     */
    protected $identifier;

    /**
     * @var string
     */
    protected $serverFile;

    /**
     * @var string
     */
    protected $clientFile;

    /**
     * @var array
     */
    protected $internalData = [];

    /**
     * @var array
     */
    protected $additionalDependency = [];

    /**
     * @var array
     */
    protected $aliases = [];

    /**
     * @var array
     */
    protected $hypotheticalFiles = [];

    /**
     * @var array
     */
    protected $helperInfos = [];

    /**
     * @var string
     */
    protected $baseDirectory;

    /**
     * @var string
     */
    protected $publicPath;

    /**
     * @param string $identifier
     * @return ViewConfiguration
     */
    public function setIdentifier(string $identifier): ViewConfiguration
    {
        $this->identifier = $identifier;
        return $this;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @param string $serverFile
     * @return ViewConfiguration
     */
    public function setServerFile(string $serverFile): ViewConfiguration
    {
        $this->serverFile = $serverFile;
        return $this;
    }

    /**
     * @param string $clientFile
     * @return ViewConfiguration
     */
    public function setClientFile(string $clientFile): ViewConfiguration
    {
        $this->clientFile = $clientFile;
        return $this;
    }

    /**
     * @param string $key
     * @param $value
     * @return ViewConfiguration
     */
    public function addInternalData(string $key, $value): ViewConfiguration
    {
        $this->internalData[$key] = $value;
        return $this;
    }

    /**
     * @return array
     */
    public function getInternalData(): array
    {
        return $this->internalData;
    }

    /**
     * @param string $path
     * @return ViewConfiguration
     */
    public function addAdditionalDependency(string $path): ViewConfiguration
    {
        $this->additionalDependency[] = $path;
        return $this;
    }

    /**
     * @return array
     */
    public function getAdditionalDependency(): array
    {
        return $this->additionalDependency;
    }

    /**
     * @param string $name
     * @param string $path
     * @return $this
     */
    public function addAlias(string $name, string $path): ViewConfiguration
    {
        $this->aliases[$name] = $path;
        return $this;
    }

    /**
     * @param string $name
     * @param string $content
     * @return ViewConfiguration
     */
    public function addHypotheticalFile(string $name, string $content): ViewConfiguration
    {
        $this->hypotheticalFiles[$name] = $content;
        return $this;
    }

    /**
     * @param array $helperInfos
     */
    public function setHelperInfos(array $helperInfos): void
    {
        $this->helperInfos = $helperInfos;
    }

    /**
     * @param string $baseDirectory
     */
    public function setBaseDirectory(string $baseDirectory): void
    {
        $this->baseDirectory = $baseDirectory;
    }

    /**
     * @param string $publicPath
     */
    public function setPublicPath(string $publicPath): void
    {
        $this->publicPath = $publicPath;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'identifier' => $this->identifier,
            'serverFile' => $this->serverFile,
            'clientFile' => $this->clientFile,
            'baseDirectory' => $this->baseDirectory,
            'helpers' => $this->helperInfos,
            'hypotheticalFiles' => $this->hypotheticalFiles,
            'aliases' => $this->aliases,
            'publicPath' => $this->publicPath
        ];
    }
}
