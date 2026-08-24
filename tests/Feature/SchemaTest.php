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

    /**
     * `photos.external_url` foi criada em 2026_06_27_200001 e nunca foi
     * escrita nem lida por nada. O caso do arquivo que mora fora do servidor
     * vive em `plan_versions.external_url` (PLAN-08), esse sim implementado de
     * ponta a ponta.
     */
    public function test_photos_table_has_no_unused_external_url_column(): void
    {
        $this->assertFalse(Schema::hasColumn('photos', 'external_url'));
        $this->assertTrue(Schema::hasColumn('plan_versions', 'external_url'));
    }

    public function test_messages_table_exists_with_body_column(): void
    {
        $this->assertTrue(Schema::hasTable('messages'));
        $this->assertTrue(Schema::hasColumn('messages', 'body'));
    }
}
