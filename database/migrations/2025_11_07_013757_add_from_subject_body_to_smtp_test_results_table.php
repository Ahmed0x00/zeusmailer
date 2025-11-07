<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('smtp_test_results', function (Blueprint $table) {
            $table->string('from_name')->nullable()->after('id');
            $table->string('subject')->nullable()->after('from_name');
            $table->longText('html_body')->nullable()->after('subject');
        });
    }

    public function down(): void
    {
        Schema::table('smtp_test_results', function (Blueprint $table) {
            $table->dropColumn(['from_name', 'subject', 'html_body']);
        });
    }
};
