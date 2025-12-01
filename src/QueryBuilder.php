<?php

declare(strict_types=1);

namespace Laravilt\QueryBuilder;

use Illuminate\Database\Eloquent\Builder;
use Laravilt\QueryBuilder\Filters\Filter;
use Laravilt\Support\Contracts\InertiaSerializable;

class QueryBuilder implements InertiaSerializable
{
    /** @var array<int, Filter> */
    protected array $filters = [];

    /** @var array<int, Sort> */
    protected array $sorts = [];

    /** @var array<string, mixed> */
    protected array $filterValues = [];

    protected ?string $search = null;

    protected ?string $sortBy = null;

    protected ?string $sortDirection = 'asc';

    protected int $perPage = 15;

    protected bool $paginated = true;

    /**
     * @param  array<int, Filter>  $filters
     */
    public function filters(array $filters): static
    {
        $this->filters = $filters;

        return $this;
    }

    public function addFilter(Filter $filter): static
    {
        $this->filters[] = $filter;

        return $this;
    }

    /**
     * @param  array<int, Sort>  $sorts
     */
    public function sorts(array $sorts): static
    {
        $this->sorts = $sorts;

        return $this;
    }

    public function addSort(Sort $sort): static
    {
        $this->sorts[] = $sort;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function applyFilters(array $values): static
    {
        $this->filterValues = $values;

        return $this;
    }

    public function search(?string $search): static
    {
        $this->search = $search;

        return $this;
    }

    public function sortBy(?string $column, ?string $direction = 'asc'): static
    {
        $this->sortBy = $column;
        $this->sortDirection = $direction;

        return $this;
    }

    public function perPage(int $perPage): static
    {
        $this->perPage = $perPage;

        return $this;
    }

    public function paginated(bool $condition = true): static
    {
        $this->paginated = $condition;

        return $this;
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @return Builder<\Illuminate\Database\Eloquent\Model>
     */
    public function apply(Builder $query): Builder
    {
        // Apply filters
        foreach ($this->filters as $filter) {
            $value = $this->filterValues[$filter->getName()] ?? null;

            if ($value !== null && $value !== '') {
                $filter->apply($query, $value);
            }
        }

        // Apply search if configured
        if ($this->search !== null && $this->search !== '') {
            $this->applySearch($query);
        }

        // Apply sorting
        if ($this->sortBy !== null) {
            $query->orderBy($this->sortBy, $this->sortDirection ?? 'asc');
        }

        return $query;
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     */
    protected function applySearch(Builder $query): void
    {
        // Search implementation can be customized per use case
        // This is a basic implementation
    }

    /**
     * @return array<string, mixed>
     */
    public function toInertiaProps(): array
    {
        return [
            'filters' => array_map(
                fn (Filter $filter) => $filter->toInertiaProps(),
                $this->filters
            ),
            'sorts' => array_map(
                fn (Sort $sort) => $sort->toInertiaProps(),
                $this->sorts
            ),
            'filterValues' => $this->filterValues,
            'search' => $this->search,
            'sortBy' => $this->sortBy,
            'sortDirection' => $this->sortDirection,
            'perPage' => $this->perPage,
            'paginated' => $this->paginated,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toFlutterProps(): array
    {
        return [
            'filters' => array_map(
                fn (Filter $filter) => $filter->toFlutterProps(),
                $this->filters
            ),
            'sorts' => array_map(
                fn (Sort $sort) => $sort->toFlutterProps(),
                $this->sorts
            ),
            'filterValues' => $this->filterValues,
            'search' => $this->search,
            'sortBy' => $this->sortBy,
            'sortDirection' => $this->sortDirection,
            'perPage' => $this->perPage,
            'paginated' => $this->paginated,
        ];
    }
}
