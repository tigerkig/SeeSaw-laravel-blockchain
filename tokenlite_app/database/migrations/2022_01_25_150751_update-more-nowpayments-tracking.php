<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateMoreNowpaymentsTracking extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('nowpayments_tracking'); // reset the tracking table since we're making a lot of changes
        Schema::create('nowpayments_tracking', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tnx_id')->nullable();
            $table->string('type')->nullable();
            $table->text('request')->nullable();
            $table->text('response')->nullable();
            $table->integer('status_code')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('nowpayments_tracking');
    }
}
