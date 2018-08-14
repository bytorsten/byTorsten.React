<?php
namespace byTorsten\React\Core;

class Module
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $code;

    /**
     * @var string
     */
    protected $map;

    /**
     * @var bool
     */
    protected $initial;

    /**
     * @var int
     */
    protected $order;

    /**
     *
     */
    protected function __construct()
    {
    }

    /**
     * @param string $name
     * @param string $code
     * @param null|string $map
     * @param bool $initial
     * @param int $order
     * @return Module
     */
    public static function create(string $name, string $code, ?string $map, bool $initial, int $order)
    {
        $module = new static();
        $module->name = $name;
        $module->code = $code;
        $module->map = $map;
        $module->initial = $initial;
        $module->order = $order;

        return $module;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @return null|string
     */
    public function getMap(): ?string
    {
        return $this->map;
    }

    /**
     * @return bool
     */
    public function isInitial(): bool
    {
        return $this->initial;
    }

    /**
     * @return int
     */
    public function getOrder(): int
    {
        return $this->order;
    }
}
