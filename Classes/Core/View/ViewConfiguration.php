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
     * @var array
     */
    protected $externals = [];

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
     * @param null|string $serverFile
     * @return ViewConfiguration
     */
    public function setServerFile(?string $serverFile): ViewConfiguration
    {
        $this->serverFile = $serverFile;
        return $this;
    }

    /**
     * @param null|string $clientFile
     * @return ViewConfiguration
     */
    public function setClientFile(?string $clientFile): ViewConfiguration
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
     * @return ViewConfiguration
     */
    public function setHelperInfos(array $helperInfos): ViewConfiguration
    {
        $this->helperInfos = $helperInfos;
        return $this;
    }

    /**
     * @param string $baseDirectory
     * @return ViewConfiguration
     */
    public function setBaseDirectory(string $baseDirectory): ViewConfiguration
    {
        $this->baseDirectory = $baseDirectory;
        return $this;
    }

    /**
     * @param string $publicPath
     * @return ViewConfiguration
     */
    public function setPublicPath(string $publicPath): ViewConfiguration
    {
        $this->publicPath = $publicPath;
        return $this;
    }


    public function addExternal(string $name, string $path): ViewConfiguration
    {
        $this->externals[$name] = $path;
        return $this;
    }

    /**
     * @return array
     */
    public function toTranspilerConfiguration(): array
    {
        return [
            'identifier' => $this->identifier,
            'file' => $this->serverFile,
            'baseDirectory' => $this->baseDirectory,
            'helpers' => $this->helperInfos,
            'hypotheticalFiles' => $this->hypotheticalFiles,
            'aliases' => $this->aliases,
            'publicPath' => $this->publicPath
        ];
    }

    /**
     * @return array
     */
    public function toBundlerConfiguration(): array
    {
        return [
            'identifier' => $this->identifier,
            'file' => $this->clientFile,
            'baseDirectory' => $this->baseDirectory,
            'helpers' => $this->helperInfos,
            'hypotheticalFiles' => $this->hypotheticalFiles,
            'aliases' => $this->aliases,
            'publicPath' => $this->publicPath,
            'externals' => $this->externals
        ];
    }

    /**
     * @return array
     */
    public function toRendererConfiguration(): array
    {
        return [
            'identifier' => $this->getIdentifier(),
            'internalData' => $this->getInternalData(),
        ];
    }
}
