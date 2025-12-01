<?php

declare(strict_types=1);

namespace Laravilt\QueryBuilder\Filters;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Laravilt\Support\Contracts\FlutterSerializable;
use Laravilt\Support\Contracts\InertiaSerializable;

abstract class Filter implements FlutterSerializable, InertiaSerializable
{
    protected string $name;

    protected ?string $label = null;

    protected ?string $column = null;

    protected ?Closure $query = null;

    protected mixed $default = null;

    protected bool $visible = true;

    protected ?string $placeholder = null;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->column = $name;
    }

    public static function make(string $name): static
    {
        return new static($name); // @phpstan-ignore new.static
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

    public function query(Closure $callback): static
    {
        $this->query = $callback;

        return $this;
    }

    public function default(mixed $value): static
    {
        $this->default = $value;

        return $this;
    }

    public function visible(bool $condition = true): static
    {
        $this->visible = $condition;

        return $this;
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label ?? str($this->name)->headline()->toString();
    }

    public function getColumn(): string
    {
        return $this->column ?? $this->name;
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     */
    public function apply(Builder $query, mixed $value): void
    {
        if ($this->query !== null) {
            ($this->query)($query, $value);
        } else {
            $this->applyDefault($query, $value);
        }
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     */
    abstract protected function applyDefault(Builder $query, mixed $value): void;

    /**
     * @return array<string, mixed>
     */
    public function toInertiaProps(): array
    {
        return [
            'type' => $this->getType(),
            'name' => $this->name,
            'label' => $this->getLabel(),
            'column' => $this->getColumn(),
            'default' => $this->default,
            'visible' => $this->visible,
            'placeholder' => $this->placeholder,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toFlutterProps(): array
    {
        return [
            'type' => $this->getType(),
            'name' => $this->name,
            'label' => $this->getLabel(),
            'column' => $this->getColumn(),
            'default' => $this->default,
            'visible' => $this->visible,
            'placeholder' => $this->placeholder,
        ];
    }

    protected function getType(): string
    {
        return class_basename(static::class);
    }
}
