<?php

declare(strict_types=1);

namespace Laravilt\QueryBuilder\Filters;

use Illuminate\Database\Eloquent\Builder;

class SelectFilter extends Filter
{
    /** @var array<string, string> */
    protected array $options = [];

    protected bool $multiple = false;

    protected bool $searchable = false;

    /**
     * @param  array<string, string>  $options
     */
    public function options(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    public function multiple(bool $condition = true): static
    {
        $this->multiple = $condition;

        return $this;
    }

    public function searchable(bool $condition = true): static
    {
        $this->searchable = $condition;

        return $this;
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     */
    protected function applyDefault(Builder $query, mixed $value): void
    {
        $column = $this->getColumn();

        if ($this->multiple && is_array($value)) {
            $query->whereIn($column, $value);
        } else {
            $query->where($column, $value);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toInertiaProps(): array
    {
        return [
            ...parent::toInertiaProps(),
            'options' => $this->options,
            'multiple' => $this->multiple,
            'searchable' => $this->searchable,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toFlutterProps(): array
    {
        return [
            ...parent::toFlutterProps(),
            'options' => $this->options,
            'multiple' => $this->multiple,
            'searchable' => $this->searchable,
        ];
    }
}
