<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatingLojistasAttributes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->char('CPF', 11)->unique();
            $table->char('CNPJ', 15)->unique()->nullable();
            $table->boolean('isPJ')->default(false);
            $table->float('balance')->default(0.0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['CPF', 'CNPJ', 'isPJ', 'balance']);
        });

    }
}
