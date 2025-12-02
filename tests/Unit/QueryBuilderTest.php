<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravilt\QueryBuilder\Filters\BooleanFilter;
use Laravilt\QueryBuilder\Filters\SelectFilter;
use Laravilt\QueryBuilder\Filters\TextFilter;
use Laravilt\QueryBuilder\QueryBuilder;
use Laravilt\QueryBuilder\Sort;

beforeEach(function () {
    // Create a test table
    Schema::create('test_users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->string('status');
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });

    // Create test model
    $this->model = new class extends Model
    {
        protected $table = 'test_users';

        protected $guarded = [];
    };

    // Insert test data
    for ($i = 1; $i <= 20; $i++) {
        $this->model::create([
            'name' => "User {$i}",
            'email' => "user{$i}@example.com",
            'status' => $i % 2 === 0 ? 'active' : 'inactive',
            'is_active' => $i % 3 === 0,
        ]);
    }
});

afterEach(function () {
    Schema::dropIfExists('test_users');
});

test('can create query builder instance', function () {
    $queryBuilder = new QueryBuilder;

    expect($queryBuilder)->toBeInstanceOf(QueryBuilder::class);
});

test('can add filters using filters method', function () {
    $queryBuilder = new QueryBuilder;

    $filters = [
        SelectFilter::make('status')->options(['active' => 'Active', 'inactive' => 'Inactive']),
        TextFilter::make('name'),
    ];

    $queryBuilder->filters($filters);

    $props = $queryBuilder->toInertiaProps();

    expect($props['filters'])->toHaveCount(2);
    expect($props['filters'][0]['name'])->toBe('status');
    expect($props['filters'][1]['name'])->toBe('name');
});

test('can add single filter using addFilter method', function () {
    $queryBuilder = new QueryBuilder;

    $queryBuilder->addFilter(SelectFilter::make('status')->options(['active' => 'Active']));
    $queryBuilder->addFilter(TextFilter::make('name'));

    $props = $queryBuilder->toInertiaProps();

    expect($props['filters'])->toHaveCount(2);
});

test('can add sorts using sorts method', function () {
    $queryBuilder = new QueryBuilder;

    $sorts = [
        Sort::make('name')->label('Name'),
        Sort::make('created_at')->label('Date'),
    ];

    $queryBuilder->sorts($sorts);

    $props = $queryBuilder->toInertiaProps();

    expect($props['sorts'])->toHaveCount(2);
    expect($props['sorts'][0]['name'])->toBe('name');
    expect($props['sorts'][1]['name'])->toBe('created_at');
});

test('can add single sort using addSort method', function () {
    $queryBuilder = new QueryBuilder;

    $queryBuilder->addSort(Sort::make('name')->label('Name'));
    $queryBuilder->addSort(Sort::make('created_at')->label('Date'));

    $props = $queryBuilder->toInertiaProps();

    expect($props['sorts'])->toHaveCount(2);
});

test('can apply filter values', function () {
    $queryBuilder = new QueryBuilder;

    $queryBuilder->applyFilters([
        'status' => 'active',
        'name' => 'John',
    ]);

    $props = $queryBuilder->toInertiaProps();

    expect($props['filterValues'])->toBe([
        'status' => 'active',
        'name' => 'John',
    ]);
});

test('can set search query', function () {
    $queryBuilder = new QueryBuilder;

    $queryBuilder->search('test query');

    $props = $queryBuilder->toInertiaProps();

    expect($props['search'])->toBe('test query');
});

test('can set sort by and direction', function () {
    $queryBuilder = new QueryBuilder;

    $queryBuilder->sortBy('name', 'desc');

    $props = $queryBuilder->toInertiaProps();

    expect($props['sortBy'])->toBe('name');
    expect($props['sortDirection'])->toBe('desc');
});

test('can set per page value', function () {
    $queryBuilder = new QueryBuilder;

    $queryBuilder->perPage(25);

    $props = $queryBuilder->toInertiaProps();

    expect($props['perPage'])->toBe(25);
});

test('can set paginated state', function () {
    $queryBuilder = new QueryBuilder;

    $queryBuilder->paginated(false);

    $props = $queryBuilder->toInertiaProps();

    expect($props['paginated'])->toBe(false);
});

