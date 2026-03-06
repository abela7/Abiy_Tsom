@extends('layouts.admin')
@section('title', __('app.whatsapp_engagement_title'))

@section('content')
@include('admin.whatsapp._nav')

<div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between mb-8">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-primary">{{ __('app.whatsapp_engagement_title') }}</h1>
        <p class="text-sm text-muted-text mt-1.5">
            {{ $member->baptism_name ?: __('app.whatsapp_template_test_member_fallback') }}
            @if($member->whatsapp_phone)
                - {{ maskPhone($member->whatsapp_phone) }}
            @endif
        </p>
    </div>
    <a href="{{ route('admin.whatsapp.reminders') }}"
       class="inline-flex items-center justify-center px-4 py-2.5 rounded-lg text-sm font-medium bg-muted text-secondary hover:bg-muted/80 transition">
        {{ __('app.back') }}
    </a>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
    <div class="bg-card rounded-xl p-5 shadow-sm border border-border">
        <p class="text-xs font-semibold text-muted-text uppercase tracking-wider">{{ __('app.whatsapp_last_opened') }}</p>
        <p class="text-2xl font-bold text-accent mt-2 tabular-nums">
            {{ $lastOpenedAt ? $lastOpenedAt->format('Y-m-d H:i') : __('app.never') }}
        </p>
    </div>
    <div class="bg-card rounded-xl p-5 shadow-sm border border-border">
        <p class="text-xs font-semibold text-muted-text uppercase tracking-wider">{{ __('app.whatsapp_opened_days') }}</p>
        <p class="text-2xl font-bold text-primary mt-2 tabular-nums">{{ number_format($openedDaysCount) }}</p>
    </div>
</div>

<div class="bg-card rounded-xl shadow-sm border border-border overflow-hidden">
    <div class="px-5 py-4 border-b border-border">
        <h2 class="text-sm font-semibold text-primary">{{ __('app.whatsapp_engagement_opened_days_list') }}</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-border">
                    <th class="text-left px-5 py-3.5 font-medium text-muted-text text-xs uppercase tracking-wider">{{ __('app.whatsapp_engagement_day') }}</th>
                    <th class="text-left px-5 py-3.5 font-medium text-muted-text text-xs uppercase tracking-wider">{{ __('app.whatsapp_engagement_content_date') }}</th>
                    <th class="text-left px-5 py-3.5 font-medium text-muted-text text-xs uppercase tracking-wider">{{ __('app.whatsapp_last_opened') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @forelse($reminderOpens as $open)
                    <tr class="hover:bg-muted/40 transition-colors">
                        <td class="px-5 py-3.5 font-medium text-primary">
                            @if($open->dailyContent?->day_number)
                                {{ __('app.day_x', ['day' => $open->dailyContent->day_number]) }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-secondary">
                            {{ $open->dailyContent?->date?->format('Y-m-d') ?? '-' }}
                        </td>
                        <td class="px-5 py-3.5 text-secondary tabular-nums">
                            {{ $open->last_opened_at?->format('Y-m-d H:i') ?? __('app.never') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-5 py-12 text-center text-muted-text">{{ __('app.whatsapp_engagement_empty') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($reminderOpens->hasPages())
        <div class="px-5 py-4 border-t border-border">
            {{ $reminderOpens->links() }}
        </div>
    @endif
</div>
@endsection
