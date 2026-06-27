<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('amount', 12, 2);
            $table->enum('type', ['boleto', 'pix'])->default('pix');
            $table->enum('status', ['pending', 'paid', 'cancelled'])->default('pending');
            $table->date('due_date')->nullable();
            $table->string('barcode', 60)->nullable();   // linha digitável boleto
            $table->string('pix_key', 100)->nullable();
            $table->text('pix_qrcode')->nullable();       // base64 ou código copia-cola
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
