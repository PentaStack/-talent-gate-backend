<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->text('cover_letter')->after('status');

            // useCurrent() sets DEFAULT CURRENT_TIMESTAMP at the DB level —
            // a safety net for any non-Eloquent insert. The model's creating
            // event is the authoritative setter during normal application flow.
            $table->timestamp('submitted_at')->useCurrent()->after('cover_letter');

            $table->timestamp('viewed_at')->nullable()->after('submitted_at');

            // Prevents the same candidate from applying twice to the same job.
            $table->unique(['job_id', 'candidate_id']);
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->dropUnique(['job_id', 'candidate_id']);
            $table->dropColumn(['viewed_at', 'submitted_at', 'cover_letter']);
        });
    }
};
