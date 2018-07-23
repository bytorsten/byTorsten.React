<?php
namespace byTorsten\React\Core;

class Module
{
    /**
     * @var string
     */
    protected $code;

    /**
     * @var string
     */
    protected $map;

    /**
     *
     */
    protected function __construct()
    {
    }

    /**
     * @param string $code
     * @param null|string $map
     * @return static
     */
    public static function create(string $code, ?string $map)
    {
        $module = new static();
        $module->code = $code;
        $module->map = $map;

        return $module;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @param string $code
     */
    public function appendCode(string $code)
    {
        $this->code .= $code;
    }

    /**
     * @return null|string
     */
    public function getMap(): ?string
    {
        return $this->map;
    }
}
