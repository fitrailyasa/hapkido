<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contingents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('region')->nullable();
            $table->string('coach_name')->nullable();
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('type', ['daeryun', 'art'])->default('daeryun');
            $table->enum('gender', ['male', 'female', 'open'])->default('open');
            $table->string('age_class')->nullable();
            $table->timestamps();
        });

        Schema::create('athletes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('gender', ['male', 'female']);
            $table->date('birth_date')->nullable();
            $table->string('id_number')->nullable();
            $table->foreignId('contingent_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('participant_number')->unique();
            $table->string('qr_code')->unique();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('photo')->nullable();
            $table->timestamps();
        });

        Schema::create('arenas', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('label');
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('match_type', ['daeryun', 'art'])->default('daeryun');
            $table->enum('status', ['idle', 'preparation', 'running'])->default('idle');
            $table->foreignId('current_schedule_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arenas');
        Schema::dropIfExists('athletes');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('contingents');
    }
};
