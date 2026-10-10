<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use Amarenkov\MutableContentTasks\Models\TimeEntry;

return new class extends Migration
{
    public function up(): void
    {
        Schema::createWithLog('tasks.time_entries', function (Blueprint $table) {
            $table->fieldsBase();
            $table->fieldsUpdatedAt();
            $table->softDeletes();

            $table->fieldExtract(TimeEntry::FIELD_ACTOR_ID)->type('bigint');
            $table->fieldExtract(TimeEntry::FIELD_POINT_ID)->type('bigint');
            $table->fieldExtract(TimeEntry::FIELD_FROM)->type('varchar(20)');
            $table->fieldExtract(TimeEntry::FIELD_TO)->type('varchar(20)');

            $table->index([TimeEntry::FIELD_ACTOR_ID, TimeEntry::FIELD_FROM]);
            $table->index(TimeEntry::FIELD_POINT_ID);
        });
    }
};
