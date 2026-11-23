@extends('layouts.app')

@section('pageTitle', __('labels.new_concern'))

@section('content')
    <div class="mx-auto max-w-3xl">
        <h1 class="font-serif text-3xl font-semibold text-spruce-800">{{ __('labels.submit_concern') }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ __('labels.register_subtitle') }}</p>

        <section class="mt-6 rounded-xl border border-sage-200 bg-sage-50/80 p-5">
            <h2 class="font-serif text-lg font-semibold text-spruce-800">{{ __('labels.concern_templates') }}</h2>
            <p class="mt-1 text-xs text-slate-500">{{ __('labels.concern_templates_help') }}</p>
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach ($templates as $template)
                    <button type="button"
                            data-concern-template
                            data-template-subject="{{ $template['subject'] }}"
                            data-template-description="{{ $template['description'] }}"
                            class="rounded-full border border-spruce-200 bg-white px-3 py-1.5 text-xs font-semibold text-spruce-700 shadow-sm transition hover:border-spruce-400 hover:bg-spruce-50">
                        {{ $template['label'] }}
                    </button>
                @endforeach
            </div>
        </section>

        <form method="POST" action="{{ route('concerns.store') }}" enctype="multipart/form-data" class="mt-6 space-y-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf

            <div>
                <label for="category_id" class="block text-sm font-medium text-slate-700">{{ __('labels.category') }}</label>
                <select id="category_id" name="category_id" required class="mt-1.5 w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-spruce-500 focus:ring-spruce-500">
                    <option value="">{{ __('labels.category') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id') == $category->id) @if ($category->is_urgent) data-urgent="1" @endif>
                            {{ $category->name }}@if ($category->is_urgent) &mdash; {{ __('labels.priority_urgent') }}@endif
                        </option>
                    @endforeach
                </select>
                <p id="urgent-category-warning" class="mt-2 hidden rounded-lg border border-clay-200 bg-clay-50 px-3 py-2 text-xs leading-relaxed text-clay-800">
                    {{ __('labels.urgent_category_warning') }}
                </p>
            </div>

            <div>
                <label for="recipient_id" class="block text-sm font-medium text-slate-700">{{ __('labels.recipient') }}</label>
                <select id="recipient_id" name="recipient_id" class="mt-1.5 w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-spruce-500 focus:ring-spruce-500">
                    <option value="">{{ __('labels.unassigned') }}</option>
                    @foreach ($teachers as $teacher)
                        <option value="{{ $teacher->id }}" @selected(old('recipient_id') == $teacher->id)>{{ $teacher->name }}</option>
                    @endforeach
                </select>
                <p class="mt-1.5 text-xs text-slate-500">{{ __('labels.recipient_help') }}</p>
            </div>

            <div>
                <label for="subject" class="block text-sm font-medium text-slate-700">{{ __('labels.subject') }}</label>
                <input id="subject" name="subject" type="text" value="{{ old('subject') }}" maxlength="150" required autocomplete="off" class="mt-1.5 w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-spruce-500 focus:ring-spruce-500">
                <div data-similar-concerns
                     data-url="{{ route('concerns.suggestions') }}"
                     data-no-suggestions="{{ __('labels.no_similar_concerns') }}"
                     class="mt-3 hidden rounded-lg border border-sage-200 bg-sage-50/80 p-4">
                    <p class="text-sm font-semibold text-spruce-800">{{ __('labels.similar_concerns') }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ __('labels.similar_concerns_help') }}</p>
                    <ul data-similar-concerns-list class="mt-3 space-y-2 text-sm"></ul>
                </div>
            </div>

            <div>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <label for="description" class="block text-sm font-medium text-slate-700">{{ __('labels.description') }}</label>
                    <span data-character-counter-output
                          data-counter-label="{{ __('labels.characters') }}"
                          aria-live="polite"
                          class="font-mono text-xs text-slate-500">0 / 5,000 {{ __('labels.characters') }}</span>
                </div>
                <textarea id="description"
                          name="description"
                          rows="6"
                          maxlength="5000"
                          required
                          data-character-counter-input
                          class="mt-1.5 w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-spruce-500 focus:ring-spruce-500">{{ old('description') }}</textarea>
            </div>

            <div>
                <label for="priority" class="block text-sm font-medium text-slate-700">{{ __('labels.priority') }}</label>
                <select id="priority" name="priority" required class="mt-1.5 w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-spruce-500 focus:ring-spruce-500">
                    @foreach (['low' => __('labels.priority_low'), 'normal' => __('labels.priority_normal'), 'high' => __('labels.priority_high'), 'urgent' => __('labels.priority_urgent')] as $value => $label)
                        <option value="{{ $value }}" @selected(old('priority', 'normal') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="attachments" class="block text-sm font-medium text-slate-700">{{ __('labels.attachments') }}</label>
                <input id="attachments" name="attachments[]" type="file" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx,.txt" class="mt-1.5 block w-full text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-spruce-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-spruce-700 hover:file:bg-spruce-100">
                <p class="mt-1.5 text-xs text-slate-500">{{ __('labels.attachment_help') }}</p>
            </div>

            <div data-anonymous-panel class="rounded-lg border border-slate-200 bg-sage-50 p-4">
                <div class="flex items-start gap-3">
                    <input id="is_anonymous" name="is_anonymous" type="checkbox" value="1" @checked(old('is_anonymous')) data-anonymous-toggle class="mt-0.5 h-4 w-4 rounded border-slate-300 text-spruce-600 focus:ring-spruce-500">
                    <div>
                        <label for="is_anonymous" class="text-sm font-medium text-slate-800">{{ __('labels.anonymous') }}</label>
                        <p data-anonymous-hint class="mt-1 rounded-md border border-transparent px-2 py-1 text-xs leading-relaxed text-slate-600">
                            {{ __('labels.anonymous_help') }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="rounded-lg bg-spruce-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-spruce-700">
                    {{ __('labels.submit') }}
                </button>
                <a href="{{ route('concerns.index') }}" class="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    {{ __('labels.cancel') }}
                </a>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            const categorySelect = document.getElementById('category_id');
            const urgentWarning = document.getElementById('urgent-category-warning');

            const syncUrgentWarning = () => {
                const selected = categorySelect?.selectedOptions[0];
                urgentWarning?.classList.toggle('hidden', selected?.dataset.urgent !== '1');
            };

            categorySelect?.addEventListener('change', syncUrgentWarning);
            syncUrgentWarning();
        </script>
    @endpush
@endsection
