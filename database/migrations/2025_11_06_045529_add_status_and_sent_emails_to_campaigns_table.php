<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('campaigns', function (Blueprint $table) {
        $table->enum('status', ['running', 'paused', 'completed'])
              ->default('running')
              ->after('id');
        $table->json('sent_emails')->nullable()->after('failed');
    });
}

public function down()
{
    Schema::table('campaigns', function (Blueprint $table) {
        $table->dropColumn(['status', 'sent_emails']);
    });
}
};
