<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('verify_results', function (Blueprint $table) {
            $table->id();
            $table->uuid('batch_id')->index();
            $table->string('email');
            $table->enum('status', ['valid', 'invalid', 'error', 'unknown'])->default('unknown');
            $table->string('reason')->nullable();
            $table->string('mx')->nullable();
            $table->text('response')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();

            $table->foreign('batch_id')->references('id')->on('verify_batches')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verify_results');
    }
};
