<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('campaigns', function (Blueprint $table) {
        $table->dropColumn('debug_log');
    });
}

public function down()
{
    Schema::table('campaigns', function (Blueprint $table) {
        $table->text('debug_log')->nullable()->after('log');
    });
}
};
