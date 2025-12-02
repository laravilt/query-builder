<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravilt\QueryBuilder\Filters\BooleanFilter;
use Laravilt\QueryBuilder\Filters\DateFilter;
use Laravilt\QueryBuilder\Filters\SelectFilter;
use Laravilt\QueryBuilder\Filters\TextFilter;
use Laravilt\QueryBuilder\QueryBuilder;
use Laravilt\QueryBuilder\Sort;

beforeEach(function () {
    // Create a comprehensive test table
    Schema::create('test_articles', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->string('author');
        $table->string('status');
        $table->string('category');
        $table->boolean('is_featured')->default(false);
        $table->boolean('is_published')->default(true);
        $table->integer('views')->default(0);
        $table->timestamp('published_at')->nullable();
        $table->timestamps();
    });

    $this->model = new class extends Model
    {
        protected $table = 'test_articles';

        protected $guarded = [];

        protected $casts = [
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    };

    // Insert comprehensive test data
    $articles = [
        ['title' => 'Laravel Best Practices', 'author' => 'John Doe', 'status' => 'published', 'category' => 'tutorial', 'is_featured' => true, 'is_published' => true, 'views' => 1500, 'published_at' => '2024-01-15'],
        ['title' => 'Vue 3 Guide', 'author' => 'Jane Smith', 'status' => 'published', 'category' => 'tutorial', 'is_featured' => false, 'is_published' => true, 'views' => 800, 'published_at' => '2024-01-20'],
        ['title' => 'PHP 8 Features', 'author' => 'John Doe', 'status' => 'draft', 'category' => 'news', 'is_featured' => false, 'is_published' => false, 'views' => 100, 'published_at' => null],
        ['title' => 'Inertia.js Introduction', 'author' => 'Alice Johnson', 'status' => 'published', 'category' => 'tutorial', 'is_featured' => true, 'is_published' => true, 'views' => 2000, 'published_at' => '2024-02-01'],
        ['title' => 'Database Optimization', 'author' => 'Bob Wilson', 'status' => 'published', 'category' => 'advanced', 'is_featured' => false, 'is_published' => true, 'views' => 1200, 'published_at' => '2024-02-10'],
        ['title' => 'Testing with Pest', 'author' => 'Jane Smith', 'status' => 'draft', 'category' => 'tutorial', 'is_featured' => false, 'is_published' => false, 'views' => 50, 'published_at' => null],
        ['title' => 'API Design Patterns', 'author' => 'John Doe', 'status' => 'published', 'category' => 'advanced', 'is_featured' => true, 'is_published' => true, 'views' => 1800, 'published_at' => '2024-03-01'],
        ['title' => 'Tailwind CSS Tips', 'author' => 'Alice Johnson', 'status' => 'published', 'category' => 'tutorial', 'is_featured' => false, 'is_published' => true, 'views' => 900, 'published_at' => '2024-03-15'],
    ];

    foreach ($articles as $article) {
        $this->model::create($article);
    }
});

afterEach(function () {
    Schema::dropIfExists('test_articles');
});

test('can build complete query with all filter types', function () {
    $queryBuilder = new QueryBuilder;

    $queryBuilder
        ->filters([
            SelectFilter::make('status')->options(['published' => 'Published', 'draft' => 'Draft']),
            SelectFilter::make('category')->options(['tutorial' => 'Tutorial', 'news' => 'News', 'advanced' => 'Advanced']),
            BooleanFilter::make('is_featured'),
            BooleanFilter::make('is_published'),
            TextFilter::make('author')->contains(),
            DateFilter::make('published_at')->between(),
        ])
        ->sorts([
            Sort::make('title')->label('Title'),
            Sort::make('published_at')->label('Published Date')->defaultDirection('desc'),
            Sort::make('views')->label('Views')->defaultDirection('desc'),
        ])
        ->applyFilters([
            'status' => 'published',
            'is_featured' => true,
        ])
        ->sortBy('views', 'desc')
        ->perPage(10);

    $query = $this->model::query();
    $queryBuilder->apply($query);

    $results = $query->get();

    expect($results)->toHaveCount(3);
    expect($results->first()->title)->toBe('Inertia.js Introduction'); // Highest views
    expect($results->every(fn ($article) => $article->status === 'published' && $article->is_featured === true))->toBeTrue();
});

test('can filter by multiple select values', function () {
    $queryBuilder = new QueryBuilder;

    $queryBuilder
        ->filters([
            SelectFilter::make('category')->multiple(),
        ])
        ->applyFilters([
            'category' => ['tutorial', 'advanced'],
        ]);

    $query = $this->model::query();
    $queryBuilder->apply($query);

    $results = $query->get();

    expect($results)->toHaveCount(7);
    expect($results->whereIn('category', ['tutorial', 'advanced'])->count())->toBe(7);
});

test('can filter by text with contains operator', function () {
    $queryBuilder = new QueryBuilder;

    $queryBuilder
        ->filters([
            TextFilter::make('title')->contains(),
        ])
        ->applyFilters([
            'title' => 'Laravel',
        ]);

    $query = $this->model::query();
    $queryBuilder->apply($query);

    $results = $query->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->title)->toBe('Laravel Best Practices');
});

