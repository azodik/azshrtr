<?php

namespace App\Services\Ops;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

class BatchDeleter
{
    /**
     * Delete matching Eloquent rows in primary-key batches.
     *
     * @param  Builder<Model>  $query
     */
    public function deleteEloquent(Builder $query, string $keyColumn = 'id', int $chunk = 1000): int
    {
        $chunk = max(1, $chunk);
        $deleted = 0;

        while (true) {
            $ids = (clone $query)
                ->orderBy($keyColumn)
                ->limit($chunk)
                ->pluck($keyColumn);

            if ($ids->isEmpty()) {
                break;
            }

            $deleted += $query->getModel()->newQuery()
                ->whereIn($keyColumn, $ids->all())
                ->delete();
        }

        return $deleted;
    }

    /**
     * Delete matching query-builder rows in primary-key batches.
     */
    public function deleteQuery(QueryBuilder $query, string $table, string $keyColumn = 'id', int $chunk = 1000): int
    {
        $chunk = max(1, $chunk);
        $deleted = 0;

        while (true) {
            $ids = (clone $query)
                ->orderBy($keyColumn)
                ->limit($chunk)
                ->pluck($keyColumn);

            if ($ids->isEmpty()) {
                break;
            }

            $deleted += DB::table($table)->whereIn($keyColumn, $ids->all())->delete();
        }

        return $deleted;
    }
}
