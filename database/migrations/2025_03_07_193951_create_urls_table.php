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
        Schema::create('urls', function (Blueprint $table) {
            $table->id();
            $table->string('alias', 10)->unique();
            $table->text('original_url');
            $table->string('status')->default('active');
            $table->json('redirect_logs')->nullable();
            $table->timestamps();

            $table->index('alias');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('urls');
    }
};
