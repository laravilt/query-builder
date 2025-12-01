<?php

declare(strict_types=1);

namespace Laravilt\QueryBuilder;

use Laravilt\Support\Contracts\FlutterSerializable;
use Laravilt\Support\Contracts\InertiaSerializable;

class Sort implements FlutterSerializable, InertiaSerializable
{
    protected string $name;

    protected ?string $label = null;

    protected string $column;

    protected string $defaultDirection = 'asc';

    protected bool $visible = true;

    public function __construct(string $name, ?string $column = null)
    {
        $this->name = $name;
        $this->column = $column ?? $name;
    }

    public static function make(string $name, ?string $column = null): static
    {
        return new static($name, $column); // @phpstan-ignore new.static
    }

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function column(string $column): static
    {
        $this->column = $column;

        return $this;
    }

    public function defaultDirection(string $direction): static
    {
        $this->defaultDirection = $direction;

        return $this;
    }

    public function visible(bool $condition = true): static
    {
        $this->visible = $condition;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getColumn(): string
    {
        return $this->column;
    }

    public function getLabel(): string
    {
        return $this->label ?? str($this->name)->headline()->toString();
    }

    /**
     * @return array<string, mixed>
     */
    public function toInertiaProps(): array
    {
        return [
            'name' => $this->name,
            'label' => $this->getLabel(),
            'column' => $this->column,
            'defaultDirection' => $this->defaultDirection,
            'visible' => $this->visible,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toFlutterProps(): array
    {
        return [
            'name' => $this->name,
            'label' => $this->getLabel(),
            'column' => $this->column,
            'defaultDirection' => $this->defaultDirection,
            'visible' => $this->visible,
        ];
    }
}
