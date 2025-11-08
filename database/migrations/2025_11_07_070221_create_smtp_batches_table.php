<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSmtpBatchesTable extends Migration
{
    public function up()
    {
        Schema::create('smtp_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('total')->default(0);
            $table->integer('processed')->default(0);
            $table->integer('success')->default(0);
            $table->json('recent_results')->nullable(); // optional: store last N results
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('smtp_batches');
    }
}
