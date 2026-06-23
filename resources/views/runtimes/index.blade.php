@php
    $providerColors = [
        'claude' => '#7c3aed',
        'copilot' => '#2563a8',
    ];
    $defaultProviderColor = '#6b7280';
@endphp

<x-layouts.app title="Runtimes">
    <div class="flex flex-col gap-6">
        {{-- Header --}}
        <div>
            <h1 class="text-[24px] font-semibold text-ink mb-1">Runtimes</h1>
            <p class="text-[13px] text-dim">Connected agent runtimes on your machines.</p>
        </div>

        @if($runtimes->isEmpty())
            {{-- Empty state --}}
            <div class="flex flex-col items-center justify-center py-16 px-6 bg-card border border-hairline rounded-12 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 text-hairline mb-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V5a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 5v14a2.25 2.25 0 0 0 2.25 2.25h13.5A2.25 2.25 0 0 0 21 19V5m-3-7l-6-3.75-6 3.75m6-3.75v16.5m0-6h.008v.008H12v-.008Z"/>
                </svg>
                <p class="text-[14px] font-medium text-dim mb-1">No runtimes connected.</p>
                <p class="text-[13px] text-muted">Start the daemon on your machine to connect a runtime.</p>
            </div>
        @else
            {{-- Table --}}
            <div class="bg-card border border-hairline rounded-12 shadow-sm overflow-hidden">
                <table class="w-full">
                    <thead>
                        <tr class="bg-canvas border-b border-line">
                            <th class="px-6 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-muted">Runtime</th>
                            <th class="px-6 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-muted">Status</th>
                            <th class="px-6 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-muted">Owner</th>
                            <th class="px-6 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-muted">Agents</th>
                            <th class="px-6 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-muted">Last Seen</th>
                            <th class="px-6 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-muted">Version</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-hairline">
                        @foreach($runtimes as $runtime)
                        <tr class="hover:bg-canvas transition-colors duration-100">
                            {{-- Runtime --}}
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                                         style="background: {{ $providerColors[$runtime->provider] ?? $defaultProviderColor }}">
                                        @if($runtime->provider === 'claude')
                                        <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/>
                                        </svg>
                                        @elseif($runtime->provider === 'copilot')
                                        <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/>
                                        </svg>
                                        @else
                                        <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><path d="M2 17h20"/>
                                        </svg>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-[13px] font-medium text-ink truncate">{{ $runtime->name }}</p>
                                        @if($runtime->device_info)
                                        <p class="text-[12px] text-muted truncate">{{ $runtime->device_info }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            {{-- Status --}}
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    @if($runtime->status === 'online')
                                    <span class="w-2 h-2 rounded-full bg-[#3d9970]"></span>
                                    <span class="text-[13px] text-[#3d9970] font-medium">Online</span>
                                    @else
                                    <span class="w-2 h-2 rounded-full bg-muted"></span>
                                    <span class="text-[13px] text-muted font-medium">Offline</span>
                                    @endif
                                </div>
                            </td>

                            {{-- Owner --}}
                            <td class="px-6 py-4">
                                @if($runtime->user)
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold text-white shrink-0"
                                         style="background: {{ $runtime->user->color ?? '#D97757' }}">
                                        {{ $runtime->user->initials ?? strtoupper(substr($runtime->user->name, 0, 2)) }}
                                    </div>
                                    <span class="text-[13px] text-ink">{{ $runtime->user->name }}</span>
                                </div>
                                @else
                                <span class="text-[13px] text-muted">—</span>
                                @endif
                            </td>

                            {{-- Agents count --}}
                            <td class="px-6 py-4">
                                @php
                                    $agentCount = $runtime->agents->count();
                                @endphp
                                @if($agentCount > 0)
                                <span class="inline-flex items-center text-[11px] font-semibold rounded-full px-2 py-0.5 bg-surface text-muted">{{ $agentCount }}</span>
                                @else
                                <span class="text-[13px] text-muted">—</span>
                                @endif
                            </td>

                            {{-- Last seen --}}
                            <td class="px-6 py-4">
                                @if($runtime->last_seen_at)
                                <span class="text-[13px] text-dim">{{ $runtime->last_seen_at->diffForHumans() }}</span>
                                @else
                                <span class="text-[13px] text-muted">Never</span>
                                @endif
                            </td>

                            {{-- Version --}}
                            <td class="px-6 py-4">
                                @if($runtime->cli_version)
                                <span class="text-[13px] text-dim font-mono">{{ $runtime->cli_version }}</span>
                                @else
                                <span class="text-[13px] text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-layouts.app>
