<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up()
{
    Schema::table('campaigns', function (Blueprint $table) {
        $table->json('failed_emails')->nullable()->after('sent_emails');
    });
}

public function down()
{
    Schema::table('campaigns', function (Blueprint $table) {
        $table->dropColumn('failed_emails');
    });
}
};
