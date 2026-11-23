@extends('layouts.app')

@section('pageTitle', __('labels.create') . ' ' . __('labels.users'))

@section('content')
    <div class="mx-auto max-w-3xl">
        <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-spruce-700 transition hover:text-spruce-800">{{ __('labels.back') }}</a>
        <div class="mt-3 rounded-2xl border border-slate-200/80 bg-white/90 p-6 shadow-sm backdrop-blur sm:p-8">
            <div class="mb-6">
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900">{{ __('labels.create') }} {{ __('labels.users') }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ __('labels.users') }}</p>
            </div>

            <form method="POST" action="{{ route('admin.users.store') }}" class="grid gap-5 sm:grid-cols-2">
                @csrf

                <div class="sm:col-span-2">
                    <label for="name" class="block text-sm font-medium text-slate-700">{{ __('labels.name') }}</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required class="mt-1.5 w-full rounded-xl border-slate-300 px-3 py-2.5 text-sm shadow-sm transition focus:border-spruce-500 focus:ring-spruce-500">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700">{{ __('labels.email') }}</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required class="mt-1.5 w-full rounded-xl border-slate-300 px-3 py-2.5 text-sm shadow-sm transition focus:border-spruce-500 focus:ring-spruce-500">
                </div>

                <div>
                    <label for="role_id" class="block text-sm font-medium text-slate-700">{{ __('labels.role') }}</label>
                    <select id="role_id" name="role_id" required class="mt-1.5 w-full rounded-xl border-slate-300 px-3 py-2.5 text-sm shadow-sm transition focus:border-spruce-500 focus:ring-spruce-500">
                        <option value="">{{ __('labels.role') }}</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" data-role-name="{{ $role->name }}" @selected(old('role_id') == $role->id)>{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div id="student-number-field" class="transition-all duration-200">
                    <label for="student_number" class="block text-sm font-medium text-slate-700">{{ __('labels.student_number') }}</label>
                    <input id="student_number" name="student_number" type="text" value="{{ old('student_number') }}" class="mt-1.5 w-full rounded-xl border-slate-300 px-3 py-2.5 text-sm shadow-sm transition focus:border-spruce-500 focus:ring-spruce-500">
                    <p class="mt-1 text-xs text-slate-500">{{ __('labels.student_number') }}</p>
                </div>

                <div>
                    <label for="department" class="block text-sm font-medium text-slate-700">{{ __('labels.department') }}</label>
                    <input id="department" name="department" type="text" value="{{ old('department') }}" class="mt-1.5 w-full rounded-xl border-slate-300 px-3 py-2.5 text-sm shadow-sm transition focus:border-spruce-500 focus:ring-spruce-500">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700">{{ __('labels.password') }}</label>
                    <input id="password" name="password" type="password" required autocomplete="new-password" class="mt-1.5 w-full rounded-xl border-slate-300 px-3 py-2.5 text-sm shadow-sm transition focus:border-spruce-500 focus:ring-spruce-500">
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-slate-700">{{ __('labels.confirm_password') }}</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="mt-1.5 w-full rounded-xl border-slate-300 px-3 py-2.5 text-sm shadow-sm transition focus:border-spruce-500 focus:ring-spruce-500">
                </div>

                <div class="sm:col-span-2 flex flex-wrap gap-6 rounded-xl bg-slate-50 px-4 py-3">
                    <div class="flex items-center gap-2">
                        <input type="hidden" name="is_active" value="0">
                        <input id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', true)) class="h-4 w-4 rounded border-slate-300 text-spruce-600 focus:ring-spruce-500">
                        <label for="is_active" class="text-sm text-slate-700">{{ __('labels.active') }}</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="hidden" name="email_opt_in" value="0">
                        <input id="email_opt_in" name="email_opt_in" type="checkbox" value="1" @checked(old('email_opt_in', true)) class="h-4 w-4 rounded border-slate-300 text-spruce-600 focus:ring-spruce-500">
                        <label for="email_opt_in" class="text-sm text-slate-700">{{ __('labels.email_opt_in') }}</label>
                    </div>
                </div>

                <div class="sm:col-span-2 flex flex-wrap gap-3 pt-2">
                    <button type="submit" class="rounded-xl bg-spruce-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-spruce-700 hover:shadow">{{ __('labels.create') }}</button>
                    <a href="{{ route('admin.users.index') }}" class="rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">{{ __('labels.cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (() => {
        const roleSelect = document.getElementById('role_id');
        const field = document.getElementById('student-number-field');
        const input = document.getElementById('student_number');
        if (!roleSelect || !field || !input) return;

        const syncStudentNumber = () => {
            const selected = roleSelect.options[roleSelect.selectedIndex];
            const roleName = (selected?.dataset.roleName || '').toLowerCase();
            const isStudent = roleName === 'student';

            field.classList.toggle('opacity-50', !isStudent);
            field.classList.toggle('pointer-events-none', !isStudent);
            input.disabled = !isStudent;
            input.required = isStudent;
            input.placeholder = isStudent ? '' : '—';

            if (!isStudent) {
                input.value = '';
            }
        };

        roleSelect.addEventListener('change', syncStudentNumber);
        syncStudentNumber();
    })();
</script>
@endpush
