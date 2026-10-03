<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipments', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['head_guard', 'body_protector']);
            $table->string('color');
            $table->string('size');
            $table->string('code')->unique();
            $table->enum('status', ['available', 'loaned', 'maintenance'])->default('available');
            $table->timestamps();
        });

        Schema::create('equipment_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('athlete_id')->constrained()->cascadeOnDelete();
            $table->foreignId('schedule_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('loaned_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->string('loan_condition')->nullable();
            $table->string('return_condition')->nullable();
            $table->enum('status', ['loaned', 'returned'])->default('loaned');
            $table->foreignId('loaned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('returned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('athlete_a_id')->nullable()->constrained('athletes')->nullOnDelete();
            $table->foreignId('athlete_b_id')->nullable()->constrained('athletes')->nullOnDelete();
            $table->foreignId('winner_athlete_id')->nullable()->constrained('athletes')->nullOnDelete();
            $table->enum('status', ['pending', 'running', 'finished'])->default('pending');
            $table->string('round')->default('penyisihan');
            $table->integer('position')->default(1);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create('performances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('athlete_id')->constrained()->cascadeOnDelete();
            $table->integer('order_no')->default(1);
            $table->enum('status', ['waiting', 'performing', 'finished'])->default('waiting');
            $table->decimal('final_score', 8, 2)->nullable();
            $table->integer('rank')->nullable();
            $table->timestamp('performed_at')->nullable();
            $table->timestamps();
            $table->unique(['schedule_id', 'athlete_id']);
        });

        Schema::create('scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('performance_id')->constrained()->cascadeOnDelete();
            $table->integer('judge_no');
            $table->decimal('score', 6, 2);
            $table->timestamps();
            $table->unique(['performance_id', 'judge_no']);
        });

        Schema::create('bracket_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('round');
            $table->integer('position');
            $table->foreignId('athlete_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->unique(['category_id', 'round', 'position']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
        Schema::dropIfExists('bracket_slots');
        Schema::dropIfExists('scores');
        Schema::dropIfExists('performances');
        Schema::dropIfExists('matches');
        Schema::dropIfExists('equipment_loans');
        Schema::dropIfExists('equipments');
    }
};
