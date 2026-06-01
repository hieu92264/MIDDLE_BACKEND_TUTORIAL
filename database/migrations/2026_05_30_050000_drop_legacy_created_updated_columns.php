<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
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
            $columns = $this->safeGetColumnListing($tableName);

            if ($columns === []) {
                continue;
            }

            $legacyColumns = array_values(array_intersect(['created', 'updated'], $columns));

            if ($legacyColumns === []) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($legacyColumns): void {
                $table->dropColumn($legacyColumns);
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            $columns = $this->safeGetColumnListing($tableName);

            if ($columns === []) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($columns): void {
                if (! in_array('created', $columns, true)) {
                    $table->timestamp('created')->nullable();
                }

                if (! in_array('updated', $columns, true)) {
                    $table->timestamp('updated')->nullable();
                }
            });
        }
    }

    /**
     * @return array<int, string>
     */
    protected function safeGetColumnListing(string $tableName): array
    {
        try {
            if (! Schema::hasTable($tableName)) {
                return [];
            }

            return Schema::getColumnListing($tableName);
        } catch (QueryException) {
            return [];
        }
    }
};
