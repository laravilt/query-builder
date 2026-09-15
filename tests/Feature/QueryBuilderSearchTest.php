<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravilt\QueryBuilder\QueryBuilder;

class SearchTestAuthor extends Model
{
    protected $table = 'search_authors';

    protected $guarded = [];

    public $timestamps = false;
}

class SearchTestPost extends Model
{
    protected $table = 'search_posts';

    protected $guarded = [];

    public $timestamps = false;

    public function author(): BelongsTo
    {
        return $this->belongsTo(SearchTestAuthor::class, 'author_id');
    }
}

beforeEach(function () {
    Schema::create('search_authors', function (Blueprint $table) {
        $table->id();
        $table->string('name');
    });

    Schema::create('search_posts', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->string('body');
        $table->foreignId('author_id')->nullable();
    });

    $john = SearchTestAuthor::create(['name' => 'John Doe']);
    $jane = SearchTestAuthor::create(['name' => 'Jane Smith']);

    foreach ([
        ['title' => 'Laravel Tips', 'body' => 'Eloquent tricks', 'author_id' => $john->id],
        ['title' => 'Vue Guide', 'body' => 'Composition API with Laravel', 'author_id' => $jane->id],
        ['title' => '100% Coverage', 'body' => 'Testing', 'author_id' => $jane->id],
        ['title' => '100 Coverage', 'body' => 'Testing', 'author_id' => null],
        ['title' => 'snake_case names', 'body' => 'Style', 'author_id' => null],
        ['title' => 'snakeXcase names', 'body' => 'Style', 'author_id' => null],
        ['title' => 'C:\\path\\file', 'body' => 'Windows', 'author_id' => null],
        ['title' => 'C:path', 'body' => 'Windows', 'author_id' => null],
    ] as $post) {
        SearchTestPost::create($post);
    }
});

afterEach(function () {
    Schema::dropIfExists('search_posts');
    Schema::dropIfExists('search_authors');
});

function searchTitles(?string $term, array $columns = ['title']): array
{
    return (new QueryBuilder)
        ->searchable($columns)
        ->search($term)
        ->apply(SearchTestPost::query())
        ->orderBy('id')
        ->pluck('title')
        ->all();
}

test('search matches searchable columns with a contains LIKE', function () {
    expect(searchTitles('laravel'))->toBe(['Laravel Tips']);
});

test('blank or null search is a no-op', function (?string $term) {
    expect(searchTitles($term))->toHaveCount(8);
})->with([null, '', '   ']);

test('search without searchable columns is a no-op', function () {
    $count = (new QueryBuilder)->search('Laravel')->apply(SearchTestPost::query())->count();

    expect($count)->toBe(8);
});

test('search ORs across multiple columns', function () {
    expect(searchTitles('Laravel', ['title', 'body']))->toBe(['Laravel Tips', 'Vue Guide']);
});

test('search group is nested so it does not break other constraints', function () {
    $titles = (new QueryBuilder)
        ->searchable(['title', 'body'])
        ->search('Laravel')
        ->apply(SearchTestPost::query()->where('author_id', 2))
        ->pluck('title')
        ->all();

    expect($titles)->toBe(['Vue Guide']);
});

test('search escapes percent sign', function () {
    expect(searchTitles('100%'))->toBe(['100% Coverage']);
});

test('search escapes underscore', function () {
    expect(searchTitles('snake_case'))->toBe(['snake_case names']);
});

test('search escapes backslash', function () {
    expect(searchTitles('C:\\path'))->toBe(['C:\\path\\file']);
});

test('search supports relation columns via dot notation', function () {
    expect(searchTitles('Jane', ['title', 'author.name']))->toBe(['Vue Guide', '100% Coverage']);
});
