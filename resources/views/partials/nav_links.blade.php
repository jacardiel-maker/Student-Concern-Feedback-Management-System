@php
    $navLinkClass = 'block border-l-2 border-transparent px-3 py-2 text-sm font-medium transition';
    $navActiveClass = 'border-spruce-500 bg-sage-100 font-semibold text-spruce-700';
    $navIdleClass = 'text-slate-700 hover:border-sage-300 hover:bg-sage-50';
@endphp

@auth
    <a href="{{ route('dashboard') }}" class="{{ $navLinkClass }} {{ request()->routeIs('dashboard') ? $navActiveClass : $navIdleClass }}">
        {{ __('labels.dashboard') }}
    </a>

    @if (auth()->user()->isStudent())
        <a href="{{ route('concerns.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('concerns.*') ? $navActiveClass : $navIdleClass }}">
            {{ __('labels.my_concerns') }}
        </a>
        <a href="{{ route('concerns.create') }}" class="{{ $navLinkClass }} {{ request()->routeIs('concerns.create') ? $navActiveClass : $navIdleClass }}">
            {{ __('labels.submit_concern') }}
        </a>
    @elseif (auth()->user()->isTeacher())
        <a href="{{ route('teacher.assigned.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('teacher.assigned.*') ? $navActiveClass : $navIdleClass }}">
            {{ __('labels.assigned_concerns') }}
        </a>
    @elseif (auth()->user()->isAdmin())
        <a href="{{ route('admin.users.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('admin.users.*') ? $navActiveClass : $navIdleClass }}">
            {{ __('labels.users') }}
        </a>
        <a href="{{ route('admin.categories.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('admin.categories.*') ? $navActiveClass : $navIdleClass }}">
            {{ __('labels.categories') }}
        </a>
        <a href="{{ route('admin.assignments.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('admin.assignments.*') ? $navActiveClass : $navIdleClass }}">
            {{ __('labels.assignments') }}
        </a>
        <a href="{{ route('admin.reports.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('admin.reports.*') ? $navActiveClass : $navIdleClass }}">
            {{ __('labels.reports') }}
        </a>
        <a href="{{ route('admin.audit-logs.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('admin.audit-logs.*') ? $navActiveClass : $navIdleClass }}">
            {{ __('labels.audit_logs') }}
        </a>
        <a href="{{ route('admin.unmask.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('admin.unmask.*') ? $navActiveClass : $navIdleClass }}">
            {{ __('labels.unmask') }}
        </a>
    @endif
@else
    <a href="{{ route('login.show') }}" class="{{ $navLinkClass }} {{ $navIdleClass }}">
        {{ __('labels.login') }}
    </a>
    <a href="{{ route('register.show') }}" class="{{ $navLinkClass }} {{ $navIdleClass }}">
        {{ __('labels.register') }}
    </a>
@endauth
