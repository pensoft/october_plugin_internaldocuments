<?php namespace Pensoft\InternalDocuments\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class AddDeletedAtToSystemFiles extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('system_files', 'deleted_at')) {
            Schema::table('system_files', function($table)
            {
                $table->timestamp('deleted_at')->nullable();
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('system_files', 'deleted_at')) {
            Schema::table('system_files', function($table)
            {
                $table->dropColumn('deleted_at');
            });
        }
    }
}
