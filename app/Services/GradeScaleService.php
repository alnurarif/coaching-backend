<?php

namespace App\Services;

use App\Models\GradeScale;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class GradeScaleService
{
    public function list(): Collection
    {
        return GradeScale::orderBy('sort_order')->get();
    }

    public function sync(array $scales): Collection
    {
        $tenantId = auth()->user()->tenant_id;

        DB::transaction(function () use ($scales, $tenantId) {
            GradeScale::where('tenant_id', $tenantId)->delete();

            foreach ($scales as $i => $scale) {
                GradeScale::create([
                    'tenant_id'   => $tenantId,
                    'label'       => $scale['label'],
                    'min_percent' => $scale['min_percent'],
                    'max_percent' => $scale['max_percent'],
                    'gpa'         => $scale['gpa'] ?? 0,
                    'sort_order'  => $i,
                ]);
            }
        });

        return $this->list();
    }
}
