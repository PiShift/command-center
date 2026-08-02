<?php

namespace App\Livewire;

use App\Models\Project;
use App\Services\GlobalSearchService;
use Livewire\Component;

class GlobalSearch extends Component
{
    public bool $isOpen = false;
    public string $query = '';
    public array $results = [];
    public bool $searching = false;
    public array $recentSearches = [];
    public ?int $currentProjectId = null;
    public ?string $currentProjectName = null;

    private string $lastSearchedQuery = '';

    public function mount(): void
    {
        $this->recentSearches = session('recent_searches', []);
    }

    public function open(): void
    {
        $this->recentSearches = session('recent_searches', []);
        $this->isOpen = true;
        $this->results = [];
        $this->searching = false;
        $this->lastSearchedQuery = '';
        $this->detectProject();
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->query = '';
        $this->results = [];
        $this->searching = false;
        $this->lastSearchedQuery = '';
    }

    public function clearRecentSearches(): void
    {
        session()->forget('recent_searches');
        $this->recentSearches = [];
    }

    public function searchRecent(string $term): void
    {
        $this->query = $term;
        $this->updatedQuery($term);
    }

    public function updatedQuery(string $value): void
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            $this->results = [];
            $this->searching = false;
            $this->lastSearchedQuery = '';
            return;
        }

        if (mb_strlen($trimmed) < 2) {
            return;
        }

        if ($trimmed === $this->lastSearchedQuery) {
            return;
        }

        $this->lastSearchedQuery = $trimmed;
        $this->searching = true;

        $service = app(GlobalSearchService::class);
        $this->results = $service->search(auth()->user(), $trimmed);

        // Persist to recent searches
        $recent = collect(session('recent_searches', []))
            ->prepend($trimmed)
            ->unique()
            ->values()
            ->take(5)
            ->all();
        session(['recent_searches' => $recent]);
        $this->recentSearches = $recent;

        $this->searching = false;
    }

    public function detectProject(): void
    {
        $path = parse_url(url()->current(), PHP_URL_PATH) ?? '';

        if (! preg_match('#^/projects/(\d+)(?:/.*)?$#', $path, $matches)) {
            $this->currentProjectId = null;
            $this->currentProjectName = null;
            return;
        }

        $projectId = (int) $matches[1];
        $project = Project::query()->find($projectId);

        $this->currentProjectId = $project?->id;
        $this->currentProjectName = $project?->name;
    }

    public function goToNewProject(): void
    {
        $this->close();
        $this->redirect('/projects/create', navigate: true);
    }

    public function goToNewInvoice(): void
    {
        $this->close();
        $this->redirect('/invoices/create', navigate: true);
    }

    public function openQuickCustomerModal(): void
    {
        $this->close();
        $this->dispatch('open-quick-customer-modal');
    }

    public function openNewTask(): void
    {
        $this->close();
        $this->dispatch('new-task', projectId: $this->currentProjectId);
    }

    public function openNewSprint(): void
    {
        $this->close();
        $this->dispatch('open-sprint-modal');
    }

    public function openNewBacklogItem(): void
    {
        $this->close();
        $this->dispatch('open-backlog-modal');
    }

    public function render()
    {
        return view('livewire.global-search');
    }
}