<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('client')->after('email');
            $table->string('phone')->nullable()->after('role');
            $table->boolean('active')->default(true)->after('phone');
            $table->foreignId('client_id')->nullable()->after('active')->constrained('clients')->nullOnDelete();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropColumn(['role', 'phone', 'active', 'client_id']);
            $table->dropSoftDeletes();
        });
    }
};
