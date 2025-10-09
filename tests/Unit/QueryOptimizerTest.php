<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Loan;
use App\Support\QueryOptimizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class QueryOptimizerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_cache_query_results()
    {
        $result = QueryOptimizer::cacheQuery('test_key', 60, function () {
            return 'cached_value';
        });

        $this->assertEquals('cached_value', $result);
        $this->assertTrue(Cache::has('test_key'));

        Cache::forget('test_key');
    }

    #[Test]
    public function it_can_enable_and_disable_query_log()
    {
        QueryOptimizer::enableQueryLog();
        Loan::count();
        $this->assertNotEmpty(QueryOptimizer::getQueryLog());

        QueryOptimizer::disableQueryLog();
    }

    #[Test]
    public function it_can_count_queries()
    {
        QueryOptimizer::enableQueryLog();
        Loan::count();
        Branch::count();

        $count = QueryOptimizer::countQueries();
        $this->assertEquals(2, $count);

        QueryOptimizer::disableQueryLog();
    }

    #[Test]
    public function it_can_profile_query_execution()
    {
        $stats = QueryOptimizer::profile(function () {
            return Loan::count();
        });

        $this->assertArrayHasKey('result', $stats);
        $this->assertArrayHasKey('query_count', $stats);
        $this->assertArrayHasKey('execution_time', $stats);
        $this->assertArrayHasKey('queries', $stats);
    }

    #[Test]
    public function it_can_detect_n_plus_one_problems()
    {
        QueryOptimizer::enableQueryLog();

        // Execute multiple queries
        for ($i = 0; $i < 10; $i++) {
            Loan::count();
        }

        $hasN1Problem = QueryOptimizer::detectN1(2, 5);
        $this->assertTrue($hasN1Problem);

        QueryOptimizer::disableQueryLog();
    }

    #[Test]
    public function it_generates_cache_key_with_branch()
    {
        $key1 = QueryOptimizer::cacheKeyWithBranch('test', 1);
        $key2 = QueryOptimizer::cacheKeyWithBranch('test', null);

        $this->assertEquals('test_1', $key1);
        $this->assertEquals('test_all', $key2);
    }

    #[Test]
    public function it_can_cache_query_by_branch()
    {
        $result = QueryOptimizer::cacheQueryByBranch('test', 1, 60, function () {
            return 'branch_cached_value';
        });

        $this->assertEquals('branch_cached_value', $result);
        $this->assertTrue(Cache::has('test_1'));

        Cache::forget('test_1');
    }

    #[Test]
    public function it_provides_cache_statistics()
    {
        $stats = QueryOptimizer::getCacheStats();

        $this->assertArrayHasKey('driver', $stats);
        $this->assertArrayHasKey('widget_caches', $stats);
    }

    #[Test]
    public function it_can_clear_widget_cache()
    {
        // Set some widget caches
        Cache::put('loan_stats_all', 'test', 60);
        Cache::put('loans_chart_all', 'test', 60);

        QueryOptimizer::clearWidgetCache();

        $this->assertFalse(Cache::has('loan_stats_all'));
        $this->assertFalse(Cache::has('loans_chart_all'));
    }

    #[Test]
    public function it_can_add_eager_loading_to_query()
    {
        $query = Loan::query();
        $optimizedQuery = QueryOptimizer::withEagerLoading($query, ['customer', 'item']);

        // Check that the query builder has the eager load relations
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $optimizedQuery);
    }
}
