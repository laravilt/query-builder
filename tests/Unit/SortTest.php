<?php

declare(strict_types=1);

use Laravilt\QueryBuilder\Sort;

test('can create sort instance', function () {
    $sort = Sort::make('name');

    expect($sort)->toBeInstanceOf(Sort::class);
    expect($sort->getName())->toBe('name');
});

test('can create sort with custom column', function () {
    $sort = Sort::make('user_name', 'name');

    expect($sort->getName())->toBe('user_name');
    expect($sort->getColumn())->toBe('name');
});

test('can set label', function () {
    $sort = Sort::make('name')
        ->label('User Name');

    expect($sort->getLabel())->toBe('User Name');
});

test('generates label from name if not provided', function () {
    $sort = Sort::make('created_at');

    expect($sort->getLabel())->toBe('Created At');
});

test('can set custom column', function () {
    $sort = Sort::make('name')
        ->column('users.name');

    expect($sort->getColumn())->toBe('users.name');
});

test('can set default direction', function () {
    $sort = Sort::make('name')
        ->defaultDirection('desc');

    $props = $sort->toInertiaProps();

    expect($props['defaultDirection'])->toBe('desc');
});

test('can set visibility', function () {
    $sort = Sort::make('name')
        ->visible(false);

    $props = $sort->toInertiaProps();

    expect($props['visible'])->toBe(false);
});

test('serializes to inertia props correctly', function () {
    $sort = Sort::make('name')
        ->label('User Name')
        ->column('users.name')
        ->defaultDirection('desc')
        ->visible(true);

    $props = $sort->toInertiaProps();

    expect($props)->toMatchArray([
        'name' => 'name',
        'label' => 'User Name',
        'column' => 'users.name',
        'defaultDirection' => 'desc',
        'visible' => true,
    ]);
});

test('serializes to flutter props correctly', function () {
    $sort = Sort::make('name')
        ->label('User Name')
        ->defaultDirection('desc');

    $props = $sort->toFlutterProps();

    expect($props)->toHaveKeys([
        'name',
        'label',
        'column',
        'defaultDirection',
        'visible',
    ]);

    expect($props)->toMatchArray([
        'name' => 'name',
        'label' => 'User Name',
        'column' => 'name',
        'defaultDirection' => 'desc',
        'visible' => true,
    ]);
});

test('returns correct default values', function () {
    $sort = Sort::make('name');

    $props = $sort->toInertiaProps();

    expect($props['name'])->toBe('name');
    expect($props['column'])->toBe('name');
    expect($props['defaultDirection'])->toBe('asc');
    expect($props['visible'])->toBe(true);
});

test('uses name as column when column not provided', function () {
    $sort = Sort::make('created_at');

    expect($sort->getColumn())->toBe('created_at');
});

test('can chain multiple methods', function () {
    $sort = Sort::make('name')
        ->label('User Name')
        ->column('users.name')
        ->defaultDirection('desc')
        ->visible(true);

    expect($sort->getName())->toBe('name');
    expect($sort->getLabel())->toBe('User Name');
    expect($sort->getColumn())->toBe('users.name');
});

test('supports both asc and desc directions', function () {
    $sortAsc = Sort::make('name')->defaultDirection('asc');
    $sortDesc = Sort::make('name')->defaultDirection('desc');

    expect($sortAsc->toInertiaProps()['defaultDirection'])->toBe('asc');
    expect($sortDesc->toInertiaProps()['defaultDirection'])->toBe('desc');
});

test('can make multiple sorts with different configurations', function () {
    $sorts = [
        Sort::make('name')->label('Name')->defaultDirection('asc'),
        Sort::make('email')->label('Email')->defaultDirection('asc'),
        Sort::make('created_at')->label('Created')->defaultDirection('desc'),
    ];

    expect($sorts)->toHaveCount(3);
    expect($sorts[0]->getName())->toBe('name');
    expect($sorts[1]->getName())->toBe('email');
    expect($sorts[2]->getName())->toBe('created_at');
    expect($sorts[2]->toInertiaProps()['defaultDirection'])->toBe('desc');
});
