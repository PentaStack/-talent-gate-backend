<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_listings', function (Blueprint $table) {
            $table->text('description')->nullable()->after('title');
            $table->text('requirements')->nullable()->after('description');
            $table->string('salary_range')->nullable()->after('requirements');
            $table->string('work_type')->default('onsite')->after('salary_range');
            $table->string('location')->nullable()->after('work_type');
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete()->after('location');
        });
    }

    public function down(): void
    {
        Schema::table('job_listings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
            $table->dropColumn(['description', 'requirements', 'salary_range', 'work_type', 'location']);
        });
    }
};
