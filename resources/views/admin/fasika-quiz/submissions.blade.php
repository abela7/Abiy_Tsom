@extends('layouts.admin')

@section('title', 'የፋሲካ ጥያቄ ውጤቶች')

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-primary">የፋሲካ ጥያቄ ውጤቶች</h1>
            <p class="mt-1 text-sm text-muted-text">ሁሉንም ሙከራዎች፣ ስሞችን፣ ነጥቦችን እና IP ያሳያል።</p>
        </div>
        <a href="{{ route('admin.fasika-quiz.index') }}"
           class="inline-flex items-center rounded-xl border border-border bg-card px-4 py-2 text-sm font-semibold text-primary transition hover:bg-muted/50">
            ← ጥያቄዎች
        </a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-border bg-card p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-muted-text">ጠቅላላ ሙከራዎች</p>
            <p class="mt-3 text-3xl font-black text-primary">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="rounded-2xl border border-border bg-card p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-muted-text">ስም ያስገቡ</p>
            <p class="mt-3 text-3xl font-black text-primary">{{ number_format($stats['named']) }}</p>
        </div>
        <div class="rounded-2xl border border-border bg-card p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-muted-text">አማካይ ነጥብ</p>
            <p class="mt-3 text-3xl font-black text-primary">{{ $stats['avg_score'] }}<span class="text-base font-medium text-muted-text">/30</span></p>
        </div>
        <div class="rounded-2xl border border-border bg-card p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-muted-text">ሙሉ ነጥብ</p>
            <p class="mt-3 text-3xl font-black text-primary">{{ number_format($stats['perfect']) }}</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-border bg-card">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-border text-sm">
                <thead class="bg-surface/70">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-secondary">ስም</th>
                        <th class="px-4 py-3 text-left font-semibold text-secondary">ነጥብ</th>
                        <th class="px-4 py-3 text-left font-semibold text-secondary">ትክክለኛ</th>
                        <th class="px-4 py-3 text-left font-semibold text-secondary">ጊዜ</th>
                        <th class="px-4 py-3 text-left font-semibold text-secondary">IP</th>
                        <th class="px-4 py-3 text-left font-semibold text-secondary">ቀን</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse($submissions as $sub)
                        <tr class="align-middle">
                            <td class="px-4 py-3 font-semibold text-primary">
                                {{ $sub->participant_name ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-bold text-primary">{{ $sub->score }}</span>
                                <span class="text-muted-text">/{{ $sub->total_possible }}</span>
                                <span class="ml-1 text-xs text-muted-text">({{ $sub->percentageScore() }}%)</span>
                            </td>
                            <td class="px-4 py-3 text-primary">
                                {{ $sub->correctCount() }}/{{ count((array) $sub->answers) }}
                            </td>
                            <td class="px-4 py-3 font-mono text-primary">{{ $sub->formattedTime() }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-muted-text">{{ $sub->ip_address ?? '—' }}</td>
                            <td class="px-4 py-3 text-muted-text">{{ $sub->created_at?->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-muted-text">ምንም ውጤት አልተመዘገበም።</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $submissions->links() }}
</div>
@endsection
