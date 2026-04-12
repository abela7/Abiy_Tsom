@extends('layouts.admin')

@section('title', __('app.fasika_quiz_admin_page_title'))

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <a href="{{ route('admin.fasika-greetings.index') }}"
               class="mb-2 inline-flex text-sm font-medium text-muted-text hover:text-primary">
                {{ __('app.fasika_quiz_admin_back_greetings') }}
            </a>
            <h1 class="text-2xl font-bold text-primary">{{ __('app.fasika_quiz_admin_page_title') }}</h1>
            <p class="mt-1 text-sm text-muted-text">{{ __('app.fasika_quiz_admin_page_subtitle') }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('admin.fasika-quiz.submissions') }}"
               class="inline-flex items-center justify-center rounded-xl border border-border bg-card px-4 py-2 text-sm font-semibold text-primary transition hover:bg-muted/50">
                {{ __('app.fasika_quiz_admin_view_submissions') }}
            </a>
            <a href="{{ route('admin.fasika-quiz.create') }}"
               class="inline-flex items-center justify-center rounded-xl bg-accent px-4 py-2 text-sm font-semibold text-on-accent shadow transition hover:opacity-90">
                {{ __('app.fasika_quiz_admin_add_question') }}
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm font-medium text-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-border bg-card p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-muted-text">{{ __('app.fasika_quiz_admin_stat_total_questions') }}</p>
            <p class="mt-3 text-3xl font-black text-primary">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="rounded-2xl border border-border bg-card p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-muted-text">{{ __('app.fasika_quiz_admin_stat_active_questions') }}</p>
            <p class="mt-3 text-3xl font-black text-primary">{{ number_format($stats['active']) }}</p>
        </div>
        <div class="rounded-2xl border border-border bg-card p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-muted-text">{{ __('app.fasika_quiz_admin_stat_attempts') }}</p>
            <p class="mt-3 text-3xl font-black text-primary">{{ number_format($stats['submissions']) }}</p>
        </div>
        <div class="rounded-2xl border border-border bg-card p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-muted-text">{{ __('app.fasika_quiz_admin_stat_avg_score') }}</p>
            <p class="mt-3 text-3xl font-black text-primary">{{ $stats['avg_score'] }}<span class="text-base font-medium text-muted-text">/30</span></p>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-border bg-card">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-border text-sm">
                <thead class="bg-surface/70">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-secondary">{{ __('app.fasika_quiz_admin_col_sort') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-secondary">{{ __('app.fasika_quiz_admin_col_question') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-secondary">{{ __('app.fasika_quiz_admin_col_difficulty') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-secondary">{{ __('app.fasika_quiz_admin_col_points') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-secondary">{{ __('app.fasika_quiz_admin_col_correct') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-secondary">{{ __('app.fasika_quiz_admin_col_status') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-secondary">{{ __('app.fasika_quiz_admin_col_actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse($questions as $q)
                        <tr class="align-top {{ $q->is_active ? '' : 'opacity-50' }}">
                            <td class="px-4 py-3 text-muted-text">{{ $q->sort_order }}</td>
                            <td class="px-4 py-3 max-w-xs text-primary">
                                <p class="line-clamp-2 leading-relaxed">{{ $q->question }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold
                                    {{ $q->difficulty === 'easy' ? 'bg-green-500/15 text-green-400' : ($q->difficulty === 'medium' ? 'bg-yellow-500/15 text-yellow-400' : 'bg-red-500/15 text-red-400') }}">
                                    {{ $q->difficultyLabel() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 font-semibold text-primary">{{ $q->points }}</td>
                            <td class="px-4 py-3 font-bold text-accent uppercase">{{ $q->correct_option }}</td>
                            <td class="px-4 py-3">
                                <form method="POST" action="{{ route('admin.fasika-quiz.toggle', $q) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit"
                                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold transition
                                                {{ $q->is_active ? 'bg-success/15 text-success hover:bg-success/25' : 'bg-muted text-muted-text hover:bg-muted/70' }}">
                                        {{ $q->is_active ? __('app.fasika_quiz_admin_status_active') : __('app.fasika_quiz_admin_status_inactive') }}
                                    </button>
                                </form>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.fasika-quiz.edit', $q) }}"
                                       class="inline-flex items-center rounded-lg border border-accent/30 bg-accent/10 px-3 py-1.5 text-xs font-semibold text-accent transition hover:bg-accent/15">
                                        {{ __('app.fasika_quiz_admin_edit') }}
                                    </a>
                                    <form method="POST" action="{{ route('admin.fasika-quiz.destroy', $q) }}"
                                          onsubmit="return confirm(@json(__('app.fasika_quiz_admin_delete_confirm')))">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex items-center rounded-lg border border-error/30 bg-error/10 px-3 py-1.5 text-xs font-semibold text-error transition hover:bg-error/15">
                                            {{ __('app.fasika_quiz_admin_delete') }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-muted-text">{{ __('app.fasika_quiz_admin_empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
