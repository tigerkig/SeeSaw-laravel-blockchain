<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateContribution extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ico_stages', function (Blueprint $table) {
            $table->enum('price_type', ['static', 'dynamic'])->default('static');
            $table->double('liquidity')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ico_stages', function (Blueprint $table) {
            $table->dropColumn('price_type');
            $table->dropColumn('liquidity');
        });
    }
}
