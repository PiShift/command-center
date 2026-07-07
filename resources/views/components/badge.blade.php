{{--
  Badge component.
  Usage: @include('components.badge', ['type' => 'status|priority|type|project_status|health|customer_status|invoice_status', 'value' => $val])
--}}
@php
$maps = [
    'status' => [
        'backlog'     => ['label' => 'Backlog',     'bg' => '#f3f2ee', 'text' => '#5c5c5a'],
        'in-progress' => ['label' => 'In Progress', 'bg' => '#eef3fb', 'text' => '#3a6fba'],
        'in-review'   => ['label' => 'In Review',   'bg' => '#fef9ec', 'text' => '#9a7a1a'],
        'done'        => ['label' => 'Done',         'bg' => '#edf7f2', 'text' => '#2e7d55'],
    ],
    'priority' => [
        'high'   => ['label' => 'High',   'bg' => '#fde8de', 'text' => '#b94040'],
        'medium' => ['label' => 'Medium', 'bg' => '#fef9ec', 'text' => '#9a7a1a'],
        'low'    => ['label' => 'Low',    'bg' => '#f3f2ee', 'text' => '#5c5c5a'],
    ],
    'type' => [
        'bug'     => ['label' => 'Bug',     'bg' => '#fde8de', 'text' => '#b94040'],
        'feature' => ['label' => 'Feature', 'bg' => '#eef3fb', 'text' => '#3a6fba'],
        'change'  => ['label' => 'Change',  'bg' => '#f3f2ee', 'text' => '#5c5c5a'],
    ],
    'project_status' => [
        'active'   => ['label' => 'Active',    'bg' => '#edf7f2', 'text' => '#2e7d55'],
        'paused'   => ['label' => 'Paused',    'bg' => '#fef9ec', 'text' => '#9a7a1a'],
        'complete' => ['label' => 'Complete',  'bg' => '#f3f2ee', 'text' => '#5c5c5a'],
    ],
    'health' => [
        'on-track' => ['label' => 'On Track', 'bg' => '#edf7f2', 'text' => '#2e7d55'],
        'at-risk'  => ['label' => 'At Risk',  'bg' => '#fef9ec', 'text' => '#9a7a1a'],
        'blocked'  => ['label' => 'Blocked',  'bg' => '#fde8de', 'text' => '#b94040'],
    ],
    'customer_status' => [
        'active'   => ['label' => 'Active',   'bg' => '#edf7f2', 'text' => '#2e7d55'],
        'prospect' => ['label' => 'Prospect', 'bg' => '#eef3fb', 'text' => '#3a6fba'],
        'churned'  => ['label' => 'Churned',  'bg' => '#f3f2ee', 'text' => '#5c5c5a'],
    ],
    'source' => [
        'manual'  => ['label' => 'Manual',  'bg' => '#f3f2ee', 'text' => '#5c5c5a'],
        'ai-chat' => ['label' => 'AI Chat', 'bg' => '#eef3fb', 'text' => '#3a6fba'],
    ],
    'invoice_status' => [
        'not_invoiced' => ['label' => 'Not Invoiced', 'bg' => '#f3f2ee', 'text' => '#5c5c5a'],
        'invoiced'     => ['label' => 'Invoiced',     'bg' => '#fef9ec', 'text' => '#9a7a1a'],
        'paid'         => ['label' => 'Paid',         'bg' => '#edf7f2', 'text' => '#2e7d55'],
    ],
];
$badge = $maps[$type][$value] ?? ['label' => ucfirst($value), 'bg' => '#f3f2ee', 'text' => '#5c5c5a'];
@endphp
<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold"
      style="background: {{ $badge['bg'] }}; color: {{ $badge['text'] }}">
    {{ $badge['label'] }}
</span>
