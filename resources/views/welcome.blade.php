<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'SupportDesk') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-950 font-sans text-white antialiased">
        <main class="relative isolate min-h-screen overflow-hidden">
            <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_78%_42%,rgba(79,70,229,.5),transparent_35%),linear-gradient(135deg,#0f1b46_0%,#172c70_50%,#111d4c_100%)]"></div>
            <div class="absolute left-1/2 top-20 -z-10 h-80 w-80 -translate-x-1/2 rounded-full bg-indigo-400/10 blur-3xl"></div>

            <header class="mx-auto flex max-w-7xl items-center justify-between px-6 py-7 lg:px-10">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-500 text-sm font-bold shadow-lg shadow-indigo-950/30">ST</span>
                    <div>
                        <p class="text-lg font-bold tracking-tight">SupportDesk</p>
                        <p class="text-[10px] uppercase tracking-[.2em] text-indigo-200">Ticket support</p>
                    </div>
                </div>
                <a href="{{ route('login') }}" class="rounded-lg border border-white/25 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/10">
                    Login Helpdesk
                </a>
            </header>

            <section class="mx-auto grid max-w-7xl items-center gap-14 px-6 pb-20 pt-12 lg:grid-cols-2 lg:px-10 lg:pb-28 lg:pt-20">
                <div class="max-w-xl">
                    <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-indigo-200/20 bg-white/10 px-4 py-2 text-sm text-indigo-100">
                        <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                        Support that keeps work moving
                    </div>
                    <h1 class="text-5xl font-bold leading-[1.05] tracking-tight sm:text-6xl">
                        Resolve every request with <span class="text-indigo-300">clarity.</span>
                    </h1>
                    <p class="mt-6 max-w-lg text-lg leading-8 text-indigo-100/80">
                        Create, assign, manage, and resolve support tickets from one focused workspace built for your team.
                    </p>
                    <div class="mt-9 flex flex-wrap items-center gap-3">
                        <a href="{{ route('login') }}" class="rounded-lg bg-white px-5 py-3 text-sm font-bold text-indigo-950 shadow-xl shadow-indigo-950/20 transition hover:bg-indigo-50">
                            Login Helpdesk <span class="ml-2">→</span>
                        </a>
                        <a href="{{ route('tickets.create') }}" class="rounded-lg border border-white/25 bg-white/10 px-5 py-3 text-sm font-bold text-white transition hover:bg-white/15">
                            Submit ticket <span class="ml-2">→</span>
                        </a>
                    </div>
                    <div class="mt-8 flex flex-wrap gap-x-6 gap-y-2 text-sm text-indigo-100/70">
                        <span>✓ SLA-aware workflows</span>
                        <span>✓ Secure conversations</span>
                        <span>✓ Clear ownership</span>
                    </div>
                </div>

                <div class="relative">
                    <div class="absolute -inset-5 rounded-3xl bg-indigo-400/20 blur-2xl"></div>
                    <div class="relative overflow-hidden rounded-2xl border border-white/20 bg-white/10 p-2 shadow-2xl shadow-slate-950/50 backdrop-blur-sm">
                        <img src="{{ asset('images/dashboard-preview.png') }}" alt="SupportDesk dashboard preview" class="w-full rounded-xl">
                    </div>
                    <div class="absolute -bottom-5 -left-5 hidden rounded-xl border border-emerald-300/30 bg-emerald-400 px-4 py-3 text-sm font-semibold text-emerald-950 shadow-xl sm:block">
                        <span class="mr-2">✓</span> Tickets stay on track
                    </div>
                    <div class="absolute -right-4 -top-5 hidden h-14 w-14 items-center justify-center rounded-2xl bg-indigo-500 text-2xl shadow-xl sm:flex">✦</div>
                </div>
            </section>
        </main>
    </body>
</html>
