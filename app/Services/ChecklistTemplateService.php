<?php

namespace App\Services;

use App\Models\ChecklistTemplate;
use App\Models\Task;

class ChecklistTemplateService
{
    /**
     * Copy the items of every checklist template matching the task's
     * project + type onto the task. Matching templates are combined and
     * identical items (case-insensitive label match) are deduplicated.
     *
     * Items already present on the task (by label) are never duplicated.
     * Only applies to the task as given — existing tasks are untouched
     * unless this is called explicitly.
     *
     * @return int Number of checklist items attached.
     */
    public function applyToTask(Task $task): int
    {
        $templates = ChecklistTemplate::with('items')
            ->matching($task->project_id, $task->type)
            ->orderBy('id')
            ->get();

        if ($templates->isEmpty()) {
            return 0;
        }

        $seen = $task->checklists()
            ->pluck('label')
            ->map(fn (string $label) => $this->normalize($label))
            ->all();

        $sortOrder = ($task->checklists()->max('sort_order') ?? -1) + 1;
        $attached = 0;

        foreach ($templates as $template) {
            foreach ($template->items as $item) {
                $key = $this->normalize($item->label);

                if ($key === '' || in_array($key, $seen, true)) {
                    continue;
                }

                $seen[] = $key;

                $task->checklists()->create([
                    'checklist_template_item_id' => $item->id,
                    'label' => $item->label,
                    'is_checked' => false,
                    'sort_order' => $sortOrder++,
                ]);

                $attached++;
            }
        }

        return $attached;
    }

    private function normalize(string $label): string
    {
        return mb_strtolower(trim($label));
    }
}
