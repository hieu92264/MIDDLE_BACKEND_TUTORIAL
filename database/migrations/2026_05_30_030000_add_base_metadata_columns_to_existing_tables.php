<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    protected array $tables = [
        'users',
        'password_reset_tokens',
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'personal_access_tokens',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            $columns = Schema::getColumnListing($tableName);

            Schema::table($tableName, function (Blueprint $table) use ($columns): void {
                if (! in_array('is_active', $columns, true)) {
                    $table->boolean('is_active')->default(true);
                }

                if (! in_array('user_name_created', $columns, true)) {
                    $table->unsignedBigInteger('user_name_created')->nullable();
                }

                if (! in_array('user_name_updated', $columns, true)) {
                    $table->unsignedBigInteger('user_name_updated')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            $columns = Schema::getColumnListing($tableName);
            $columnsToDrop = array_values(array_intersect([
                'is_active',
                'user_name_created',
                'user_name_updated',
            ], $columns));

            if ($columnsToDrop === []) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($columnsToDrop): void {
                $table->dropColumn($columnsToDrop);
            });
        }
    }
};
