@extends('layouts.admin')

@section('title', $question ? __('app.fasika_quiz_admin_form_title_edit') : __('app.fasika_quiz_admin_form_title_new'))

@section('content')
<div class="mx-auto max-w-2xl space-y-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.fasika-quiz.index') }}"
           class="inline-flex items-center text-sm font-medium text-muted-text hover:text-primary">
            {{ __('app.fasika_quiz_admin_form_back') }}
        </a>
        <h1 class="text-2xl font-bold text-primary">{{ $question ? __('app.fasika_quiz_admin_form_title_edit') : __('app.fasika_quiz_admin_form_title_new') }}</h1>
    </div>

    @if($errors->any())
        <div class="rounded-xl border border-error/30 bg-error/10 px-4 py-3 text-sm text-error">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
          action="{{ $question ? route('admin.fasika-quiz.update', $question) : route('admin.fasika-quiz.store') }}"
          class="space-y-5 rounded-2xl border border-border bg-card p-6">
        @csrf
        @if($question) @method('PUT') @endif

        <div>
            <label class="block text-sm font-semibold text-secondary mb-1.5">ጥያቄ</label>
            <textarea name="question" rows="3" required
                      class="w-full rounded-xl border border-border bg-surface px-4 py-3 text-sm text-primary shadow-inner outline-none transition placeholder:text-muted-text focus:border-accent/50 focus:ring-2 focus:ring-accent/20"
                      placeholder="ጥያቄዎን ያስገቡ">{{ old('question', $question?->question) }}</textarea>
        </div>

        @foreach(['a','b','c','d'] as $opt)
        <div>
            <label class="block text-sm font-semibold text-secondary mb-1.5">
                ምርጫ {{ strtoupper($opt) }}
            </label>
            <input type="text" name="option_{{ $opt }}" required
                   value="{{ old('option_'.$opt, $question?->{'option_'.$opt}) }}"
                   class="w-full rounded-xl border border-border bg-surface px-4 py-2.5 text-sm text-primary shadow-inner outline-none transition placeholder:text-muted-text focus:border-accent/50 focus:ring-2 focus:ring-accent/20"
                   placeholder="ምርጫ {{ strtoupper($opt) }}">
        </div>
        @endforeach

        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label class="block text-sm font-semibold text-secondary mb-1.5">ትክክለኛ መልስ</label>
                <select name="correct_option" required
                        class="w-full rounded-xl border border-border bg-surface px-4 py-2.5 text-sm text-primary outline-none transition focus:border-accent/50 focus:ring-2 focus:ring-accent/20">
                    @foreach(['a'=>'ሀ (A)','b'=>'ለ (B)','c'=>'ሐ (C)','d'=>'መ (D)'] as $val => $label)
                        <option value="{{ $val }}" {{ old('correct_option', $question?->correct_option) === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-secondary mb-1.5">ደረጃ</label>
                <select name="difficulty" required
                        class="w-full rounded-xl border border-border bg-surface px-4 py-2.5 text-sm text-primary outline-none transition focus:border-accent/50 focus:ring-2 focus:ring-accent/20">
                    <option value="easy"   {{ old('difficulty', $question?->difficulty) === 'easy'   ? 'selected' : '' }}>ቀላል</option>
                    <option value="medium" {{ old('difficulty', $question?->difficulty) === 'medium' ? 'selected' : '' }}>መካከለኛ</option>
                    <option value="hard"   {{ old('difficulty', $question?->difficulty) === 'hard'   ? 'selected' : '' }}>ከባድ</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-secondary mb-1.5">ነጥብ</label>
                <input type="number" name="points" min="1" max="10" required
                       value="{{ old('points', $question?->points ?? 1) }}"
                       class="w-full rounded-xl border border-border bg-surface px-4 py-2.5 text-sm text-primary outline-none transition focus:border-accent/50 focus:ring-2 focus:ring-accent/20">
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-semibold text-secondary mb-1.5">ቅደም ተከተል</label>
                <input type="number" name="sort_order" min="0" required
                       value="{{ old('sort_order', $question?->sort_order ?? 0) }}"
                       class="w-full rounded-xl border border-border bg-surface px-4 py-2.5 text-sm text-primary outline-none transition focus:border-accent/50 focus:ring-2 focus:ring-accent/20">
            </div>
            <div class="flex items-end pb-1">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1"
                           {{ old('is_active', $question?->is_active ?? true) ? 'checked' : '' }}
                           class="h-4 w-4 rounded border-border text-accent focus:ring-accent/30">
                    <span class="text-sm font-semibold text-secondary">{{ __('app.fasika_quiz_admin_form_active_label') }}</span>
                </label>
            </div>
        </div>

        <div class="flex justify-end gap-3 pt-2 border-t border-border">
            <a href="{{ route('admin.fasika-quiz.index') }}"
               class="inline-flex items-center rounded-xl border border-border bg-surface px-5 py-2.5 text-sm font-semibold text-primary transition hover:bg-muted/50">
                {{ __('app.fasika_quiz_admin_form_cancel') }}
            </a>
            <button type="submit"
                    class="inline-flex items-center rounded-xl bg-accent px-5 py-2.5 text-sm font-semibold text-white shadow transition hover:opacity-90">
                {{ $question ? __('app.fasika_quiz_admin_form_submit_edit') : __('app.fasika_quiz_admin_form_submit_new') }}
            </button>
        </div>
    </form>
</div>
@endsection
