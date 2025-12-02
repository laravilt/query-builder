<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravilt\QueryBuilder\Filters\BooleanFilter;

beforeEach(function () {
    Schema::create('test_users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->boolean('is_active')->default(true);
        $table->boolean('is_verified')->default(false);
        $table->timestamps();
    });

    $this->model = new class extends Model
    {
        protected $table = 'test_users';

        protected $guarded = [];

        protected $casts = [
            'is_active' => 'boolean',
            'is_verified' => 'boolean',
        ];
    };

    // Insert test data
    $this->model::create(['name' => 'User 1', 'is_active' => true, 'is_verified' => true]);
    $this->model::create(['name' => 'User 2', 'is_active' => false, 'is_verified' => false]);
    $this->model::create(['name' => 'User 3', 'is_active' => true, 'is_verified' => false]);
    $this->model::create(['name' => 'User 4', 'is_active' => false, 'is_verified' => true]);
});

afterEach(function () {
    Schema::dropIfExists('test_users');
});

test('can create boolean filter instance', function () {
    $filter = BooleanFilter::make('is_active');

    expect($filter)->toBeInstanceOf(BooleanFilter::class);
    expect($filter->getName())->toBe('is_active');
});

test('can set label', function () {
    $filter = BooleanFilter::make('is_active')
        ->label('Active Status');

    expect($filter->getLabel())->toBe('Active Status');
});

test('can set custom column', function () {
    $filter = BooleanFilter::make('active')
        ->column('is_active');

    expect($filter->getColumn())->toBe('is_active');
});

test('can set true label', function () {
    $filter = BooleanFilter::make('is_active')
        ->trueLabel('Active');

    $props = $filter->toInertiaProps();

    expect($props['trueLabel'])->toBe('Active');
});

test('can set false label', function () {
    $filter = BooleanFilter::make('is_active')
        ->falseLabel('Inactive');

    $props = $filter->toInertiaProps();

    expect($props['falseLabel'])->toBe('Inactive');
});

test('can set default value', function () {
    $filter = BooleanFilter::make('is_active')
        ->default(true);

    $props = $filter->toInertiaProps();

    expect($props['default'])->toBe(true);
});

test('applies true value to query', function () {
    $filter = BooleanFilter::make('is_active');

    $query = $this->model::query();
    $filter->apply($query, true);

    $results = $query->get();

    expect($results)->toHaveCount(2);
    expect($results->every(fn ($user) => $user->is_active === true))->toBeTrue();
});

test('applies false value to query', function () {
    $filter = BooleanFilter::make('is_active');

    $query = $this->model::query();
    $filter->apply($query, false);

    $results = $query->get();

    expect($results)->toHaveCount(2);
    expect($results->every(fn ($user) => $user->is_active === false))->toBeTrue();
});

test('applies string "true" value to query', function () {
    $filter = BooleanFilter::make('is_active');

    $query = $this->model::query();
    $filter->apply($query, 'true');

    $results = $query->get();

    expect($results)->toHaveCount(2);
    expect($results->every(fn ($user) => $user->is_active === true))->toBeTrue();
});

test('applies string "false" value to query', function () {
    $filter = BooleanFilter::make('is_active');

    $query = $this->model::query();
    $filter->apply($query, 'false');

    $results = $query->get();

    expect($results)->toHaveCount(2);
    expect($results->every(fn ($user) => $user->is_active === false))->toBeTrue();
});

test('applies integer 1 value to query', function () {
    $filter = BooleanFilter::make('is_active');

    $query = $this->model::query();
    $filter->apply($query, 1);

    $results = $query->get();

    expect($results)->toHaveCount(2);
    expect($results->every(fn ($user) => $user->is_active === true))->toBeTrue();
});

test('applies integer 0 value to query', function () {
    $filter = BooleanFilter::make('is_active');

    $query = $this->model::query();
    $filter->apply($query, 0);

    $results = $query->get();

    expect($results)->toHaveCount(2);
    expect($results->every(fn ($user) => $user->is_active === false))->toBeTrue();
});

test('applies custom query closure', function () {
    $filter = BooleanFilter::make('is_active')
        ->query(function ($query, $value) {
            if ($value) {
                $query->where('is_active', true)->where('is_verified', true);
            } else {
                $query->where('is_active', false);
            }
        });

    $query = $this->model::query();
    $filter->apply($query, true);

    $results = $query->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->name)->toBe('User 1');
});

test('serializes to inertia props correctly', function () {
    $filter = BooleanFilter::make('is_active')
        ->label('Active Status')
        ->trueLabel('Active')
        ->falseLabel('Inactive')
        ->default(true);

    $props = $filter->toInertiaProps();

    expect($props)->toMatchArray([
        'type' => 'BooleanFilter',
        'name' => 'is_active',
        'label' => 'Active Status',
        'column' => 'is_active',
        'trueLabel' => 'Active',
        'falseLabel' => 'Inactive',
        'default' => true,
        'visible' => true,
    ]);
});

test('serializes to flutter props correctly', function () {
    $filter = BooleanFilter::make('is_active')
        ->trueLabel('Active')
        ->falseLabel('Inactive');

    $props = $filter->toFlutterProps();

    expect($props)->toHaveKeys([
        'type',
        'name',
        'label',
        'column',
        'trueLabel',
        'falseLabel',
        'default',
        'visible',
        'placeholder',
    ]);
});

test('returns correct default values', function () {
    $filter = BooleanFilter::make('is_active');

    $props = $filter->toInertiaProps();

    expect($props['trueLabel'])->toBe('Yes');
    expect($props['falseLabel'])->toBe('No');
    expect($props['visible'])->toBe(true);
});
