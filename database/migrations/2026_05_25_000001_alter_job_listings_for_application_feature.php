<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const VALID_STATUSES = ['draft', 'pending', 'active', 'closed', 'rejected'];

    public function up(): void
    {
        // Rename legacy 'approved' rows before altering the column type so the
        // new enum values do not reject existing data.
        DB::table('job_listings')
            ->where('status', 'approved')
            ->update(['status' => 'active']);

        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            // PostgreSQL: keep the column as VARCHAR (already is) and enforce
            // valid values with a named CHECK constraint instead of an inline
            // ENUM type — simpler to modify in future migrations.
            $values = implode("','", self::VALID_STATUSES);
            DB::statement(
                "ALTER TABLE job_listings
                 ADD CONSTRAINT chk_job_listings_status
                 CHECK (status IN ('{$values}'))"
            );
        } elseif ($driver !== 'sqlite') {
            // MySQL / MariaDB: use a real ENUM column.
            Schema::table('job_listings', function (Blueprint $table): void {
                $table->enum('status', self::VALID_STATUSES)
                    ->default('pending')
                    ->change();
            });
        }
        // SQLite (tests): column stays VARCHAR, PHP enum cast enforces values.

        Schema::table('job_listings', function (Blueprint $table): void {
            // Temporary default allows NOT NULL to succeed on existing rows.
            $table->date('application_deadline')
                ->default('2099-12-31')
                ->after('status');
        });

        // Drop the default so future inserts must supply an explicit deadline.
        // On SQLite this is a no-op — acceptable because the FormRequest and
        // factory enforce a real value at the application layer.
        if ($driver !== 'sqlite') {
            Schema::table('job_listings', function (Blueprint $table): void {
                $table->date('application_deadline')
                    ->default(null)
                    ->change();
            });
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        Schema::table('job_listings', function (Blueprint $table) use ($driver): void {
            $table->dropColumn('application_deadline');

            if ($driver === 'pgsql') {
                // CHECK constraint is dropped separately below.
            } elseif ($driver !== 'sqlite') {
                $table->string('status')->default('pending')->change();
            }
        });

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE job_listings DROP CONSTRAINT IF EXISTS chk_job_listings_status');
        }
    }
};
