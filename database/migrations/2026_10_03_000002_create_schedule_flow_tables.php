<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->string('match_no');
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('arena_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['daeryun', 'art']);
            $table->string('round')->default('penyisihan');
            $table->integer('order_no')->default(1);
            $table->date('match_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->enum('status', ['pending', 'preparation', 'running', 'finished', 'cancelled'])->default('pending');
            $table->timestamps();
            $table->unique('match_no');
        });

        Schema::create('callings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('athlete_id')->constrained()->cascadeOnDelete();
            $table->enum('level', ['30', '15', '5'])->nullable();
            $table->enum('status', ['waiting', 'called', 'ready'])->default('waiting');
            $table->timestamp('called_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamps();
            $table->unique(['schedule_id', 'athlete_id']);
        });

        Schema::create('verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('athlete_id')->constrained()->cascadeOnDelete();
            $table->enum('method', ['qr', 'manual'])->default('qr');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->enum('status', ['present', 'absent'])->default('present');
            $table->timestamps();
            $table->unique(['schedule_id', 'athlete_id']);
        });

        Schema::create('readiness_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('athlete_id')->constrained()->cascadeOnDelete();
            $table->boolean('attendance_check')->default(false);
            $table->boolean('athlete_check')->default(false);
            $table->boolean('equipment_check')->default(false);
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'ready'])->default('pending');
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();
            $table->unique(['schedule_id', 'athlete_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('readiness_checks');
        Schema::dropIfExists('verifications');
        Schema::dropIfExists('callings');
        Schema::dropIfExists('schedules');
    }
};
