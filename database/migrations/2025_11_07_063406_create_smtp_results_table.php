<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::create('smtp_results', function (Blueprint $table) {
        $table->id();
        $table->string('email');
        $table->string('password')->nullable();
        $table->string('smtp_host')->nullable();
        $table->string('provider')->nullable();
        $table->string('status')->default('failed');
        $table->float('response_time')->nullable();
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('smtp_results');
    }
};
