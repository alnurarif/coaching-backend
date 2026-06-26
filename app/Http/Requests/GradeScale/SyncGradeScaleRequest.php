<?php

namespace App\Http\Requests\GradeScale;

use Illuminate\Foundation\Http\FormRequest;

class SyncGradeScaleRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'scales'                => ['required', 'array', 'min:1'],
            'scales.*.label'        => ['required', 'string', 'max:10'],
            'scales.*.min_percent'  => ['required', 'numeric', 'min:0', 'max:100'],
            'scales.*.max_percent'  => ['required', 'numeric', 'min:0', 'max:100'],
            'scales.*.gpa'          => ['nullable', 'numeric', 'min:0', 'max:5'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $scales = collect($this->input('scales', []));

            foreach ($scales as $i => $scale) {
                if (($scale['min_percent'] ?? 0) > ($scale['max_percent'] ?? 0)) {
                    $v->errors()->add(
                        "scales.{$i}.min_percent",
                        'Minimum percent cannot be greater than maximum percent.'
                    );
                }
            }

            if ($v->errors()->isNotEmpty()) {
                return;
            }

            // Overlap detection: sort by min_percent, check consecutive ranges don't overlap
            $sorted = $scales
                ->filter(fn($s) => isset($s['min_percent'], $s['max_percent']))
                ->sortBy('min_percent')
                ->values();

            for ($i = 1; $i < $sorted->count(); $i++) {
                $prev = $sorted[$i - 1];
                $curr = $sorted[$i];
                if ((float) $curr['min_percent'] <= (float) $prev['max_percent']) {
                    $v->errors()->add(
                        'scales',
                        "Ranges overlap between '{$prev['label']}' ({$prev['min_percent']}–{$prev['max_percent']}) and '{$curr['label']}' ({$curr['min_percent']}–{$curr['max_percent']})."
                    );
                }
            }
        });
    }
}
