<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migration 000003 created the equipment_loans.equipment_id foreign key
     * referencing the "equipment" table (the result of Str::plural applied to
     * an uncountable name), even though the table is actually named "equipments".
     * As a result every INSERT into equipment_loans failed
     * ("no such table: main.equipment"). The table is recreated with the
     * correct reference and the existing rows are copied over.
     */
    public function up(): void
    {
        $foreignKeyDisabled = $this->disableForeignKeys();

        Schema::create('equipment_loans_rebuilt', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained('equipments')->cascadeOnDelete();
            $table->foreignId('athlete_id')->constrained('athletes')->cascadeOnDelete();
            $table->unsignedInteger('qty')->default(1);
            $table->foreignId('schedule_id')->nullable()->constrained('schedules')->nullOnDelete();
            $table->timestamp('loaned_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->string('loan_condition')->nullable();
            $table->string('return_condition')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['loaned', 'returned'])->default('loaned');
            $table->foreignId('loaned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('returned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $this->copyRows('equipment_loans', 'equipment_loans_rebuilt');

        Schema::drop('equipment_loans');
        Schema::rename('equipment_loans_rebuilt', 'equipment_loans');

        $this->restoreForeignKeys($foreignKeyDisabled);
    }

    public function down(): void
    {
        $foreignKeyDisabled = $this->disableForeignKeys();

        Schema::create('equipment_loans_original', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained('equipment')->cascadeOnDelete();
            $table->foreignId('athlete_id')->constrained('athletes')->cascadeOnDelete();
            $table->foreignId('schedule_id')->nullable()->constrained('schedules')->nullOnDelete();
            $table->timestamp('loaned_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->string('loan_condition')->nullable();
            $table->string('return_condition')->nullable();
            $table->enum('status', ['loaned', 'returned'])->default('loaned');
            $table->foreignId('loaned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('returned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $this->copyRows('equipment_loans', 'equipment_loans_original');

        Schema::drop('equipment_loans');
        Schema::rename('equipment_loans_original', 'equipment_loans');

        $this->restoreForeignKeys($foreignKeyDisabled);
    }

    protected function copyRows(string $from, string $to): void
    {
        $columns = array_values(array_intersect(
            Schema::getColumnListing($from),
            [
                'id', 'equipment_id', 'athlete_id', 'qty', 'schedule_id', 'loaned_at',
                'returned_at', 'loan_condition', 'return_condition', 'notes', 'status',
                'loaned_by', 'returned_by', 'created_at', 'updated_at',
            ]
        ));

        $targetColumns = array_values(array_intersect($columns, Schema::getColumnListing($to)));

        if ($targetColumns === []) {
            return;
        }

        $list = implode(', ', $targetColumns);

        DB::statement("INSERT INTO {$to} ({$list}) SELECT {$list} FROM {$from}");
    }

    protected function disableForeignKeys(): bool
    {
        if (DB::getDriverName() !== 'sqlite') {
            return false;
        }

        $enabled = (bool) (DB::selectOne('PRAGMA foreign_keys')->foreign_keys ?? false);

        if ($enabled) {
            DB::statement('PRAGMA foreign_keys = OFF');
        }

        return $enabled;
    }

    protected function restoreForeignKeys(bool $disabled): void
    {
        if ($disabled) {
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }
};
