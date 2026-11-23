<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="session-lifetime" content="{{ config('session.lifetime') * 60 }}">
    <script>
        document.documentElement.classList.add('js');
    </script>

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
    <div class="flex min-h-screen">
        <aside class="no-print hidden w-64 shrink-0 flex-col border-r border-spruce-100 bg-sage-50/80 lg:flex">
            <div class="flex h-16 items-center gap-3 border-b border-spruce-100 px-6">
                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-spruce-500 font-serif text-base font-semibold text-white" aria-hidden="true">S</span>
                <div>
                    <a href="{{ auth()->check() ? route('dashboard') : route('login.show') }}" class="font-serif text-lg font-semibold tracking-tight text-spruce-700">
                        SCFMS
                    </a>
                    <p class="text-[10px] uppercase tracking-[0.18em] text-sage-600">Guidance office</p>
                </div>
            </div>
            <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4" aria-label="{{ __('labels.dashboard') }}">
                @include('partials.nav_links')
            </nav>
            <div class="border-t border-spruce-100 px-6 py-4 text-xs text-sage-600">
                SCFMS &copy; {{ date('Y') }}
            </div>
        </aside>

        <div class="flex min-h-screen min-w-0 flex-1 flex-col">
            <header class="no-print sticky top-0 z-20 flex h-16 shrink-0 items-center justify-between gap-3 border-b border-spruce-100 bg-mist/90 px-4 backdrop-blur sm:px-6">
                <div class="flex min-w-0 items-center gap-3">
                    <a href="{{ auth()->check() ? route('dashboard') : route('login.show') }}" class="font-serif text-base font-semibold text-spruce-700 lg:hidden">
                        SCFMS
                    </a>
                    <span class="hidden truncate text-sm text-slate-500 sm:inline">
                        {{ $pageTitle ?? '' }}
                    </span>
                </div>

                <div class="flex items-center gap-2 sm:gap-3">
                    <form method="POST" action="{{ route('language.switch') }}" class="flex items-center gap-1.5">
                        @csrf
                        <label class="sr-only" for="locale">{{ __('labels.language') }}</label>
                        <select id="locale" name="locale" class="rounded-md border-spruce-100 bg-white py-1.5 pl-2 pr-7 text-xs text-slate-700 shadow-sm focus:border-spruce-500 focus:ring-spruce-500">
                            <option value="en" @selected(app()->getLocale() === 'en')>{{ __('labels.english') }}</option>
                            <option value="fil" @selected(app()->getLocale() === 'fil')>{{ __('labels.filipino') }}</option>
                        </select>
                        <button type="submit" class="rounded-md bg-sage-100 px-2 py-1.5 text-xs font-medium text-spruce-700 hover:bg-sage-200">
                            {{ __('labels.save') }}
                        </button>
                    </form>

                    @auth
                        <a href="{{ route('notifications.index') }}" class="relative inline-flex items-center rounded-md border border-spruce-100 bg-white px-2.5 py-1.5 text-sm hover:bg-sage-50" aria-label="{{ __('labels.notifications') }}">
                            <span @class(['bell-swing' => ($unreadNotificationCount ?? 0) > 0]) aria-hidden="true">&#128276;</span>
                            @if (($unreadNotificationCount ?? 0) > 0)
                                <span class="ml-1 rounded-full bg-clay-500 px-1.5 py-0.5 text-xs font-semibold text-white">
                                    {{ $unreadNotificationCount }}
                                </span>
                            @endif
                        </a>

                        <a href="{{ route('profile.edit') }}" class="rounded-md border border-spruce-100 bg-white px-2.5 py-1.5 text-xs font-medium text-spruce-700 hover:bg-sage-50">
                            {{ __('labels.profile') }}
                        </a>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="rounded-md border border-spruce-100 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-sage-50">
                                {{ __('labels.logout') }}
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login.show') }}" class="rounded-md px-2.5 py-1.5 text-xs font-medium text-spruce-700 hover:bg-sage-100">
                            {{ __('labels.login') }}
                        </a>
                        <a href="{{ route('register.show') }}" class="rounded-md bg-spruce-600 px-2.5 py-1.5 text-xs font-medium text-white hover:bg-spruce-700">
                            {{ __('labels.register') }}
                        </a>
                    @endauth
                </div>
            </header>

            @auth
                <nav class="no-print flex gap-1 overflow-x-auto border-b border-spruce-100 bg-white px-4 py-2 lg:hidden" aria-label="{{ __('labels.dashboard') }}">
                    @include('partials.nav_links')
                </nav>
            @endauth

            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                <div class="page-enter mx-auto w-full max-w-7xl space-y-6">
                    @include('partials.alerts')

                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    @auth
        @include('partials.session_timeout_modal')
    @endauth
    @include('partials.confirm_modal')

    @stack('scripts')
</body>
</html>
