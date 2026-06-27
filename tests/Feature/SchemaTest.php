<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_application_tables_exist(): void
    {
        $this->assertTrue(Schema::hasTable('users'));
        $this->assertTrue(Schema::hasTable('clients'));
        $this->assertTrue(Schema::hasTable('projects'));
        $this->assertTrue(Schema::hasTable('project_phases'));
        $this->assertTrue(Schema::hasTable('photos'));
        $this->assertTrue(Schema::hasTable('expenses'));
        $this->assertTrue(Schema::hasTable('documents'));
        $this->assertTrue(Schema::hasTable('project_updates'));
        $this->assertTrue(Schema::hasTable('messages'));
        $this->assertTrue(Schema::hasTable('settings'));
    }

    public function test_users_table_has_role_and_relations_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('users', [
            'role',
            'phone',
            'active',
            'client_id',
            'deleted_at',
        ]));
    }

    public function test_projects_table_has_budget_column(): void
    {
        $this->assertTrue(Schema::hasColumn('projects', 'budget'));
    }

    public function test_documents_table_has_visibility_column(): void
    {
        $this->assertTrue(Schema::hasColumn('documents', 'visibility'));
    }

    public function test_messages_table_exists_with_body_column(): void
    {
        $this->assertTrue(Schema::hasTable('messages'));
        $this->assertTrue(Schema::hasColumn('messages', 'body'));
    }
}
