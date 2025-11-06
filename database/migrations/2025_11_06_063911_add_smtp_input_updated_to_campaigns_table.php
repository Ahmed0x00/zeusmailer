<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('campaigns', function (Blueprint $table) {
        $table->text('smtp_input_updated')->nullable()->after('smtp_input');
    });
}

public function down()
{
    Schema::table('campaigns', function (Blueprint $table) {
        $table->dropColumn('smtp_input_updated');
    });
}
};
