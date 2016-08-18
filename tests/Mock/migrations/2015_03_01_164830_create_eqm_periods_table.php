<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEqmPeriodsTable extends Migration {

	public function up()
	{
		Schema::create('periods', function(Blueprint $table) {
			$table->increments('id');
			$table->timestamps();
			$table->string('name')->unique();
		});
	}

	public function down()
	{
		Schema::drop('periods');
	}
}