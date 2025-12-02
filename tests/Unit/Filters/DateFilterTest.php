<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravilt\QueryBuilder\Filters\DateFilter;

beforeEach(function () {
    Schema::create('test_posts', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->timestamp('published_at')->nullable();
        $table->timestamps();
    });

    $this->model = new class extends Model
    {
        protected $table = 'test_posts';

        protected $guarded = [];

        protected $casts = [
            'published_at' => 'datetime',
        ];
    };

    // Insert test data
    $this->model::create(['title' => 'Post 1', 'published_at' => '2024-01-01']);
    $this->model::create(['title' => 'Post 2', 'published_at' => '2024-01-15']);
    $this->model::create(['title' => 'Post 3', 'published_at' => '2024-02-01']);
    $this->model::create(['title' => 'Post 4', 'published_at' => '2024-03-01']);
});

afterEach(function () {
    Schema::dropIfExists('test_posts');
});

test('can create date filter instance', function () {
    $filter = DateFilter::make('published_at');

    expect($filter)->toBeInstanceOf(DateFilter::class);
    expect($filter->getName())->toBe('published_at');
});

test('can set label', function () {
    $filter = DateFilter::make('published_at')
        ->label('Publication Date');

    expect($filter->getLabel())->toBe('Publication Date');
});

test('can set custom column', function () {
    $filter = DateFilter::make('publish_date')
        ->column('published_at');

    expect($filter->getColumn())->toBe('published_at');
});

test('can set operator', function () {
    $filter = DateFilter::make('published_at')
        ->operator('>');

    $props = $filter->toInertiaProps();

    expect($props['operator'])->toBe('>');
});

test('can set before operator', function () {
    $filter = DateFilter::make('published_at')
        ->before();

    $props = $filter->toInertiaProps();

    expect($props['operator'])->toBe('<');
});

test('can set after operator', function () {
    $filter = DateFilter::make('published_at')
        ->after();

    $props = $filter->toInertiaProps();

    expect($props['operator'])->toBe('>');
});

test('can set between operator', function () {
    $filter = DateFilter::make('published_at')
        ->between();

    $props = $filter->toInertiaProps();

    expect($props['operator'])->toBe('between');
});

test('can set min date', function () {
    $filter = DateFilter::make('published_at')
        ->minDate('2024-01-01');

    $props = $filter->toInertiaProps();

    expect($props['minDate'])->toBe('2024-01-01');
});

test('can set max date', function () {
    $filter = DateFilter::make('published_at')
        ->maxDate('2024-12-31');

    $props = $filter->toInertiaProps();

    expect($props['maxDate'])->toBe('2024-12-31');
});

test('can enable with time', function () {
    $filter = DateFilter::make('published_at')
        ->withTime();

    $props = $filter->toInertiaProps();

    expect($props['withTime'])->toBeTrue();
});

test('applies equal operator to query', function () {
    $filter = DateFilter::make('published_at');

    $query = $this->model::query();
    $filter->apply($query, '2024-01-01 00:00:00');

    $results = $query->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->title)->toBe('Post 1');
});

test('applies before operator to query', function () {
    $filter = DateFilter::make('published_at')
        ->before();

    $query = $this->model::query();
    $filter->apply($query, '2024-02-01');

    $results = $query->get();

    expect($results)->toHaveCount(2);
});

test('applies after operator to query', function () {
    $filter = DateFilter::make('published_at')
        ->after();

    $query = $this->model::query();
    $filter->apply($query, '2024-01-15 23:59:59');

    $results = $query->get();

    expect($results)->toHaveCount(2);
});

test('applies between operator to query', function () {
    $filter = DateFilter::make('published_at')
        ->between();

    $query = $this->model::query();
    $filter->apply($query, ['2024-01-10', '2024-02-10']);

    $results = $query->get();

    expect($results)->toHaveCount(2);
    expect($results->pluck('title')->toArray())->toBe(['Post 2', 'Post 3']);
});

test('applies custom query closure', function () {
    $filter = DateFilter::make('published_at')
        ->query(function ($query, $value) {
            $query->whereYear('published_at', '2024');
        });

    $query = $this->model::query();
    $filter->apply($query, '2024');

    $results = $query->get();

    expect($results)->toHaveCount(4);
});

test('serializes to inertia props correctly', function () {
    $filter = DateFilter::make('published_at')
        ->label('Publication Date')
        ->between()
        ->minDate('2024-01-01')
        ->maxDate('2024-12-31')
        ->withTime();

    $props = $filter->toInertiaProps();

    expect($props)->toMatchArray([
        'type' => 'DateFilter',
        'name' => 'published_at',
        'label' => 'Publication Date',
        'column' => 'published_at',
        'operator' => 'between',
        'minDate' => '2024-01-01',
        'maxDate' => '2024-12-31',
        'withTime' => true,
        'visible' => true,
    ]);
});

test('serializes to flutter props correctly', function () {
    $filter = DateFilter::make('published_at')
        ->between();

    $props = $filter->toFlutterProps();

    expect($props)->toHaveKeys([
        'type',
        'name',
        'label',
        'column',
        'operator',
        'minDate',
        'maxDate',
        'withTime',
        'default',
        'visible',
        'placeholder',
    ]);
});

test('returns correct default values', function () {
    $filter = DateFilter::make('published_at');

    $props = $filter->toInertiaProps();

    expect($props['operator'])->toBe('=');
    expect($props['minDate'])->toBeNull();
    expect($props['maxDate'])->toBeNull();
    expect($props['withTime'])->toBe(false);
    expect($props['visible'])->toBe(true);
});
