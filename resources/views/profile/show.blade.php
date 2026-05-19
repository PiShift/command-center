<x-layouts.app>
    @php $pageTitle = 'My Profile'; @endphp

    <div style="max-width:1100px;margin:0 auto;padding:32px 24px">

        {{-- Flash messages --}}
        @if(session('success'))
            <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:10px;padding:12px 16px;margin-bottom:20px;font-size:13px;color:#166534">
                {{ session('success') }}
            </div>
        @endif

        <div style="display:grid;grid-template-columns:1.4fr 1fr;gap:24px;align-items:start">

            {{-- ═══════════════════════════════════════════════════════════════
                 LEFT COLUMN
            ═══════════════════════════════════════════════════════════════════ --}}
            <div style="display:flex;flex-direction:column;gap:20px">

                {{-- Personal Info Card --}}
                <div style="background:#fff;border:1px solid #e5e4df;border-radius:14px;padding:28px">
                    <h2 style="margin:0 0 20px;font-size:16px;font-weight:700;color:#141413">Personal Information</h2>

                    {{-- Avatar + color picker --}}
                    <div x-data="{ color: '{{ $user->color ?? '#D97757' }}' }" style="margin-bottom:24px;display:flex;align-items:center;gap:16px">
                        <div
                            :style="`width:60px;height:60px;border-radius:50%;background:${color};display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:700;color:#fff`"
                        >
                            {{ strtoupper($user->initials ?? substr($user->name, 0, 2)) }}
                        </div>
                        <div>
                            <p style="margin:0 0 8px;font-size:12px;font-weight:600;color:#8c8c8a;text-transform:uppercase;letter-spacing:.05em">Avatar Color</p>
                            <div style="display:flex;gap:8px;flex-wrap:wrap">
                                @foreach(['#D97757','#6366f1','#0ea5e9','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899'] as $swatch)
                                    <button
                                        type="button"
                                        @click="color = '{{ $swatch }}'; $el.closest('[x-data]').querySelector('input[name=color]').value = '{{ $swatch }}'"
                                        :style="`width:26px;height:26px;border-radius:50%;background:{{ $swatch }};border:2px solid ${color === '{{ $swatch }}' ? '#141413' : 'transparent'};cursor:pointer`"
                                    ></button>
                                @endforeach
                            </div>
                            <input type="hidden" name="color" :value="color" id="color-input">
                        </div>
                    </div>

                    <form method="POST" action="{{ route('profile.update') }}" id="profile-form">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="color" id="color-hidden" value="{{ $user->color }}">

                        <div style="display:grid;grid-template-columns:1fr 90px;gap:12px;margin-bottom:16px">
                            <div>
                                <label style="display:block;font-size:12px;font-weight:600;color:#5c5c5a;text-transform:uppercase;letter-spacing:.05em;margin-bottom:5px">Name</label>
                                <input type="text" name="name" value="{{ old('name', $user->name) }}"
                                    style="width:100%;padding:9px 12px;border:1px solid #e5e4df;border-radius:8px;background:#F5F4EF;font-size:14px;color:#141413;outline:none;box-sizing:border-box">
                            </div>
                            <div>
                                <label style="display:block;font-size:12px;font-weight:600;color:#5c5c5a;text-transform:uppercase;letter-spacing:.05em;margin-bottom:5px">Initials</label>
                                <input type="text" name="initials" value="{{ old('initials', $user->initials) }}" maxlength="4"
                                    style="width:100%;padding:9px 12px;border:1px solid #e5e4df;border-radius:8px;background:#F5F4EF;font-size:14px;color:#141413;outline:none;box-sizing:border-box;text-transform:uppercase">
                            </div>
                        </div>

                        <div style="margin-bottom:16px">
                            <label style="display:block;font-size:12px;font-weight:600;color:#5c5c5a;text-transform:uppercase;letter-spacing:.05em;margin-bottom:5px">Email</label>
                            <input type="email" value="{{ $user->email }}" disabled
                                style="width:100%;padding:9px 12px;border:1px solid #eeeee9;border-radius:8px;background:#faf9f5;font-size:14px;color:#8c8c8a;box-sizing:border-box">
                        </div>

                        <div style="margin-bottom:20px;display:flex;align-items:center;gap:10px">
                            <span style="font-size:12px;font-weight:600;color:#5c5c5a;text-transform:uppercase;letter-spacing:.05em">Role</span>
                            <span style="display:inline-flex;align-items:center;padding:3px 10px;border-radius:999px;background:#fdf3ee;font-size:12px;font-weight:600;color:#D97757">
                                {{ $user->roleModel?->name ?? 'No role' }}
                            </span>
                        </div>

                        <div style="margin-bottom:20px;font-size:12px;color:#8c8c8a">
                            Member since {{ $user->created_at->format('M j, Y') }}
                        </div>

                        <button type="submit" onclick="document.getElementById('color-hidden').value = document.querySelector('[name=color]').value"
                            style="padding:9px 20px;background:#D97757;color:#fff;font-size:14px;font-weight:600;border:none;border-radius:8px;cursor:pointer">
                            Save changes
                        </button>
                    </form>
                </div>

                {{-- Stats Grid --}}
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px">
                    @php
                        $stats = [
                            ['label' => 'Tasks completed', 'value' => $tasksCompletedAllTime, 'sub' => 'all time'],
                            ['label' => 'This month', 'value' => $tasksCompletedThisMonth, 'sub' => now()->format('M Y')],
                            ['label' => 'Active now', 'value' => $currentActiveTasks, 'sub' => 'in progress'],
                            ['label' => 'Avg. weight', 'value' => $avgWeight, 'sub' => 'per task'],
                            ['label' => 'Sprints', 'value' => $sprintsParticipated, 'sub' => 'participated'],
                            ['label' => 'Top project', 'value' => $mostActiveProject, 'sub' => 'most completed'],
                        ];
                    @endphp
                    @foreach($stats as $stat)
                        <div style="background:#fff;border:1px solid #e5e4df;border-radius:12px;padding:18px 16px">
                            <p style="margin:0 0 4px;font-size:11px;font-weight:600;color:#8c8c8a;text-transform:uppercase;letter-spacing:.05em">{{ $stat['label'] }}</p>
                            <p style="margin:0 0 2px;font-size:22px;font-weight:700;color:#141413;line-height:1.1">{{ $stat['value'] }}</p>
                            <p style="margin:0;font-size:11px;color:#8c8c8a">{{ $stat['sub'] }}</p>
                        </div>
                    @endforeach
                </div>

                {{-- Change Password Card --}}
                <div x-data="{ open: false }" style="background:#fff;border:1px solid #e5e4df;border-radius:14px;padding:28px">
                    <button @click="open = !open" style="display:flex;align-items:center;justify-content:space-between;width:100%;background:none;border:none;cursor:pointer;padding:0">
                        <span style="font-size:16px;font-weight:700;color:#141413">Change Password</span>
                        <svg width="16" height="16" :style="open ? 'transform:rotate(180deg)' : ''" style="flex-shrink:0;color:#8c8c8a;transition:transform .2s" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>

                    <div x-show="open" x-cloak style="margin-top:20px">
                        @if($errors->has('current_password') || $errors->has('password'))
                            <div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;padding:10px 14px;margin-bottom:16px;font-size:13px;color:#dc2626">
                                {{ $errors->first('current_password') ?: $errors->first('password') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('profile.password') }}">
                            @csrf
                            @method('PATCH')

                            <div style="margin-bottom:12px">
                                <label style="display:block;font-size:12px;font-weight:600;color:#5c5c5a;text-transform:uppercase;letter-spacing:.05em;margin-bottom:5px">Current Password</label>
                                <input type="password" name="current_password"
                                    style="width:100%;padding:9px 12px;border:1px solid #e5e4df;border-radius:8px;background:#F5F4EF;font-size:14px;color:#141413;outline:none;box-sizing:border-box">
                            </div>
                            <div style="margin-bottom:12px">
                                <label style="display:block;font-size:12px;font-weight:600;color:#5c5c5a;text-transform:uppercase;letter-spacing:.05em;margin-bottom:5px">New Password</label>
                                <input type="password" name="password"
                                    style="width:100%;padding:9px 12px;border:1px solid #e5e4df;border-radius:8px;background:#F5F4EF;font-size:14px;color:#141413;outline:none;box-sizing:border-box">
                            </div>
                            <div style="margin-bottom:20px">
                                <label style="display:block;font-size:12px;font-weight:600;color:#5c5c5a;text-transform:uppercase;letter-spacing:.05em;margin-bottom:5px">Confirm New Password</label>
                                <input type="password" name="password_confirmation"
                                    style="width:100%;padding:9px 12px;border:1px solid #e5e4df;border-radius:8px;background:#F5F4EF;font-size:14px;color:#141413;outline:none;box-sizing:border-box">
                            </div>
                            <button type="submit" style="padding:9px 20px;background:#141413;color:#fff;font-size:14px;font-weight:600;border:none;border-radius:8px;cursor:pointer">
                                Update password
                            </button>
                        </form>
                    </div>
                </div>

            </div>

            {{-- ═══════════════════════════════════════════════════════════════
                 RIGHT COLUMN
            ═══════════════════════════════════════════════════════════════════ --}}
            <div style="display:flex;flex-direction:column;gap:20px">

                {{-- Two-Factor Authentication Card --}}
                <div style="background:#fff;border:1px solid #e5e4df;border-radius:14px;padding:28px">
                    <h2 style="margin:0 0 6px;font-size:16px;font-weight:700;color:#141413">Two-Factor Authentication</h2>

                    @if($user->isTwoFactorEnabled())
                        <p style="margin:0 0 16px;font-size:13px;color:#5c5c5a">2FA is <strong style="color:#16a34a">enabled</strong> on your account.</p>

                        @if(session('success') && str_contains(session('success'), 'Two-factor'))
                            <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:8px;padding:10px 14px;margin-bottom:12px;font-size:13px;color:#166534">{{ session('success') }}</div>
                        @endif

                        {{-- Regenerate recovery codes --}}
                        <form x-data="{ open: false }" method="POST" action="{{ route('2fa.regenerate-codes') }}" style="margin-bottom:12px">
                            @csrf
                            <button type="button" @click="open = !open" style="font-size:13px;color:#D97757;background:none;border:none;cursor:pointer;padding:0;margin-bottom:8px">Regenerate recovery codes</button>
                            <div x-show="open" x-cloak style="margin-top:8px">
                                <input type="password" name="password" placeholder="Confirm password"
                                    style="width:100%;padding:8px 12px;border:1px solid #e5e4df;border-radius:8px;background:#F5F4EF;font-size:13px;color:#141413;outline:none;box-sizing:border-box;margin-bottom:8px">
                                <button type="submit" style="padding:7px 16px;background:#141413;color:#fff;font-size:13px;border:none;border-radius:8px;cursor:pointer">Generate new codes</button>
                            </div>
                        </form>

                        {{-- Disable 2FA --}}
                        @if(!$user->requiresTwoFactor())
                            <form x-data="{ open: false }" method="POST" action="{{ route('2fa.disable') }}">
                                @csrf
                                <button type="button" @click="open = !open" style="font-size:13px;color:#dc2626;background:none;border:none;cursor:pointer;padding:0;margin-bottom:8px">Disable 2FA</button>
                                <div x-show="open" x-cloak style="margin-top:8px">
                                    <input type="password" name="password" placeholder="Confirm password"
                                        style="width:100%;padding:8px 12px;border:1px solid #e5e4df;border-radius:8px;background:#F5F4EF;font-size:13px;color:#141413;outline:none;box-sizing:border-box;margin-bottom:8px">
                                    <button type="submit" style="padding:7px 16px;background:#ef4444;color:#fff;font-size:13px;border:none;border-radius:8px;cursor:pointer">Disable</button>
                                </div>
                            </form>
                        @else
                            <p style="font-size:12px;color:#8c8c8a">2FA is required for your role and cannot be disabled.</p>
                        @endif

                    @elseif($user->requiresTwoFactor())
                        <div style="background:#fef9ec;border:1px solid #fcd34d;border-radius:10px;padding:14px;margin-bottom:16px">
                            <p style="margin:0;font-size:13px;color:#92400e">Your role requires two-factor authentication. Please set it up to continue accessing the system.</p>
                        </div>
                        <a href="{{ route('2fa.setup') }}" style="display:inline-flex;align-items:center;gap:6px;padding:9px 20px;background:#D97757;color:#fff;font-size:13px;font-weight:600;border-radius:8px;text-decoration:none">
                            Set up 2FA
                        </a>
                    @else
                        <p style="margin:0 0 16px;font-size:13px;color:#5c5c5a">Enhance your account security with an authenticator app.</p>
                        <a href="{{ route('2fa.setup') }}" style="display:inline-flex;align-items:center;gap:6px;padding:9px 20px;background:#D97757;color:#fff;font-size:13px;font-weight:600;border-radius:8px;text-decoration:none">
                            Enable 2FA
                        </a>
                    @endif
                </div>

                {{-- Trusted Devices Card --}}
                <div style="background:#fff;border:1px solid #e5e4df;border-radius:14px;padding:28px">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
                        <h2 style="margin:0;font-size:16px;font-weight:700;color:#141413">Trusted Devices</h2>
                        @if($user->devices->count() > 0)
                            <form method="POST" action="{{ route('profile.devices.revoke-all') }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" style="font-size:12px;color:#dc2626;background:none;border:none;cursor:pointer;padding:0">Revoke all</button>
                            </form>
                        @endif
                    </div>

                    @forelse($user->devices as $device)
                        <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid #eeeee9">
                            <div>
                                <p style="margin:0 0 2px;font-size:13px;font-weight:600;color:#141413">{{ $device->name }}</p>
                                <p style="margin:0;font-size:11px;color:#8c8c8a">
                                    {{ $device->ip_address }}
                                    @if($device->last_used_at)
                                        · Last used {{ $device->last_used_at->diffForHumans() }}
                                    @endif
                                    @if($device->trusted)
                                        · <span style="color:#16a34a">Trusted</span>
                                    @endif
                                </p>
                            </div>
                            <form method="POST" action="{{ route('profile.devices.revoke', $device) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" style="font-size:12px;color:#dc2626;background:none;border:none;cursor:pointer;padding:0">Revoke</button>
                            </form>
                        </div>
                    @empty
                        <p style="margin:0;font-size:13px;color:#8c8c8a">No trusted devices.</p>
                    @endforelse
                </div>

                {{-- Login History Card --}}
                <div style="background:#fff;border:1px solid #e5e4df;border-radius:14px;padding:28px">
                    <h2 style="margin:0 0 16px;font-size:16px;font-weight:700;color:#141413">Login History</h2>

                    @forelse($loginHistory as $entry)
                        <div style="display:flex;align-items:flex-start;justify-content:space-between;padding:10px 0;border-bottom:1px solid #eeeee9">
                            <div>
                                <p style="margin:0 0 2px;font-size:13px;color:#141413">{{ $entry->device ?? 'Unknown device' }}</p>
                                <p style="margin:0;font-size:11px;color:#8c8c8a">{{ $entry->ip_address }} · {{ $entry->created_at->format('M j, Y H:i') }}</p>
                            </div>
                            @if($entry->status === 'success')
                                <span style="font-size:11px;font-weight:600;color:#16a34a;background:#f0fdf4;border:1px solid #86efac;border-radius:999px;padding:2px 8px">Success</span>
                            @else
                                <span style="font-size:11px;font-weight:600;color:#dc2626;background:#fef2f2;border:1px solid #fca5a5;border-radius:999px;padding:2px 8px">Failed</span>
                            @endif
                        </div>
                    @empty
                        <p style="margin:0;font-size:13px;color:#8c8c8a">No login history.</p>
                    @endforelse
                </div>

                {{-- Notification Preferences Card --}}
                <div
                    x-data="{
                        prefs: {{ json_encode($user->notification_preferences ?? ['email_enabled' => true]) }},
                        save() {
                            fetch('{{ route('profile.notifications') }}', {
                                method: 'PATCH',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                body: JSON.stringify({ notification_preferences: this.prefs })
                            });
                        }
                    }"
                    style="background:#fff;border:1px solid #e5e4df;border-radius:14px;padding:28px"
                >
                    <h2 style="margin:0 0 16px;font-size:16px;font-weight:700;color:#141413">Notification Preferences</h2>

                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
                        <input type="checkbox" x-model="prefs.email_enabled" @change="save()" style="accent-color:#D97757;width:16px;height:16px">
                        <span style="font-size:13px;color:#141413">Email notifications</span>
                    </label>
                </div>

            </div>
        </div>
    </div>
</x-layouts.app>
