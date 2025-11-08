<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('smtp_batches', function (Blueprint $table) {
            // Add missing columns
            if (!Schema::hasColumn('smtp_batches', 'filename')) {
                $table->string('filename')->nullable()->after('id');
            }
            if (!Schema::hasColumn('smtp_batches', 'status')) {
                $table->enum('status', ['pending', 'running', 'paused', 'finished'])
                      ->default('pending')
                      ->after('success');
            }
        });
    }

    public function down()
    {
        Schema::table('smtp_batches', function (Blueprint $table) {
            $table->dropColumn(['filename', 'status']);
        });
    }
};
