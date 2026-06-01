<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
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
        $this->changeUserReferenceColumnsToBigInt();
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            $columns = $this->safeGetColumnListing($tableName);

            if ($columns === []) {
                continue;
            }

            if (in_array('user_name_created', $columns, true)) {
                $this->safeStatement("ALTER TABLE `{$tableName}` MODIFY `user_name_created` VARCHAR(255) NULL");
            }

            if (in_array('user_name_updated', $columns, true)) {
                $this->safeStatement("ALTER TABLE `{$tableName}` MODIFY `user_name_updated` VARCHAR(255) NULL");
            }
        }
    }

    protected function changeUserReferenceColumnsToBigInt(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        foreach ($this->tables as $tableName) {
            $columns = $this->safeGetColumnListing($tableName);

            if ($columns === []) {
                continue;
            }

            if (in_array('user_name_created', $columns, true)) {
                $this->safeStatement("ALTER TABLE `{$tableName}` MODIFY `user_name_created` BIGINT UNSIGNED NULL");
            }

            if (in_array('user_name_updated', $columns, true)) {
                $this->safeStatement("ALTER TABLE `{$tableName}` MODIFY `user_name_updated` BIGINT UNSIGNED NULL");
            }
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

    protected function safeStatement(string $sql): void
    {
        try {
            DB::statement($sql);
        } catch (QueryException) {
            // Ignore missing infrastructure tables on databases that were created before these tables existed.
        }
    }
};
