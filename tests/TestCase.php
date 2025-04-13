<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure we're using SQLite for testing
        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.foreign_key_constraints' => true]);
        
        // Begin transaction
        DB::beginTransaction();
    }

    protected function dropAllTables(): void
    {
        // Disable foreign key checks
        DB::statement('PRAGMA foreign_keys = OFF');
        
        // Get all table names
        $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name != 'sqlite_sequence'");
        
        // Drop each table
        foreach ($tables as $table) {
            Schema::drop($table->name);
        }
        
        // Re-enable foreign key checks
        DB::statement('PRAGMA foreign_keys = ON');
    }

    protected function tearDown(): void
    {
        // Rollback transaction
        DB::rollBack();
        
        parent::tearDown();
    }
}
