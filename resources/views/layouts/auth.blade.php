<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>
        @hasSection('pageTitle')
            @yield('pageTitle') &mdash; {{ config('app.name', 'SCFMS') }}
        @else
            {{ $pageTitle ?? config('app.name', 'SCFMS') }}
        @endif
    </title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-mist text-slate-800 antialiased">
    @hasSection('authArt')
        <div class="min-h-screen lg:grid lg:grid-cols-[1.05fr_0.95fr]">
            <aside class="relative hidden overflow-hidden bg-spruce-600 px-12 py-10 text-white lg:flex lg:flex-col lg:justify-between">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-white/10 font-serif text-lg font-semibold" aria-hidden="true">S</span>
                    <div>
                        <p class="font-serif text-xl font-semibold tracking-tight">SCFMS</p>
                        <p class="text-[11px] uppercase tracking-[0.2em] text-sage-200">Student concern &amp; feedback</p>
                    </div>
                </div>

                @yield('authArt')

                <p class="max-w-sm text-xs leading-relaxed text-sage-200">
                    {{ __('messages.guidance_privacy') }}
                </p>
            </aside>

            <main class="flex min-h-screen items-center justify-center px-4 py-10 sm:px-8">
                <div class="page-enter w-full max-w-md space-y-5">
                    @include('partials.alerts')
                    @yield('content')
                </div>
            </main>
        </div>
    @else
        <main class="flex min-h-screen items-center justify-center px-4 py-10 sm:px-8">
            <div class="page-enter w-full max-w-xl space-y-5">
                @include('partials.alerts')
                @yield('content')
            </div>
        </main>
    @endif

    @stack('scripts')
</body>
</html>
