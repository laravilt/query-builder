<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravilt\QueryBuilder\Filters\SelectFilter;

beforeEach(function () {
    Schema::create('test_users', function (Blueprint $table) {
        $table->id();
        $table->string('status');
        $table->string('role');
        $table->timestamps();
    });

    $this->model = new class extends Model
    {
        protected $table = 'test_users';

        protected $guarded = [];
    };

    // Insert test data
    $this->model::create(['status' => 'active', 'role' => 'admin']);
    $this->model::create(['status' => 'inactive', 'role' => 'user']);
    $this->model::create(['status' => 'active', 'role' => 'user']);
    $this->model::create(['status' => 'pending', 'role' => 'admin']);
});

afterEach(function () {
    Schema::dropIfExists('test_users');
});

test('can create select filter instance', function () {
    $filter = SelectFilter::make('status');

    expect($filter)->toBeInstanceOf(SelectFilter::class);
    expect($filter->getName())->toBe('status');
});

test('can set options', function () {
    $filter = SelectFilter::make('status')
        ->options([
            'active' => 'Active',
            'inactive' => 'Inactive',
        ]);

    $props = $filter->toInertiaProps();

    expect($props['options'])->toBe([
        'active' => 'Active',
        'inactive' => 'Inactive',
    ]);
});

test('can set label', function () {
    $filter = SelectFilter::make('status')
        ->label('User Status');

    expect($filter->getLabel())->toBe('User Status');
});

test('can set custom column', function () {
    $filter = SelectFilter::make('user_status')
        ->column('status');

    expect($filter->getColumn())->toBe('status');
});

test('can enable multiple selection', function () {
    $filter = SelectFilter::make('status')
        ->multiple();

    $props = $filter->toInertiaProps();

    expect($props['multiple'])->toBeTrue();
});

test('can enable searchable', function () {
    $filter = SelectFilter::make('status')
        ->searchable();

    $props = $filter->toInertiaProps();

    expect($props['searchable'])->toBeTrue();
});

test('can set default value', function () {
    $filter = SelectFilter::make('status')
        ->default('active');

    $props = $filter->toInertiaProps();

    expect($props['default'])->toBe('active');
});

test('can set visibility', function () {
    $filter = SelectFilter::make('status')
        ->visible(false);

    $props = $filter->toInertiaProps();

    expect($props['visible'])->toBe(false);
});

test('can set placeholder', function () {
    $filter = SelectFilter::make('status')
        ->placeholder('Select status...');

    $props = $filter->toInertiaProps();

    expect($props['placeholder'])->toBe('Select status...');
});

test('applies single value filter to query', function () {
    $filter = SelectFilter::make('status');

    $query = $this->model::query();
    $filter->apply($query, 'active');

    $results = $query->get();

    expect($results)->toHaveCount(2);
    expect($results->every(fn ($user) => $user->status === 'active'))->toBeTrue();
});

test('applies multiple values filter to query', function () {
    $filter = SelectFilter::make('status')
        ->multiple();

    $query = $this->model::query();
    $filter->apply($query, ['active', 'pending']);

    $results = $query->get();

    expect($results)->toHaveCount(3);
    expect($results->whereIn('status', ['active', 'pending'])->count())->toBe(3);
});

test('applies custom query closure', function () {
    $filter = SelectFilter::make('status')
        ->query(function ($query, $value) {
            $query->where('status', '!=', $value);
        });

    $query = $this->model::query();
    $filter->apply($query, 'active');

    $results = $query->get();

    expect($results)->toHaveCount(2);
    expect($results->every(fn ($user) => $user->status !== 'active'))->toBeTrue();
});

test('serializes to inertia props correctly', function () {
    $filter = SelectFilter::make('status')
        ->label('User Status')
        ->options(['active' => 'Active', 'inactive' => 'Inactive'])
        ->multiple()
        ->searchable()
        ->default('active')
        ->placeholder('Select status...');

    $props = $filter->toInertiaProps();

    expect($props)->toMatchArray([
        'type' => 'SelectFilter',
        'name' => 'status',
        'label' => 'User Status',
        'column' => 'status',
        'options' => ['active' => 'Active', 'inactive' => 'Inactive'],
        'multiple' => true,
        'searchable' => true,
        'default' => 'active',
        'visible' => true,
        'placeholder' => 'Select status...',
    ]);
});

test('serializes to flutter props correctly', function () {
    $filter = SelectFilter::make('status')
        ->label('User Status')
        ->options(['active' => 'Active'])
        ->multiple();

    $props = $filter->toFlutterProps();

    expect($props)->toHaveKeys([
        'type',
        'name',
        'label',
        'column',
        'options',
        'multiple',
        'searchable',
        'default',
        'visible',
        'placeholder',
    ]);
});

test('returns correct default values', function () {
    $filter = SelectFilter::make('status');

    $props = $filter->toInertiaProps();

    expect($props['multiple'])->toBe(false);
    expect($props['searchable'])->toBe(false);
    expect($props['visible'])->toBe(true);
    expect($props['options'])->toBe([]);
});