test('can filter by date range', function () {
    $queryBuilder = new QueryBuilder;

    $queryBuilder
        ->filters([
            DateFilter::make('published_at')->between(),
        ])
        ->applyFilters([
            'published_at' => ['2024-02-01', '2024-03-31'],
        ]);

    $query = $this->model::query();
    $queryBuilder->apply($query);

    $results = $query->get();

    expect($results)->toHaveCount(4);
    expect($results->pluck('title')->toArray())->toBe([
        'Inertia.js Introduction',
        'Database Optimization',
        'API Design Patterns',
        'Tailwind CSS Tips',
    ]);
});

test('can combine multiple filters and sorting', function () {
    $queryBuilder = new QueryBuilder;

    $queryBuilder
        ->filters([
            SelectFilter::make('status'),
            SelectFilter::make('category'),
            BooleanFilter::make('is_published'),
        ])
        ->applyFilters([
            'status' => 'published',
            'category' => 'tutorial',
            'is_published' => true,
        ])
        ->sortBy('views', 'desc');

    $query = $this->model::query();
    $queryBuilder->apply($query);

    $results = $query->get();

    expect($results)->toHaveCount(4);
    expect($results->first()->title)->toBe('Inertia.js Introduction'); // Highest views in tutorial
    expect($results->last()->title)->toBe('Vue 3 Guide'); // Lowest views in tutorial
});

test('can filter by author using text filter', function () {
    $queryBuilder = new QueryBuilder;

    $queryBuilder
        ->filters([
            TextFilter::make('author')->exact(),
        ])
        ->applyFilters([
            'author' => 'John Doe',
        ]);

    $query = $this->model::query();
    $queryBuilder->apply($query);

    $results = $query->get();

    expect($results)->toHaveCount(3);
    expect($results->every(fn ($article) => $article->author === 'John Doe'))->toBeTrue();
});

test('serializes complete query builder to inertia props', function () {
    $queryBuilder = new QueryBuilder;

    $queryBuilder
        ->filters([
            SelectFilter::make('status')->options(['published' => 'Published', 'draft' => 'Draft']),
            BooleanFilter::make('is_featured'),
            TextFilter::make('title')->contains(),
        ])
        ->sorts([
            Sort::make('title')->label('Title'),
            Sort::make('views')->label('Views')->defaultDirection('desc'),
        ])
        ->applyFilters(['status' => 'published'])
        ->search('Laravel')
        ->sortBy('views', 'desc')
        ->perPage(20);

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

    expect($props['filters'])->toHaveCount(3);
    expect($props['sorts'])->toHaveCount(2);
    expect($props['filterValues'])->toBe(['status' => 'published']);
    expect($props['search'])->toBe('Laravel');
    expect($props['sortBy'])->toBe('views');
    expect($props['sortDirection'])->toBe('desc');
    expect($props['perPage'])->toBe(20);
});

test('can use custom query closures for complex filtering', function () {
    $queryBuilder = new QueryBuilder;

    $queryBuilder
        ->filters([
            SelectFilter::make('status')
                ->query(function ($query, $value) {
                    if ($value === 'published') {
                        $query->where('status', 'published')
                            ->where('is_published', true)
                            ->whereNotNull('published_at');
                    }
                }),
        ])
        ->applyFilters(['status' => 'published']);

    $query = $this->model::query();
    $queryBuilder->apply($query);

    $results = $query->get();

    expect($results)->toHaveCount(6);
    expect($results->every(fn ($article) => $article->status === 'published' && $article->is_published === true))->toBeTrue();
});

test('handles empty filter values gracefully', function () {
    $queryBuilder = new QueryBuilder;

    $queryBuilder
        ->filters([
            SelectFilter::make('status'),
            TextFilter::make('title'),
            BooleanFilter::make('is_featured'),
        ])
        ->applyFilters([
            'status' => null,
            'title' => '',
            'is_featured' => null,
        ]);

    $query = $this->model::query();
    $queryBuilder->apply($query);

    $results = $query->get();

    expect($results)->toHaveCount(8); // All records
});

test('can sort by multiple columns in sequence', function () {
    $queryBuilder = new QueryBuilder;

    $queryBuilder->sortBy('author', 'asc');

    $query = $this->model::query();
    $queryBuilder->apply($query);

    $results = $query->get();

    expect($results->first()->author)->toBe('Alice Johnson');
    expect($results->last()->author)->toBe('John Doe');
});

test('supports complex real-world scenario', function () {
    // Scenario: Get featured, published articles from tutorials or advanced categories,
    // published in February 2024, sorted by views descending
    $queryBuilder = new QueryBuilder;

    $queryBuilder
        ->filters([
            SelectFilter::make('category')->multiple(),
            BooleanFilter::make('is_featured'),
            BooleanFilter::make('is_published'),
            DateFilter::make('published_at')->between(),
        ])
        ->applyFilters([
            'category' => ['tutorial', 'advanced'],
            'is_featured' => true,
            'is_published' => true,
            'published_at' => ['2024-02-01', '2024-02-28'],
        ])
        ->sortBy('views', 'desc');

    $query = $this->model::query();
    $queryBuilder->apply($query);

    $results = $query->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->title)->toBe('Inertia.js Introduction');
    expect($results->first()->is_featured)->toBeTrue();
    expect($results->first()->views)->toBe(2000);
});
