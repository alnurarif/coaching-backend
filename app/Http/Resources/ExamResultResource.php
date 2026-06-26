<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $marks = $this->marks_obtained !== null ? (float) $this->marks_obtained : null;
        $total = $this->whenLoaded('exam', fn() => (float) $this->exam->total_marks, null);

        return [
            'id'             => $this->id,
            'position'       => $this->position,
            'student_id'     => $this->whenLoaded('student', fn() => $this->student?->id),
            'student_code'   => $this->whenLoaded('student', fn() => $this->student?->student_id),
            'name'           => $this->whenLoaded('student', fn() => $this->student?->name),
            'phone'          => $this->whenLoaded('student', fn() => $this->student?->phone),
            'marks_obtained' => $marks,
            'total_marks'    => $total,
            'percent'        => ($marks !== null && $total > 0)
                ? round($marks / $total * 100, 2)
                : null,
            'grade'          => $this->grade,
            'is_absent'      => (bool) $this->is_absent,
            'is_pass'        => $this->is_pass,
            'remarks'        => $this->remarks,
        ];
    }
}
