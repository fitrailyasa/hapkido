<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->unsignedSmallInteger('score_a')->nullable()->after('athlete_b_id');
            $table->unsignedSmallInteger('score_b')->nullable()->after('score_a');
            $table->foreignId('winner_id')->nullable()->after('winner_athlete_id')->constrained('athletes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('winner_id');
            $table->dropColumn(['score_a', 'score_b']);
        });
    }
};
