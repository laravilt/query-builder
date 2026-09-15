<?php

declare(strict_types=1);

namespace Laravilt\QueryBuilder;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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

    /** @var array<int, string> */
    protected array $searchableColumns = [];

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

    /**
     * Columns the search term is matched against (OR'd together). Use dot notation
     * (e.g. `author.name`) to search a column on a relation.
     *
     * @param  array<int, string>|string  $columns
     */
    public function searchable(array|string $columns): static
    {
        $this->searchableColumns = array_values(array_filter(
            is_array($columns) ? $columns : func_get_args(),
            fn ($column) => is_string($column) && $column !== '',
        ));

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getSearchableColumns(): array
    {
        return $this->searchableColumns;
    }

    public function sortBy(?string $column, ?string $direction = 'asc'): static
    {
        $this->sortBy = $column;
        // Validate sort direction
        $direction = $direction === null ? null : strtolower($direction);
        $this->sortDirection = in_array($direction, ['asc', 'desc'], true) ? $direction : 'asc';

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
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public function apply(Builder $query): Builder
    {
        // Apply filters
        foreach ($this->filters as $filter) {
            $value = $this->filterValues[$filter->getName()] ?? null;

            // A cleared multi-select arrives as [], which would otherwise become `whereIn(col, [])` and match nothing
            if ($value !== null && $value !== '' && $value !== []) {
                $filter->apply($query, $value);
            }
        }

        // Apply search if configured
        $this->applySearch($query);

        // Apply sorting
        if ($this->sortBy !== null) {
            $column = $this->resolveSortColumn($this->sortBy);

            if ($column !== null) {
                $query->orderBy($column, $this->sortDirection ?? 'asc');
            }
        }

        return $query;
    }

    /**
     * Map the requested sort to its column. When sorts are registered they act as an
     * allow-list (the request carries the Sort name, which may differ from its column);
     * without registered sorts the value is used as the column directly.
     */
    protected function resolveSortColumn(string $sortBy): ?string
    {
        if ($this->sorts === []) {
            return $sortBy;
        }

        foreach ($this->sorts as $sort) {
            if ($sort->getName() === $sortBy) {
                return $sort->getColumn();
            }
        }

        return null;
    }

    /**
     * @param  Builder<Model>  $query
     */
    protected function applySearch(Builder $query): void
    {
        $term = trim((string) $this->search);

        if ($term === '' || $this->searchableColumns === []) {
            return;
        }

        $pattern = '%'.$this->escapeLike($term).'%';

        $query->where(function (Builder $query) use ($pattern): void {
            foreach ($this->searchableColumns as $column) {
                $relation = str_contains($column, '.') ? substr($column, 0, (int) strrpos($column, '.')) : null;

                if ($relation !== null && $this->isRelationPath($query->getModel(), $relation)) {
                    $relatedColumn = substr($column, strrpos($column, '.') + 1);

                    $query->orWhereHas($relation, function (Builder $query) use ($relatedColumn, $pattern): void {
                        $this->whereLike($query, $query->qualifyColumn($relatedColumn), $pattern);
                    });

                    continue;
                }

                $query->orWhere(function (Builder $query) use ($column, $pattern): void {
                    $this->whereLike($query, $column, $pattern);
                });
            }
        });
    }

    /**
     * @param  Builder<Model>  $query
     */
    protected function whereLike(Builder $query, string $column, string $pattern): void
    {
        $grammar = $query->getQuery()->getGrammar();

        // An explicit ESCAPE clause makes the backslash escaping portable (sqlite and
        // pgsql have no default escape character; mysql's default is already '\').
        $query->whereRaw($grammar->wrap($column).' like ? escape ?', [$pattern, '\\']);
    }

    protected function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    protected function isRelationPath(Model $model, string $path): bool
    {
        foreach (explode('.', $path) as $segment) {
            if (! $model->isRelation($segment)) {
                return false;
            }

            $model = $model->{$segment}()->getRelated();
        }

        return true;
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
