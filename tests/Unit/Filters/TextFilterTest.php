<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravilt\QueryBuilder\Filters\TextFilter;

beforeEach(function () {
    Schema::create('test_products', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('sku');
        $table->text('description');
        $table->timestamps();
    });

    $this->model = new class extends Model
    {
        protected $table = 'test_products';

        protected $guarded = [];
    };

    // Insert test data
    $this->model::create(['name' => 'iPhone 15', 'sku' => 'IPH-15-BLK', 'description' => 'Latest iPhone model']);
    $this->model::create(['name' => 'Samsung Galaxy', 'sku' => 'SAM-GAL-WHT', 'description' => 'Android smartphone']);
    $this->model::create(['name' => 'iPhone 14', 'sku' => 'IPH-14-BLU', 'description' => 'Previous iPhone model']);
    $this->model::create(['name' => 'Google Pixel', 'sku' => 'GOO-PIX-GRN', 'description' => 'Google phone']);
});

afterEach(function () {
    Schema::dropIfExists('test_products');
});

test('can create text filter instance', function () {
    $filter = TextFilter::make('name');

    expect($filter)->toBeInstanceOf(TextFilter::class);
    expect($filter->getName())->toBe('name');
});

test('can set label', function () {
    $filter = TextFilter::make('name')
        ->label('Product Name');

    expect($filter->getLabel())->toBe('Product Name');
});

test('can set custom column', function () {
    $filter = TextFilter::make('product_name')
        ->column('name');

    expect($filter->getColumn())->toBe('name');
});

test('can set operator', function () {
    $filter = TextFilter::make('name')
        ->operator('=');

    $props = $filter->toInertiaProps();

    expect($props['operator'])->toBe('=');
});

test('can set exact operator', function () {
    $filter = TextFilter::make('name')
        ->exact();

    $props = $filter->toInertiaProps();

    expect($props['operator'])->toBe('=');
});

test('can set contains operator', function () {
    $filter = TextFilter::make('name')
        ->contains();

    $props = $filter->toInertiaProps();

    expect($props['operator'])->toBe('like');
});

test('can set starts with operator', function () {
    $filter = TextFilter::make('name')
        ->startsWith();

    $props = $filter->toInertiaProps();

    expect($props['operator'])->toBe('starts_with');
});

test('can set ends with operator', function () {
    $filter = TextFilter::make('name')
        ->endsWith();

    $props = $filter->toInertiaProps();

    expect($props['operator'])->toBe('ends_with');
});

test('can set case sensitive', function () {
    $filter = TextFilter::make('name')
        ->caseSensitive();

    $props = $filter->toInertiaProps();

    expect($props['caseSensitive'])->toBeTrue();
});

test('applies contains operator to query', function () {
    $filter = TextFilter::make('name')
        ->contains();

    $query = $this->model::query();
    $filter->apply($query, 'iPhone');

    $results = $query->get();

    expect($results)->toHaveCount(2);
    expect($results->pluck('name')->toArray())->toBe(['iPhone 15', 'iPhone 14']);
});

test('applies exact operator to query', function () {
    $filter = TextFilter::make('name')
        ->exact();

    $query = $this->model::query();
    $filter->apply($query, 'iPhone 15');

    $results = $query->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->name)->toBe('iPhone 15');
});

test('applies starts with operator to query', function () {
    $filter = TextFilter::make('sku')
        ->startsWith();

    $query = $this->model::query();
    $filter->apply($query, 'IPH');

    $results = $query->get();

    expect($results)->toHaveCount(2);
    expect($results->pluck('name')->toArray())->toBe(['iPhone 15', 'iPhone 14']);
});

test('applies ends with operator to query', function () {
    $filter = TextFilter::make('sku')
        ->endsWith();

    $query = $this->model::query();
    $filter->apply($query, 'WHT');

    $results = $query->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->name)->toBe('Samsung Galaxy');
});

test('applies default like operator to query', function () {
    $filter = TextFilter::make('description');

    $query = $this->model::query();
    $filter->apply($query, 'iPhone');

    $results = $query->get();

    expect($results)->toHaveCount(2);
});

test('applies custom query closure', function () {
    $filter = TextFilter::make('name')
        ->query(function ($query, $value) {
            $query->where('name', 'like', "%{$value}%")
                ->orWhere('description', 'like', "%{$value}%");
        });

    $query = $this->model::query();
    $filter->apply($query, 'iPhone');

    $results = $query->get();

    expect($results)->toHaveCount(2);
});

test('serializes to inertia props correctly', function () {
    $filter = TextFilter::make('name')
        ->label('Product Name')
        ->contains()
        ->caseSensitive()
        ->placeholder('Search products...');

    $props = $filter->toInertiaProps();

    expect($props)->toMatchArray([
        'type' => 'TextFilter',
        'name' => 'name',
        'label' => 'Product Name',
        'column' => 'name',
        'operator' => 'like',
        'caseSensitive' => true,
        'visible' => true,
        'placeholder' => 'Search products...',
    ]);
});

test('serializes to flutter props correctly', function () {
    $filter = TextFilter::make('name')
        ->contains();

    $props = $filter->toFlutterProps();

    expect($props)->toHaveKeys([
        'type',
        'name',
        'label',
        'column',
        'operator',
        'caseSensitive',
        'default',
        'visible',
        'placeholder',
    ]);
});

test('returns correct default values', function () {
    $filter = TextFilter::make('name');

    $props = $filter->toInertiaProps();

    expect($props['operator'])->toBe('like');
    expect($props['caseSensitive'])->toBe(false);
    expect($props['visible'])->toBe(true);
});
