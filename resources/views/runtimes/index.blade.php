<x-layouts.app title="Runtimes">
    <div class="flex flex-col gap-6">
        <div>
            <h1 class="text-[24px] font-semibold text-ink mb-1">Runtimes</h1>
            <p class="text-[13px] text-dim">Connected agent runtimes grouped by machine.</p>
        </div>

        @if($machines->isEmpty())
            <div class="flex flex-col items-center justify-center py-16 px-6 bg-card border border-hairline rounded-xl text-center shadow-[0_1px_3px_rgba(20,20,19,0.04)]">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 text-muted mb-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 5.25A2.25 2.25 0 0 1 5.25 3h13.5A2.25 2.25 0 0 1 21 5.25v9.5A2.25 2.25 0 0 1 18.75 17H5.25A2.25 2.25 0 0 1 3 14.75v-9.5ZM7.5 21h9"/>
                </svg>
                <p class="text-[14px] font-medium text-dim mb-1">No runtimes connected.</p>
                <p class="text-[13px] text-muted">Start your daemon to connect local runtimes.</p>
            </div>
        @else
            <div x-data="{ selected: 0 }" class="grid grid-cols-1 lg:grid-cols-[280px_minmax(0,1fr)] gap-5 min-h-[620px]">
                <aside class="bg-surface border border-line rounded-xl p-2 overflow-y-auto">
                    <div class="space-y-2">
                        @foreach($machines as $machine)
                            <button
                                type="button"
                                @click="selected = {{ $loop->index }}"
                                :class="selected === {{ $loop->index }} ? 'bg-card border-line shadow-[0_1px_3px_rgba(20,20,19,0.08)]' : 'bg-transparent border-transparent hover:bg-hairline'"
                                class="w-full text-left rounded-lg border p-3 transition-colors duration-150 cursor-pointer"
                            >
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full {{ $machine['is_online'] ? 'bg-success' : 'bg-muted' }}"></span>
                                            <p class="text-[13px] font-semibold text-ink truncate">{{ $machine['hostname'] }}</p>
                                        </div>
                                        <p class="text-[11px] text-muted mt-1">{{ $machine['runtime_count'] }} {{ $machine['runtime_count'] === 1 ? 'runtime' : 'runtimes' }}</p>
                                    </div>
                                </div>
                                <div class="mt-2 flex items-center gap-1.5 flex-wrap">
                                    @foreach($machine['providers'] as $provider)
                                        <x-provider-icon :provider="$provider" size="4" />
                                    @endforeach
                                </div>
                            </button>
                        @endforeach
                    </div>
                </aside>

                <section class="min-w-0">
                    @foreach($machines as $machine)
                        <div x-show="selected === {{ $loop->index }}" x-cloak class="bg-card border border-line rounded-xl shadow-[0_1px_3px_rgba(20,20,19,0.04)] overflow-hidden">
                            <div class="px-6 py-5 border-b border-hairline">
                                <div class="flex items-start justify-between gap-3 flex-wrap">
                                    <div>
                                        <h2 class="text-[20px] font-semibold text-ink leading-tight">{{ $machine['hostname'] }}</h2>
                                        <div class="flex items-center gap-2 mt-2 flex-wrap">
                                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $machine['is_online'] ? 'bg-[#edf7f2] text-[#2e7d55]' : 'bg-surface text-muted' }}">
                                                <span class="w-1.5 h-1.5 rounded-full {{ $machine['is_online'] ? 'bg-success' : 'bg-muted' }}"></span>
                                                {{ $machine['is_online'] ? 'Online' : 'Offline' }}
                                            </span>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-surface text-dim">Local · this machine</span>
                                        </div>
                                    </div>
                                </div>

                                <p class="text-[12px] text-muted mt-3">
                                    {{ $machine['runtime_count'] }} {{ $machine['runtime_count'] === 1 ? 'runtime' : 'runtimes' }} ·
                                    {{ $machine['online_count'] }} online ·
                                    {{ $machine['work_label'] }} ·
                                    {{ $machine['cli_version'] ? 'v' . $machine['cli_version'] : '—' }} ·
                                    daemon {{ substr($machine['daemon_id'], 0, 8) }}…
                                </p>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead>
                                        <tr class="bg-canvas border-b border-line">
                                            <th class="px-5 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-muted">Runtime</th>
                                            <th class="px-5 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-muted">Health</th>
                                            <th class="px-5 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-muted">Agents</th>
                                            <th class="px-5 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-muted">CLI</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-hairline">
                                        @foreach($machine['runtimes'] as $runtime)
                                            <tr class="hover:bg-canvas transition-colors duration-100">
                                                <td class="px-5 py-3.5">
                                                    <div class="flex items-center gap-2.5 min-w-0">
                                                        <x-provider-icon :provider="$runtime->provider" size="6" />
                                                        <div class="min-w-0">
                                                            <p class="text-[13px] font-medium text-ink truncate">{{ $runtime->name }}</p>
                                                            <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-surface text-muted">Built-in</span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-5 py-3.5">
                                                    <div class="flex items-center gap-2">
                                                        <span class="w-2 h-2 rounded-full {{ $runtime->status === 'online' ? 'bg-success' : 'bg-muted' }}"></span>
                                                        <span class="text-[13px] font-medium {{ $runtime->status === 'online' ? 'text-success' : 'text-muted' }}">{{ $runtime->status === 'online' ? 'Online' : 'Offline' }}</span>
                                                    </div>
                                                </td>
                                                <td class="px-5 py-3.5">
                                                    @if($runtime->agents_count > 0)
                                                        <span class="text-[13px] text-ink">{{ $runtime->agents_count }}</span>
                                                    @else
                                                        <span class="text-[13px] text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td class="px-5 py-3.5">
                                                    @if($runtime->cli_version)
                                                        <span class="text-[13px] text-dim">{{ $runtime->cli_version }}</span>
                                                    @else
                                                        <span class="text-[13px] text-muted">—</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </section>
            </div>
        @endif
    </div>
</x-layouts.app>
