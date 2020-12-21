<?php namespace Pensoft\InternalDocuments\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreatePensoftInternaldocumentsFolders extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('pensoft_internaldocuments_folders')) {
            Schema::create('pensoft_internaldocuments_folders', function($table)
            {
                $table->engine = 'InnoDB';
                $table->increments('id')->unsigned();
                $table->string('slug')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->string('name');
                $table->integer('sort_order')->default(1);
            });
        }
    }
    
    public function down()
    {
        if (Schema::hasTable('pensoft_internaldocuments_folders')) {
            Schema::dropIfExists('pensoft_internaldocuments_folders');
        }
    }
}
