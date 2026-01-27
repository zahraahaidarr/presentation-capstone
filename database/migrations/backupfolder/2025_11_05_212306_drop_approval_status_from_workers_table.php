<?php

// database/migrations/XXXX_XX_XX_XXXXXX_drop_approval_status_from_workers_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // For MySQL dropping a column requires doctrine/dbal in older Laravel versions.
        // If needed: composer require doctrine/dbal
        Schema::table('workers', function (Blueprint $table) {
            if (Schema::hasColumn('workers', 'approval_status')) {
                $table->dropColumn('approval_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('workers', function (Blueprint $table) {
            if (!Schema::hasColumn('workers', 'approval_status')) {
                $table->string('approval_status')->default('PENDING'); // or your previous type
            }
        });
    }
};

