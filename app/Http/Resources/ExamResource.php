<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'title'         => $this->title,
            'exam_date'     => $this->exam_date?->toDateString(),
            'total_marks'   => $this->total_marks,
            'passing_marks' => $this->passing_marks,
            'status'        => $this->status,
            'description'   => $this->description,
            'results_count' => $this->whenCounted('results'),
            'batch'         => $this->whenLoaded('batch', fn() => [
                'id'   => $this->batch->id,
                'name' => $this->batch->name,
            ]),
            'subject'       => $this->whenLoaded('subject', fn() => $this->subject ? [
                'id'   => $this->subject->id,
                'name' => $this->subject->name,
                'code' => $this->subject->code,
            ] : null),
            'exam_type'     => $this->whenLoaded('examType', fn() => $this->examType ? [
                'id'   => $this->examType->id,
                'name' => $this->examType->name,
            ] : null),
            'created_by'    => $this->whenLoaded('createdBy', fn() => $this->createdBy ? [
                'id'   => $this->createdBy->id,
                'name' => $this->createdBy->name,
            ] : null),
            'created_at'    => $this->created_at?->toDateString(),
        ];
    }
}
