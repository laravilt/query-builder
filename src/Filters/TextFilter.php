<?php

declare(strict_types=1);

namespace Laravilt\QueryBuilder\Filters;

use Illuminate\Database\Eloquent\Builder;

class TextFilter extends Filter
{
    protected string $operator = 'like';

    protected bool $caseSensitive = false;

    public function operator(string $operator): static
    {
        $this->operator = $operator;

        return $this;
    }

    public function exact(): static
    {
        $this->operator = '=';

        return $this;
    }

    public function contains(): static
    {
        $this->operator = 'like';

        return $this;
    }

    public function startsWith(): static
    {
        $this->operator = 'starts_with';

        return $this;
    }

    public function endsWith(): static
    {
        $this->operator = 'ends_with';

        return $this;
    }

    public function caseSensitive(bool $condition = true): static
    {
        $this->caseSensitive = $condition;

        return $this;
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     */
    protected function applyDefault(Builder $query, mixed $value): void
    {
        $column = $this->getColumn();

        match ($this->operator) {
            'like' => $query->where($column, 'like', "%{$value}%"),
            'starts_with' => $query->where($column, 'like', "{$value}%"),
            'ends_with' => $query->where($column, 'like', "%{$value}"),
            default => $query->where($column, $this->operator, $value),
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function toInertiaProps(): array
    {
        return [
            ...parent::toInertiaProps(),
            'operator' => $this->operator,
            'caseSensitive' => $this->caseSensitive,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toFlutterProps(): array
    {
        return [
            ...parent::toFlutterProps(),
            'operator' => $this->operator,
            'caseSensitive' => $this->caseSensitive,
        ];
    }
}
