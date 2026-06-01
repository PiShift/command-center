<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('projects')
            ->whereNotNull('guide')
            ->whereRaw("TRIM(guide) != ''")
            ->orderBy('id')
            ->chunkById(200, function ($projects) use ($now) {
                $rows = [];

                foreach ($projects as $project) {
                    $rows[] = [
                        'project_id' => $project->id,
                        'title' => 'Project Guide',
                        'content' => $project->guide,
                        'type' => 'guide',
                        'sort_order' => 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if (! empty($rows)) {
                    DB::table('project_documents')->insert($rows);
                }
            });
    }

    public function down(): void
    {
        // Keep migrated data to avoid accidental loss on rollback.
    }
};
