<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contacts_custom_field', function (Blueprint $table) {

            $table->unsignedBigInteger('field_id')->after('contact_id');

            $table->dropColumn('name');
            $table->dropColumn('tag');
            $table->dropColumn('type');
            $table->dropColumn('required');

            $table->foreign('field_id')->references('id')->on('contact_group_fields')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts_custom_field', function (Blueprint $table) {
            $table->string('name');
            $table->string('tag');
            $table->string('type')->default('text');
            $table->boolean('required')->default(false);

            $table->dropForeign(['field_id']);
            $table->dropColumn('field_id');

        });
    }
};
