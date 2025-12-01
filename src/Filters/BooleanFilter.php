<?php

declare(strict_types=1);

namespace Laravilt\QueryBuilder\Filters;

use Illuminate\Database\Eloquent\Builder;

class BooleanFilter extends Filter
{
    protected ?string $trueLabel = null;

    protected ?string $falseLabel = null;

    public function trueLabel(string $label): static
    {
        $this->trueLabel = $label;

        return $this;
    }

    public function falseLabel(string $label): static
    {
        $this->falseLabel = $label;

        return $this;
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     */
    protected function applyDefault(Builder $query, mixed $value): void
    {
        $column = $this->getColumn();
        $boolValue = filter_var($value, FILTER_VALIDATE_BOOLEAN);

        $query->where($column, $boolValue);
    }

    /**
     * @return array<string, mixed>
     */
    public function toInertiaProps(): array
    {
        return [
            ...parent::toInertiaProps(),
            'trueLabel' => $this->trueLabel ?? 'Yes',
            'falseLabel' => $this->falseLabel ?? 'No',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toFlutterProps(): array
    {
        return [
            ...parent::toFlutterProps(),
            'trueLabel' => $this->trueLabel ?? 'Yes',
            'falseLabel' => $this->falseLabel ?? 'No',
        ];
    }
}
