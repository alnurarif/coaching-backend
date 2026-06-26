<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GradeScaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'label'       => $this->label,
            'min_percent' => $this->min_percent,
            'max_percent' => $this->max_percent,
            'gpa'         => $this->gpa,
            'sort_order'  => $this->sort_order,
        ];
    }
}
