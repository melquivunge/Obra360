<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->index(['client_id', 'updated_at'], 'projects_client_updated_idx');
            $table->index(['manager_id', 'updated_at'], 'projects_manager_updated_idx');
            $table->index(['client_id', 'status'], 'projects_client_status_idx');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->index(['project_id', 'created_at'], 'messages_project_created_idx');
        });

        Schema::table('project_updates', function (Blueprint $table) {
            $table->index(['project_id', 'happened_at'], 'updates_project_happened_idx');
            $table->index(['created_at'], 'updates_created_idx');
        });

        Schema::table('photos', function (Blueprint $table) {
            $table->index(['project_id', 'media_type', 'created_at'], 'photos_project_media_created_idx');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->index(['project_id', 'spent_at'], 'expenses_project_spent_idx');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index(['project_id', 'status', 'due_date'], 'payments_project_status_due_idx');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_project_status_due_idx');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex('expenses_project_spent_idx');
        });

        Schema::table('photos', function (Blueprint $table) {
            $table->dropIndex('photos_project_media_created_idx');
        });

        Schema::table('project_updates', function (Blueprint $table) {
            $table->dropIndex('updates_project_happened_idx');
            $table->dropIndex('updates_created_idx');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('messages_project_created_idx');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex('projects_client_updated_idx');
            $table->dropIndex('projects_manager_updated_idx');
            $table->dropIndex('projects_client_status_idx');
        });
    }
};
