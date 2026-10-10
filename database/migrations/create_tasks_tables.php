<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use Amarenkov\MutableContentTasks\Models\Actor;
use Amarenkov\MutableContentTasks\Models\Participant;
use Amarenkov\MutableContentTasks\Models\Point;

return new class extends Migration
{
    public function up(): void
    {
        Schema::createSchema('tasks');

        Schema::createWithLog('tasks.points', function (Blueprint $table) {
            $table->fieldsBase();
            $table->fieldsUpdatedAt();
            $table->softDeletes();

            $table->fieldExtract(Point::FIELD_TYPE)->type('varchar(255)');
            $table->fieldExtract(Point::FIELD_PARENT_ID)->type('bigint');

            $table->index(Point::FIELD_TYPE);
            $table->index(Point::FIELD_PARENT_ID);
        });

        Schema::createWithLog('tasks.actors', function (Blueprint $table) {
            $table->fieldsBase();
            $table->fieldsUpdatedAt();
            $table->softDeletes();

            $table->fieldExtract(Actor::FIELD_TYPE)->type('varchar(255)');
            $table->fieldExtract(Actor::FIELD_USER_ID)->type('bigint');

            $table->index(Actor::FIELD_TYPE);
            $table->unique(Actor::FIELD_USER_ID);
        });

        Schema::createWithLog('tasks.participants', function (Blueprint $table) {
            $table->fieldsBase();
            $table->fieldsUpdatedAt();
            $table->softDeletes();

            $table->fieldExtract(Participant::FIELD_POINT_ID)->type('bigint');
            $table->fieldExtract(Participant::FIELD_ACTOR_ID)->type('bigint');
            $table->fieldExtract(Participant::FIELD_ROLE)->type('varchar(255)');

            $table->index(Participant::FIELD_ACTOR_ID);
            $table->unique([Participant::FIELD_POINT_ID, Participant::FIELD_ACTOR_ID, Participant::FIELD_ROLE]);
        });
    }
};
