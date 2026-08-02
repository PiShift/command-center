<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;

class GlobalSearchService
{
    public function search(User $user, string $query): array
    {
        $q = trim($query);

        if (mb_strlen($q) < 2) {
            return $this->empty();
        }

        $like = '%' . $q . '%';
        $op   = \DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
        $canViewAll = $user->hasPermission('projects.view_all');

        // ── Projects ─────────────────────────────────────────────────────────
        $projectQuery = Project::with('customer')
            ->where(function ($qb) use ($like, $op) {
                $qb->where('name', $op, $like)
                   ->orWhere('description', $op, $like)
                   ->orWhere('stack', $op, $like)
                   ->orWhereHas('customer', fn ($c) => $c->where('name', $op, $like));
            });

        if (! $canViewAll) {
            $teamIds = $user->teams()->pluck('teams.id');
            $projectQuery->whereHas('teams', fn ($q) => $q->whereIn('teams.id', $teamIds));
        }

        $projects = $projectQuery->limit(5)->get()->map(fn ($p) => [
            'id'       => $p->id,
            'title'    => $p->name,
            'subtitle' => ucfirst($p->status) . ($p->customer ? ' · ' . $p->customer->name : ''),
            'url'      => '/projects/' . $p->id,
            'color'    => $p->color ?? '#6b7280',
        ])->values()->all();

        // ── Tasks ─────────────────────────────────────────────────────────────
        $taskQuery = Task::with('project')
            ->where(function ($qb) use ($like, $op) {
                $qb->where('title', $op, $like)
                   ->orWhere('description', $op, $like);
            });

        if (! $canViewAll) {
            $teamIds = $user->teams()->pluck('teams.id');
            $taskQuery->whereHas('project.teams', fn ($q) => $q->whereIn('teams.id', $teamIds));
        }

        $tasks = $taskQuery->limit(5)->get()->map(fn ($t) => [
            'id'       => $t->id,
            'title'    => $t->title,
            'subtitle' => ucfirst(str_replace('-', ' ', $t->status)) . ($t->project ? ' · ' . $t->project->name : ''),
            'url'      => '/tasks/' . $t->id,
            'type'     => $t->type ?? 'task',
            'status'   => $t->status,
        ])->values()->all();

        // ── Customers ────────────────────────────────────────────────────────
        $customers = [];
        if ($canViewAll) {
            $customers = Customer::where('name', $op, $like)
                ->orWhere('email', $op, $like)
                ->orWhere('company', $op, $like)
                ->orWhere('phone', $op, $like)
                ->limit(5)
                ->get()
                ->map(fn ($c) => [
                    'id'       => $c->id,
                    'title'    => $c->name,
                    'subtitle' => ($c->email ?? $c->company ?? '') . ' · ' . ucfirst($c->status ?? 'active'),
                    'url'      => '/customers/' . $c->id,
                ])->values()->all();
        }

        // ── Invoices ─────────────────────────────────────────────────────────
        $invoices = [];
        if ($canViewAll) {
            $invoices = Invoice::with('customer')
                ->where(function ($qb) use ($like, $op) {
                    $qb->where('invoice_number', $op, $like)
                       ->orWhereHas('customer', fn ($c) => $c->where('name', $op, $like));
                })
                ->limit(5)
                ->get()
                ->map(fn ($inv) => [
                    'id'       => $inv->id,
                    'title'    => $inv->invoice_number,
                    'subtitle' => ($inv->currency ?? 'MRU') . ' ' . number_format((float) $inv->total, 0)
                                  . ' · ' . ucfirst($inv->payment_status ?? 'unpaid')
                                  . ($inv->customer ? ' · ' . $inv->customer->name : ''),
                    'url'      => '/invoices/' . $inv->id,
                    'payment_status' => $inv->payment_status ?? 'unpaid',
                ])->values()->all();
        }

        // ── Sprints ──────────────────────────────────────────────────────────
        $sprintQuery = Sprint::with('project')
            ->where('name', $op, $like);

        if (! $canViewAll) {
            $teamIds = $user->teams()->pluck('teams.id');
            $sprintQuery->whereHas('project.teams', fn ($q) => $q->whereIn('teams.id', $teamIds));
        }

        $sprints = $sprintQuery->limit(5)->get()->map(fn ($s) => [
            'id'       => $s->id,
            'title'    => $s->name,
            'subtitle' => ucfirst($s->status) . ($s->project ? ' · ' . $s->project->name : ''),
            'url'      => '/projects/' . $s->project_id . '#sprints',
        ])->values()->all();

        return compact('projects', 'tasks', 'customers', 'invoices', 'sprints');
    }

    private function empty(): array
    {
        return [
            'projects'  => [],
            'tasks'     => [],
            'customers' => [],
            'invoices'  => [],
            'sprints'   => [],
        ];
    }
}
