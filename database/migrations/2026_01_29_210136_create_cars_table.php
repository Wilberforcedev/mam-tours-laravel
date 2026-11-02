<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCarsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cars', function (Blueprint $table) {
            $table->id();
            $table->string('carPicture', 100)->nullable();
            $table->string('brand', 100);
            $table->string('model', 100);
            $table->string('numberPlate', 50)->unique();
            $table->integer('dailyRate');
            $table->integer('seats');
            $table->boolean('isAvailable')->default(true);
            $table->string('category', 50)->nullable();
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
        Schema::dropIfExists('cars');
    }
}
