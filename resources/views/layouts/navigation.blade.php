<nav class="w-64 shrink-0">
    <div class="sticky top-0 z-30 flex h-screen w-64 flex-col border-r border-slate-800 bg-slate-950 text-white">
        <div class="flex items-center justify-between px-6 py-7">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-500 text-sm font-bold">ST</span>
                <span class="text-lg font-semibold tracking-tight">SupportTicket</span>
            </a>
        </div>

        <div class="flex-1 border-t border-slate-800 px-4 pb-5">
            <p class="px-3 pb-3 pt-5 text-xs font-semibold uppercase tracking-wider text-slate-500">Workspace</p>
            <div class="space-y-1">
                <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') || request()->routeIs('admin.dashboard') ? 'bg-indigo-500/20 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium">
                    <span class="text-base">▦</span> Dashboard
                </a>
                <a href="{{ route('tickets.index') }}" class="{{ request()->routeIs('tickets.*') ? 'bg-indigo-500/20 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium">
                    <span class="text-base">▤</span> Tickets
                </a>
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'bg-indigo-500/20 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium">
                        <span class="text-base">♙</span> Users & agents
                    </a>
                @endif
            </div>

        </div>

        <div class="border-t border-slate-800 p-4">
            <p class="truncate px-1 text-xs text-slate-500">Signed in as</p>
            <p class="truncate px-1 pt-1 text-sm font-semibold text-white">{{ auth()->user()->name }}</p>
        </div>
    </div>
</nav>
