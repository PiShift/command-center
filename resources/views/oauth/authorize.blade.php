<x-layouts.auth>
    <div class="w-full max-w-md mx-auto px-4">
        <div class="flex justify-center mb-8">
            <img src="/images/logo.svg" alt="PiShift" class="h-9 w-auto">
        </div>

        <section class="bg-card border border-line rounded-2xl shadow-card p-8">
            <h1 class="text-2xl font-semibold text-ink leading-tight">Consent Required</h1>
            <p class="mt-1 text-sm text-dim leading-[1.5]">
                Claude MCP wants to access your Command Center.
            </p>

            <div class="mt-6 rounded-xl border border-line bg-surface p-4">
                <p class="text-xxs font-bold uppercase tracking-wider text-muted">
                    Requested Access
                </p>
                <p class="mt-2 text-sm text-dim leading-[1.5]">
                    Authorize this client to mint a personal access token on your behalf.
                </p>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3">
                <form method="POST" action="{{ route('oauth.authorize.handle') }}">
                    @csrf
                    <input type="hidden" name="action" value="deny">
                    <button
                        type="submit"
                        class="cursor-pointer rounded-md border border-line bg-surface px-4 py-2 text-sm font-medium text-ink transition-colors duration-150 ease-in-out hover:border-muted hover:bg-hairline"
                    >
                        Deny
                    </button>
                </form>

                <form method="POST" action="{{ route('oauth.authorize.handle') }}">
                    @csrf
                    <input type="hidden" name="action" value="allow">
                    <button
                        type="submit"
                        class="cursor-pointer rounded-md bg-accent px-4 py-2 text-sm font-medium text-white transition-colors duration-150 ease-in-out hover:bg-accent-hover"
                    >
                        Allow
                    </button>
                </form>
            </div>
        </section>
    </div>
</x-layouts.auth>
