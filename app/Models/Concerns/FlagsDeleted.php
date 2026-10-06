<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Soft delete memakai kolom boolean `is_deleted` (0/1).
 * Query otomatis hanya mengambil baris dengan is_deleted = 0.
 */
trait FlagsDeleted
{
    protected static function bootFlagsDeleted(): void
    {
        static::addGlobalScope('not_deleted', function (Builder $builder) {
            $builder->where($builder->getModel()->getTable().'.is_deleted', false);
        });
    }

    public function softDelete(): bool
    {
        return $this->forceFill(['is_deleted' => true])->save();
    }
}
