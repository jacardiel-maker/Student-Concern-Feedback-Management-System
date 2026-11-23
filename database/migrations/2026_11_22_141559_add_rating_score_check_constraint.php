<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const CONSTRAINT_NAME = 'chk_score';

    public function up(): void
    {
        if (! $this->isMySqlFamily() || $this->constraintExists()) {
            return;
        }

        DB::statement('ALTER TABLE ratings ADD CONSTRAINT chk_score CHECK (score BETWEEN 1 AND 5)');
    }

    public function down(): void
    {
        if (! $this->isMySqlFamily() || ! $this->constraintExists()) {
            return;
        }

        DB::statement('ALTER TABLE ratings DROP CHECK chk_score');
    }

    private function isMySqlFamily(): bool
    {
        return in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true);
    }

    private function constraintExists(): bool
    {
        $constraints = DB::select(
            'SELECT COUNT(*) AS total
             FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND CONSTRAINT_NAME = ?
               AND CONSTRAINT_TYPE = ?',
            ['ratings', self::CONSTRAINT_NAME, 'CHECK'],
        );

        return (int) $constraints[0]->total > 0;
    }
};
