<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('campaigns', function (Blueprint $table) {
        $table->string('from_name')->default('ZeusMailer')->after('subject');
    });
}

public function down()
{
    Schema::table('campaigns', function (Blueprint $table) {
        $table->dropColumn('from_name');
    });
}
};
