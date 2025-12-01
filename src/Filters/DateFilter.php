<?php

declare(strict_types=1);

namespace Laravilt\QueryBuilder\Filters;

use Illuminate\Database\Eloquent\Builder;

class DateFilter extends Filter
{
    protected string $operator = '=';

    protected ?string $minDate = null;

    protected ?string $maxDate = null;

    protected bool $withTime = false;

    public function operator(string $operator): static
    {
        $this->operator = $operator;

        return $this;
    }

    public function before(): static
    {
        $this->operator = '<';

        return $this;
    }

    public function after(): static
    {
        $this->operator = '>';

        return $this;
    }

    public function between(): static
    {
        $this->operator = 'between';

        return $this;
    }

    public function minDate(string $date): static
    {
        $this->minDate = $date;

        return $this;
    }

    public function maxDate(string $date): static
    {
        $this->maxDate = $date;

        return $this;
    }

    public function withTime(bool $condition = true): static
    {
        $this->withTime = $condition;

        return $this;
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     */
    protected function applyDefault(Builder $query, mixed $value): void
    {
        $column = $this->getColumn();

        if ($this->operator === 'between' && is_array($value) && count($value) === 2) {
            $query->whereBetween($column, $value);
        } else {
            $query->where($column, $this->operator, $value);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toInertiaProps(): array
    {
        return [
            ...parent::toInertiaProps(),
            'operator' => $this->operator,
            'minDate' => $this->minDate,
            'maxDate' => $this->maxDate,
            'withTime' => $this->withTime,
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
            'minDate' => $this->minDate,
            'maxDate' => $this->maxDate,
            'withTime' => $this->withTime,
        ];
    }
}
