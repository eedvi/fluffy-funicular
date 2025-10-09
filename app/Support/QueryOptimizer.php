<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QueryOptimizer
{
    /**
     * Cache a query result with automatic key generation
     */
    public static function cacheQuery(string $key, int $ttl, \Closure $callback)
    {
        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Execute a query with eager loading to prevent N+1 problems
     */
    public static function withEagerLoading(Builder $query, array $relations): Builder
    {
        return $query->with($relations);
    }

    /**
     * Enable query logging for performance analysis
     */
    public static function enableQueryLog(): void
    {
        DB::enableQueryLog();
    }

    /**
     * Disable query logging
     */
    public static function disableQueryLog(): void
    {
        DB::disableQueryLog();
    }

    /**
     * Get executed queries
     */
    public static function getQueryLog(): array
    {
        return DB::getQueryLog();
    }

    /**
     * Count queries executed
     */
    public static function countQueries(): int
    {
        return count(DB::getQueryLog());
    }

    /**
     * Log slow queries (> threshold ms)
     */
    public static function logSlowQueries(int $thresholdMs = 100): void
    {
        $queries = DB::getQueryLog();

        foreach ($queries as $query) {
            $time = $query['time'] ?? 0;

            if ($time > $thresholdMs) {
                Log::warning('Slow query detected', [
                    'query' => $query['query'],
                    'bindings' => $query['bindings'],
                    'time' => $time . 'ms',
                ]);
            }
        }
    }

    /**
     * Clear cache for a specific key pattern
     */
    public static function clearCachePattern(string $pattern): void
    {
        // Note: This requires Redis or Memcached driver
        // For file cache driver, you'd need to iterate through cache files
        if (config('cache.default') === 'redis') {
            $redis = Cache::getRedis();
            $keys = $redis->keys($pattern);

            foreach ($keys as $key) {
                Cache::forget($key);
            }
        } else {
            Log::info('Cache clearing by pattern requires Redis/Memcached driver');
        }
    }

    /**
     * Clear all widget caches
     */
    public static function clearWidgetCache(): void
    {
        Cache::forget('loan_stats_all');
        Cache::forget('loans_chart_all');
        Cache::forget('revenue_chart_all');

        // Also clear branch-specific caches if needed
        Log::info('Widget caches cleared');
    }

    /**
     * Execute closure with query logging and return query stats
     */
    public static function profile(\Closure $callback): array
    {
        DB::enableQueryLog();
        $startTime = microtime(true);

        $result = $callback();

        $endTime = microtime(true);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        return [
            'result' => $result,
            'query_count' => count($queries),
            'execution_time' => round(($endTime - $startTime) * 1000, 2) . 'ms',
            'queries' => $queries,
        ];
    }

    /**
     * Detect N+1 query problems by counting queries
     */
    public static function detectN1(int $expectedQueries, int $tolerance = 5): bool
    {
        $actualQueries = self::countQueries();

        if ($actualQueries > ($expectedQueries + $tolerance)) {
            Log::warning('Potential N+1 query problem detected', [
                'expected' => $expectedQueries,
                'actual' => $actualQueries,
                'difference' => $actualQueries - $expectedQueries,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Get cache statistics
     */
    public static function getCacheStats(): array
    {
        $stats = [
            'driver' => config('cache.default'),
            'widget_caches' => [],
        ];

        // Check if widget caches exist
        $widgetKeys = ['loan_stats_all', 'loans_chart_all', 'revenue_chart_all'];

        foreach ($widgetKeys as $key) {
            $stats['widget_caches'][$key] = Cache::has($key);
        }

        return $stats;
    }

    /**
     * Generate cache key with branch scope
     */
    public static function cacheKeyWithBranch(string $baseKey, ?int $branchId): string
    {
        return $baseKey . '_' . ($branchId ?? 'all');
    }

    /**
     * Cache query with automatic branch scoping
     */
    public static function cacheQueryByBranch(
        string $baseKey,
        ?int $branchId,
        int $ttl,
        \Closure $callback
    ) {
        $key = self::cacheKeyWithBranch($baseKey, $branchId);
        return Cache::remember($key, $ttl, $callback);
    }
}
