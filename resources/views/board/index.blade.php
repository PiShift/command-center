<x-layouts.app title="War Room">

<livewire:kanban-board />
<livewire:task-modal />

<script>
window.addEventListener('load', function () {
    if (!window.Echo || !window.Livewire) return;

    // Listen for status changes on all projects
    @php
        $projectIds = \App\Models\Project::pluck('id');
    @endphp
    
    @foreach($projectIds as $projectId)
    window.Echo.channel('projects.{{ $projectId }}')
        .listen('.task.status', (e) => {
            // Livewire will handle the UI update
            window.Livewire.dispatch('taskStatusChanged', { taskId: e.taskId, status: e.status });
        });
    @endforeach
});
</script>

</x-layouts.app>
