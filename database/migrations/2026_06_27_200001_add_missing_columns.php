<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->boolean('show_financials')->default(true)->after('progress');
            $table->string('cover_image_path')->nullable()->after('show_financials');
            $table->date('completed_at')->nullable()->after('expected_end_date');
        });

        Schema::table('photos', function (Blueprint $table) {
            $table->string('media_type')->default('image')->after('path');
            $table->string('external_url')->nullable()->after('media_type');
            $table->string('caption')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['show_financials', 'cover_image_path', 'completed_at']);
        });
        Schema::table('photos', function (Blueprint $table) {
            $table->dropColumn(['media_type', 'external_url', 'caption']);
        });
    }
};
