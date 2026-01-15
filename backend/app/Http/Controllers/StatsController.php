<?php
// app/Http/Controllers/StatsController.php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    public function database()
    {
        // Database stats
        $dbSize = DB::select("
            SELECT 
                pg_size_pretty(pg_database_size(current_database())) as size,
                (SELECT count(*) FROM pg_stat_activity WHERE state = 'active') as active_connections,
                (SELECT count(*) FROM pg_stat_activity) as total_connections
        ")[0];
        
        // Table sizes
        $tableSizes = DB::select("
            SELECT 
                schemaname,
                tablename,
                pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) AS size,
                pg_total_relation_size(schemaname||'.'||tablename) AS bytes
            FROM pg_tables
            WHERE schemaname = 'public'
            ORDER BY bytes DESC
            LIMIT 10
        ");
        
        // Row counts
        $rowCounts = DB::select("
            SELECT 
                schemaname,
                relname AS tablename,
                n_live_tup AS row_count
            FROM pg_stat_user_tables
            WHERE schemaname = 'public'
            ORDER BY n_live_tup DESC
        ");

        
        return response()->json([
            'database' => $dbSize,
            'table_sizes' => $tableSizes,
            'row_counts' => $rowCounts,
            'timestamp' => now()
        ]);
    }
    
    public function slowQueries()
    {
        try {
            // Check if pg_stat_statements exists
            $exists = DB::selectOne("
                SELECT 1
                FROM pg_extension
                WHERE extname = 'pg_stat_statements'
            ");

            if (!$exists) {
                return response()->json([
                    'slow_queries' => [],
                    'warning' => 'pg_stat_statements extension is not enabled',
                    'timestamp' => now()
                ]);
            }

            $slowQueries = DB::select("
                SELECT 
                    substring(query, 1, 100) as query_preview,
                    calls,
                    total_exec_time::numeric(10,2) as total_time_ms,
                    mean_exec_time::numeric(10,2) as avg_time_ms,
                    max_exec_time::numeric(10,2) as max_time_ms,
                    rows
                FROM pg_stat_statements
                WHERE query NOT LIKE '%pg_stat_statements%'
                ORDER BY mean_exec_time DESC
                LIMIT 20
            ");

            return response()->json([
                'slow_queries' => $slowQueries,
                'timestamp' => now()
            ]);

        } catch (\Throwable $e) {
            // Absolute safety net
            return response()->json([
                'slow_queries' => [],
                'error' => 'pg_stat_statements not available',
                'timestamp' => now()
            ]);
        }
    }

    public function connections()
    {
        // Active connections
        $connections = DB::select("
            SELECT 
                pid,
                usename,
                application_name,
                client_addr,
                state,
                query_start,
                state_change,
                substring(query, 1, 100) as current_query
            FROM pg_stat_activity
            WHERE datname = current_database()
            AND pid != pg_backend_pid()
            ORDER BY query_start DESC
        ");
        
        // Connection stats by state
        $connectionStats = DB::select("
            SELECT 
                state,
                count(*) as count
            FROM pg_stat_activity
            WHERE datname = current_database()
            GROUP BY state
            ORDER BY count DESC
        ");
        
        return response()->json([
            'connections' => $connections,
            'stats' => $connectionStats,
            'timestamp' => now()
        ]);
    }
}