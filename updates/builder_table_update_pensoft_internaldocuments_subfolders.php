<?php namespace Pensoft\InternalDocuments\Updates;

use Illuminate\Database\Schema\Blueprint;
use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePensoftInternaldocumentsSubfolders extends Migration
{
    public function up(): void
    {
        Schema::table('pensoft_internaldocuments_subfolders', function(Blueprint $table)
        {
            $table->timestamp('deleted_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pensoft_internaldocuments_subfolders', function(Blueprint $table)
        {
            $table->dropColumn('deleted_at');
        });
    }
}