<?php namespace Pensoft\InternalDocuments\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class AddUserIdToSystemFiles extends Migration
{
	public function up()
	{
		if (!Schema::hasColumn('system_files', 'user_id')) {
			Schema::table('system_files', function($table)
			{
				$table->integer('user_id')->nullable();
			});
		}
	}

	public function down()
	{
		if (Schema::hasColumn('system_files', 'user_id')) {
			Schema::table('system_files', function($table)
			{
				$table->dropColumn('user_id');
			});
		}
	}
}
