<?php namespace Pensoft\InternalDocuments\Updates;

use Illuminate\Database\Schema\Blueprint;
use Schema;
use October\Rain\Database\Updates\Migration;

class AddDeletedAtToSystemFiles extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('system_files', 'deleted_at')) {
            Schema::table('system_files', function(Blueprint $table)
            {
                $table->timestamp('deleted_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('system_files', 'deleted_at')) {
            Schema::table('system_files', function(Blueprint $table)
            {
                $table->dropColumn('deleted_at');
            });
        }
    }
}