test('applies filters to eloquent query', function () {
    $queryBuilder = new QueryBuilder;

    $queryBuilder
        ->filters([
            SelectFilter::make('status'),
        ])
        ->applyFilters(['status' => 'active']);

    $query = $this->model::query();
    $queryBuilder->apply($query);

    $results = $query->get();

    expect($results)->toHaveCount(10);
    expect($results->every(fn ($user) => $user->status === 'active'))->toBeTrue();
});

test('applies sorting to eloquent query', function () {
    $queryBuilder = new QueryBuilder;

    $queryBuilder->sortBy('name', 'desc');

    $query = $this->model::query();
    $queryBuilder->apply($query);

    $results = $query->get();

    expect($results->first()->name)->toBe('User 9');
});

test('applies multiple filters to eloquent query', function () {
    $queryBuilder = new QueryBuilder;

    $queryBuilder
        ->filters([
            SelectFilter::make('status'),
            BooleanFilter::make('is_active'),
        ])
        ->applyFilters([
            'status' => 'active',
            'is_active' => true,
        ]);

    $query = $this->model::query();
    $queryBuilder->apply($query);

    $results = $query->get();

    expect($results->every(fn ($user) => $user->status === 'active' && $user->is_active))->toBeTrue();
});

test('ignores null or empty filter values', function () {
    $queryBuilder = new QueryBuilder;

    $queryBuilder
        ->filters([
            SelectFilter::make('status'),
            TextFilter::make('name'),
        ])
        ->applyFilters([
            'status' => null,
            'name' => '',
        ]);

    $query = $this->model::query();
    $queryBuilder->apply($query);

    $results = $query->get();

    expect($results)->toHaveCount(20); // All records returned
});

test('serializes to inertia props correctly', function () {
    $queryBuilder = new QueryBuilder;

    $queryBuilder
        ->filters([
            SelectFilter::make('status')->options(['active' => 'Active']),
        ])
        ->sorts([
            Sort::make('name')->label('Name'),
        ])
        ->applyFilters(['status' => 'active'])
        ->search('test')
        ->sortBy('name', 'asc')
        ->perPage(25)
        ->paginated(true);

    $props = $queryBuilder->toInertiaProps();

    expect($props)->toHaveKeys([
        'filters',
        'sorts',
        'filterValues',
        'search',
        'sortBy',
        'sortDirection',
        'perPage',
        'paginated',
    ]);

    expect($props['filters'])->toHaveCount(1);
    expect($props['sorts'])->toHaveCount(1);
    expect($props['filterValues'])->toBe(['status' => 'active']);
    expect($props['search'])->toBe('test');
    expect($props['sortBy'])->toBe('name');
    expect($props['sortDirection'])->toBe('asc');
    expect($props['perPage'])->toBe(25);
    expect($props['paginated'])->toBeTrue();
});

test('serializes to flutter props correctly', function () {
    $queryBuilder = new QueryBuilder;

    $queryBuilder
        ->filters([
            SelectFilter::make('status')->options(['active' => 'Active']),
        ])
        ->sorts([
            Sort::make('name')->label('Name'),
        ])
        ->applyFilters(['status' => 'active'])
        ->search('test')
        ->sortBy('name', 'asc')
        ->perPage(25)
        ->paginated(true);

    $props = $queryBuilder->toFlutterProps();

    expect($props)->toHaveKeys([
        'filters',
        'sorts',
        'filterValues',
        'search',
        'sortBy',
        'sortDirection',
        'perPage',
        'paginated',
    ]);
});

test('returns correct default values', function () {
    $queryBuilder = new QueryBuilder;

    $props = $queryBuilder->toInertiaProps();

    expect($props['filters'])->toBe([]);
    expect($props['sorts'])->toBe([]);
    expect($props['filterValues'])->toBe([]);
    expect($props['search'])->toBeNull();
    expect($props['sortBy'])->toBeNull();
    expect($props['sortDirection'])->toBe('asc');
    expect($props['perPage'])->toBe(15);
    expect($props['paginated'])->toBeTrue();
});
