<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravilt\QueryBuilder\Filters\DateFilter;
use Laravilt\QueryBuilder\Filters\SelectFilter;
use Laravilt\QueryBuilder\QueryBuilder;
use Laravilt\QueryBuilder\Sort;

beforeEach(function () {
    Schema::create('qb_apply_items', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->string('category');
        $table->timestamp('published_at')->nullable();
    });

    $this->model = new class extends Model
    {
        protected $table = 'qb_apply_items';

        protected $guarded = [];

        public $timestamps = false;
    };

    $this->model::create(['title' => 'B', 'category' => 'news', 'published_at' => '2024-01-01']);
    $this->model::create(['title' => 'A', 'category' => 'tips', 'published_at' => '2024-02-01']);
    $this->model::create(['title' => 'C', 'category' => 'news', 'published_at' => '2024-03-01']);
});

afterEach(function () {
    Schema::dropIfExists('qb_apply_items');
});

test('sorts by the registered sort column rather than its name', function () {
    $query = (new QueryBuilder)
        ->sorts([Sort::make('headline', 'title')])
        ->sortBy('headline', 'desc')
        ->apply($this->model::query());

    expect($query->pluck('title')->all())->toBe(['C', 'B', 'A']);
});

test('ignores a sort that is not registered', function () {
    $query = (new QueryBuilder)
        ->sorts([Sort::make('title')])
        ->sortBy('category', 'desc')
        ->apply($this->model::query());

    expect($query->toBase()->orders)->toBeNull();
});

test('accepts an upper-case sort direction', function () {
    $builder = (new QueryBuilder)->sortBy('title', 'DESC');

    expect($builder->toInertiaProps()['sortDirection'])->toBe('desc')
        ->and($builder->apply($this->model::query())->pluck('title')->all())->toBe(['C', 'B', 'A']);
});

test('ignores a cleared multi-select filter', function () {
    $query = (new QueryBuilder)
        ->filters([SelectFilter::make('category')->multiple()])
        ->applyFilters(['category' => []])
        ->apply($this->model::query());

    expect($query->count())->toBe(3);
});

test('ignores an incomplete between date range instead of building invalid sql', function () {
    $query = $this->model::query();

    DateFilter::make('published_at')->between()->apply($query, '2024-01-15');

    expect($query->count())->toBe(3);
});
