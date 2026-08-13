<x-layouts.app title="War Room">

<livewire:kanban-board />
<livewire:task-modal />

<script>
window.addEventListener('load', function () {
    if (!window.Echo || !window.Livewire) return;

    let refreshTimer = null;
    const queueBoardRefresh = () => {
        if (refreshTimer !== null) {
            return;
        }

        refreshTimer = window.setTimeout(() => {
            refreshTimer = null;
            window.Livewire.dispatch('boardRealtimePulse');
        }, 350);
    };

    // Listen for status changes on all projects
    @php
        $projectIds = \App\Models\Project::pluck('id');
    @endphp
    
    @foreach($projectIds as $projectId)
    window.Echo.channel('projects.{{ $projectId }}')
        .listen('.task.status', () => {
            queueBoardRefresh();
        });
    @endforeach

    // Listen for agent activity on all tasks currently visible
    window.Echo.channel('agent-activity')
        .listen('.agent.started', () => {
            queueBoardRefresh();
        })
        .listen('.agent.completed', () => {
            queueBoardRefresh();
        })
        .listen('.agent.failed', () => {
            queueBoardRefresh();
        });
});
</script>

</x-layouts.app>
