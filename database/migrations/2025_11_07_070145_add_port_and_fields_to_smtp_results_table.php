<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPortAndFieldsToSmtpResultsTable extends Migration
{
    public function up()
    {
        Schema::table('smtp_results', function (Blueprint $table) {
            if (!Schema::hasColumn('smtp_results', 'port')) {
                $table->integer('port')->nullable()->after('smtp_host');
            }
            if (!Schema::hasColumn('smtp_results', 'provider')) {
                $table->string('provider')->nullable()->after('port');
            }
            if (!Schema::hasColumn('smtp_results', 'status')) {
                $table->string('status')->default('pending')->after('provider');
            } else {
                $table->string('status')->default('pending')->change();
            }
            if (!Schema::hasColumn('smtp_results', 'response_time')) {
                $table->float('response_time')->nullable()->after('status');
            }
            // password/email probably exist already
        });
    }

    public function down()
    {
        Schema::table('smtp_results', function (Blueprint $table) {
            if (Schema::hasColumn('smtp_results', 'port')) $table->dropColumn('port');
            if (Schema::hasColumn('smtp_results', 'provider')) $table->dropColumn('provider');
            // don't drop status/response_time if used elsewhere — adjust if needed
        });
    }
}
