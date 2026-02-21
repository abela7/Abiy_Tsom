@extends('layouts.member')

@section('title', __('app.progress_title') . ' - ' . __('app.app_name'))

@section('content')
<div class="px-4 pt-6 pb-10 space-y-5 max-w-2xl mx-auto sm:px-6"
     x-data="progressDashboard()"
     x-init="loadData()">

    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-primary tracking-tight sm:text-3xl">{{ __('app.progress_title') }}</h1>
        <p class="text-sm text-muted-text mt-0.5">{{ __('app.progress_subtitle') }}</p>
    </div>

    {{-- Report Scope: Modern pill-style control --}}
    <div class="space-y-4">
        <p class="text-[10px] font-bold text-muted-text uppercase tracking-widest">{{ __('app.report_scope') }}</p>
        <div class="relative inline-grid grid-cols-2 sm:grid-cols-4 gap-1 p-1 rounded-2xl bg-linear-to-br from-muted/60 to-muted/40 border border-border/40 w-full shadow-inner">
            <template x-for="tab in tabs" :key="tab.key">
                <button @click="switchPeriod(tab.key)"
                        :class="period === tab.key ? 'bg-linear-to-br from-accent to-accent/90 text-on-accent shadow-md scale-[1.02]' : 'text-muted-text hover:text-primary hover:bg-card/50'"
                        class="py-2.5 px-1 sm:px-3 rounded-xl text-xs sm:text-sm font-bold transition-all duration-300 whitespace-nowrap relative overflow-hidden group"
                        x-text="tab.label">
                </button>
            </template>
        </div>

        {{-- Day picker (when Daily) --}}
        <div x-show="period === 'daily'" x-transition class="flex flex-col sm:flex-row sm:items-center gap-3">
            <label for="dayPicker" class="text-[10px] font-bold text-muted-text uppercase tracking-widest shrink-0">{{ __('app.jump_to_day') }}</label>
            <select id="dayPicker"
                    x-model="selectedDay"
                    @change="applyDayFilter()"
                    class="flex-1 min-w-0 px-4 py-3 rounded-xl border border-border/60 bg-card text-primary text-sm font-bold focus:ring-2 focus:ring-accent/20 focus:border-accent outline-none appearance-none transition-all shadow-xs">
                <option value="">— {{ __('app.period_daily') }} —</option>
                <template x-for="opt in dayOptions" :key="opt.day">
                    <option :value="opt.day" :disabled="!opt.has_content" x-text="opt.label"></option>
                </template>
            </select>
        </div>

        {{-- Week picker (when Weekly) --}}
        <div x-show="period === 'weekly'" x-transition class="flex flex-col sm:flex-row sm:items-center gap-3">
            <label for="weekPicker" class="text-[10px] font-bold text-muted-text uppercase tracking-widest shrink-0">{{ __('app.jump_to_week') }}</label>
            <select id="weekPicker"
                    x-model="selectedWeek"
                    @change="applyWeekFilter()"
                    class="flex-1 min-w-0 px-4 py-3 rounded-xl border border-border/60 bg-card text-primary text-sm font-bold focus:ring-2 focus:ring-accent/20 focus:border-accent outline-none appearance-none transition-all shadow-xs">
                <option value="">— {{ __('app.period_weekly') }} —</option>
                <template x-for="opt in weekOptions" :key="opt.week">
                    <option :value="opt.week" x-text="opt.label"></option>
                </template>
            </select>
        </div>
    </div>

    {{-- Loading State --}}
    <div x-show="loading" x-transition class="flex justify-center py-12">
        <div class="w-8 h-8 border-3 border-accent/30 border-t-accent rounded-full animate-spin"></div>
    </div>

    {{-- Dashboard Content --}}
    <div x-show="!loading && loaded" x-transition.opacity class="space-y-5">

        {{-- Top Row: Overall Gauge + Streak --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

            {{-- Overall Radial Gauge --}}
            <div class="bg-card rounded-3xl p-6 shadow-sm border border-border/60 flex flex-col items-center justify-center transition-all hover:shadow-md hover:border-accent/20">
                <h3 class="text-[10px] font-bold text-muted-text uppercase tracking-widest mb-6">{{ __('app.overall_progress') }}</h3>
                <div class="relative w-44 h-44">
                    <canvas id="gaugeChart" width="176" height="176"></canvas>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-4xl font-black tracking-tight" :class="gaugeColor()" x-text="overall + '%'">0%</span>
                        <span class="text-[10px] text-muted-text font-bold uppercase tracking-wider mt-1" x-text="periodLabel()"></span>
                    </div>
                </div>
            </div>

            {{-- Streak + Best/Worst --}}
            <div class="space-y-4">
                {{-- Streak Card --}}
                <div class="bg-card rounded-3xl p-6 shadow-sm border border-border/60 transition-all hover:shadow-md">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-linear-to-br from-accent-secondary/20 to-accent-secondary/5 flex items-center justify-center shrink-0 shadow-inner">
                            <svg class="w-7 h-7 text-accent-secondary" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 23c-3.5 0-7-2.5-7-7 0-3.5 2-6 4-8l1 1c-1.5 2-2 4-2 5.5 0 2.5 1.5 4.5 4 4.5s4-2 4-4.5c0-2-.5-3.5-1-4.5l.5-.5c2 2 3.5 4.5 3.5 7.5 0 4.5-3.5 6-7 6zM12 8l-1-1c1-2 1-4 0-5.5l1-1.5c2 2 2 5 0 8z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-baseline gap-1">
                                <span class="text-3xl font-black text-primary tracking-tighter" x-text="streak"></span>
                                <span class="text-xs font-bold text-muted-text uppercase">{{ __('app.day_streak') }}</span>
                            </div>
                            <p class="text-[11px] text-muted-text font-medium mt-0.5 leading-tight">{{ __('app.consecutive_days') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Best & Worst Day --}}
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-success-bg/50 rounded-2xl p-4 text-center border border-success/10 transition hover:scale-[1.02]">
                        <p class="text-[10px] font-bold text-success uppercase tracking-widest">{{ __('app.best_day') }}</p>
                        <p class="text-xl font-black text-success tracking-tight" x-text="bestDay ? ('D' + bestDay.day) : '—'"></p>
                        <p class="text-[11px] font-bold text-success/70" x-text="bestDay ? (bestDay.rate + '%') : ''"></p>
                    </div>
                    <div class="bg-error-bg/50 rounded-2xl p-4 text-center border border-error/10 transition hover:scale-[1.02]">
                        <p class="text-[10px] font-bold text-error uppercase tracking-widest">{{ __('app.needs_work') }}</p>
                        <p class="text-xl font-black text-error tracking-tight" x-text="worstDay ? ('D' + worstDay.day) : '—'"></p>
                        <p class="text-[11px] font-bold text-error/70" x-text="worstDay ? (worstDay.rate + '%') : ''"></p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Daily Trend Line Chart --}}
        <div x-show="dailyRates.length > 1" class="bg-card rounded-3xl p-6 shadow-sm border border-border/60 transition hover:shadow-md">
            <h3 class="text-[10px] font-bold text-muted-text uppercase tracking-widest mb-6">{{ __('app.daily_completion') }}</h3>
            <div class="relative" style="height: 220px;">
                <canvas id="trendChart"></canvas>
            </div>
        </div>

        {{-- Per-Activity Horizontal Bar Chart --}}
        <div x-show="activityRates.length > 0" class="bg-card rounded-3xl p-6 shadow-sm border border-border/60 transition hover:shadow-md">
            <h3 class="text-[10px] font-bold text-muted-text uppercase tracking-widest mb-6">{{ __('app.activity_breakdown') }}</h3>
            <div class="relative" :style="'height: ' + Math.max(activityRates.length * 45, 140) + 'px'">
                <canvas id="activityChart"></canvas>
            </div>
        </div>

        {{-- 55-Day Heatmap (All period only) --}}
        <div x-show="period === 'all' && heatmap.length > 0" class="bg-card rounded-3xl p-6 shadow-sm border border-border/60">
            <h3 class="text-[10px] font-bold text-muted-text uppercase tracking-widest mb-4">{{ __('app.season_heatmap') }}</h3>
            <p class="text-xs text-muted-text mb-6 leading-relaxed">{{ __('app.heatmap_hint') }}</p>
            <div class="grid grid-cols-7 gap-1.5 sm:gap-2">
                <template x-for="cell in heatmap" :key="cell.day">
                    <div class="aspect-square rounded-lg flex items-center justify-center text-[10px] font-bold transition-colors"
                         :style="heatmapStyle(cell.rate)"
                         :title="'{{ addslashes(__("app.day_x_rate")) }}'.replace(':day', cell.day).replace(':rate', cell.rate)"
                         x-text="cell.day">
                    </div>
                </template>
            </div>
            {{-- Heatmap Legend --}}
            <div class="flex items-center justify-center gap-1.5 mt-4 text-[10px] text-muted-text">
                <span>0%</span>
                <div class="flex gap-0.5">
                    <div class="w-4 h-3 rounded-sm" style="background: rgba(22,163,74,0.1);"></div>
                    <div class="w-4 h-3 rounded-sm" style="background: rgba(22,163,74,0.3);"></div>
                    <div class="w-4 h-3 rounded-sm" style="background: rgba(22,163,74,0.5);"></div>
                    <div class="w-4 h-3 rounded-sm" style="background: rgba(22,163,74,0.7);"></div>
                    <div class="w-4 h-3 rounded-sm" style="background: rgba(22,163,74,1);"></div>
                </div>
                <span>100%</span>
            </div>
        </div>

        {{-- View day content (when viewing single day) --}}
        <div x-show="viewDayContentId" class="flex justify-center">
            <a :href="`{{ url('/member/day') }}/${viewDayContentId}`"
               class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-accent text-on-accent text-sm font-semibold hover:bg-accent-hover transition shadow-sm">
                {{ __('app.view_day_content') }}
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
            </a>
        </div>

        {{-- Improvement Suggestions --}}
        <div x-show="suggestions.length > 0" class="bg-reflection-bg/40 border border-reflection-border/60 rounded-3xl p-6 shadow-xs">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-accent-secondary/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-accent-secondary shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </div>
                <h3 class="text-xs font-bold text-primary uppercase tracking-widest">{{ __('app.suggestions') }}</h3>
            </div>
            <ul class="space-y-3">
                <template x-for="s in suggestions" :key="s.key || s.name">
                    <li class="flex items-start gap-3 text-sm text-secondary group">
                        <span class="w-2 h-2 mt-1.5 rounded-full shrink-0 transition-transform group-hover:scale-125"
                              :class="s.rate < 40 ? 'bg-error shadow-[0_0_8px_rgba(220,38,38,0.4)]' : 'bg-accent-secondary shadow-[0_0_8px_rgba(212,175,55,0.4)]'"></span>
                        <div class="flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <strong x-text="s.name" class="text-primary font-bold"></strong>
                                <span x-text="s.rate + '%'" :class="s.rate < 40 ? 'text-error' : 'text-accent-secondary'" class="font-black"></span>
                            </div>
                            <span class="text-muted-text text-[11px] font-medium block mt-0.5">{{ __('app.suggestion_improve') }}</span>
                        </div>
                    </li>
                </template>
            </ul>
        </div>

        {{-- Empty State --}}
        <div x-show="loaded && activityRates.length === 0 && !loading" class="text-center py-16 bg-card rounded-3xl border-2 border-dashed border-border/60">
            <div class="w-16 h-16 bg-muted rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-muted-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <p class="text-primary font-bold">{{ __('app.no_data') }}</p>
            <p class="text-muted-text text-xs mt-1 font-medium">{{ __('app.start_tracking_hint') }}</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
function progressDashboard() {
    return {
        period: 'daily',
        selectedDay: '',
        selectedWeek: '',
        dayOptions: [],
        weekOptions: [],
        loading: false,
        loaded: false,
        overall: 0,
        streak: 0,
        bestDay: null,
        worstDay: null,
        dailyRates: [],
        activityRates: [],
        suggestions: [],
        heatmap: [],
        viewDayContentId: null,

        _gaugeChart: null,
        _trendChart: null,
        _activityChart: null,

        tabs: [
            { key: 'daily', label: '{{ __("app.period_daily") }}' },
            { key: 'weekly', label: '{{ __("app.period_weekly") }}' },
            { key: 'monthly', label: '{{ __("app.period_monthly") }}' },
            { key: 'all', label: '{{ __("app.period_all") }}' },
        ],

        switchPeriod(key) {
            if (this.period === key || this.loading) return;
            this.period = key;
            this.selectedDay = '';
            this.selectedWeek = '';
            this.loadData();
        },

        applyDayFilter() {
            this.loadData();
        },

        applyWeekFilter() {
            this.loadData();
        },

        async loadData() {
            this.loading = true;
            let url = '/api/member/progress/data?period=' + this.period;
            if (this.period === 'daily' && this.selectedDay) {
                url += '&day=' + encodeURIComponent(this.selectedDay);
            } else if (this.period === 'weekly' && this.selectedWeek) {
                url += '&week=' + encodeURIComponent(this.selectedWeek);
            }
            try {
                const data = await AbiyTsom.get(url);
                if (data.success) {
                    this.overall = data.overall;
                    this.streak = data.streak;
                    this.bestDay = data.best_day;
                    this.worstDay = data.worst_day;
                    this.dailyRates = data.daily_rates;
                    this.activityRates = data.activity_rates;
                    this.suggestions = data.suggestions;
                    this.heatmap = data.heatmap || [];
                    this.dayOptions = data.day_options || [];
                    this.weekOptions = data.week_options || [];
                    this.viewDayContentId = data.view_day_content_id || null;
                    this.loaded = true;
                    this.$nextTick(() => this.renderCharts());
                }
            } catch (e) {
                console.error('Progress load error:', e);
                this.loaded = true;
            } finally {
                this.loading = false;
            }
        },

        /**
         * Read a CSS custom property from :root.
         */
        css(name) {
            return getComputedStyle(document.documentElement)
                .getPropertyValue(name).trim();
        },

        /**
         * Color class for the overall gauge text.
         */
        gaugeColor() {
            if (this.overall >= 70) return 'text-success';
            if (this.overall >= 40) return 'text-accent-secondary';
            return 'text-error';
        },

        /**
         * Readable label for the active period.
         */
        periodLabel() {
            const map = {
                daily: '{{ __("app.period_daily") }}',
                weekly: '{{ __("app.period_weekly") }}',
                monthly: '{{ __("app.period_monthly") }}',
                all: '{{ __("app.period_all") }}',
            };
            return map[this.period] || '';
        },

        /**
         * Inline style for heatmap cells based on rate.
         */
        heatmapStyle(rate) {
            const isDark = document.documentElement.classList.contains('dark');
            if (rate === 0) {
                return isDark
                    ? 'background: rgba(255,255,255,0.05); color: rgba(255,255,255,0.3);'
                    : 'background: rgba(0,0,0,0.04); color: rgba(0,0,0,0.35);';
            }
            const alpha = 0.15 + (rate / 100) * 0.85;
            const textColor = rate >= 50
                ? 'rgba(255,255,255,0.95)'
                : (isDark ? 'rgba(255,255,255,0.8)' : 'rgba(22,163,74,1)');
            return `background: rgba(22,163,74,${alpha}); color: ${textColor};`;
        },

        /**
         * Render / re-render all Chart.js charts.
         */
        renderCharts() {
            this.renderGauge();
            this.renderTrend();
            this.renderActivityBars();
        },

        renderGauge() {
            const el = document.getElementById('gaugeChart');
            if (!el) return;
            if (this._gaugeChart) this._gaugeChart.destroy();

            const success = this.css('--app-success');
            const amber = this.css('--app-accent-secondary');
            const error = this.css('--app-error');
            const muted = this.css('--app-muted');
            const color = this.overall >= 70 ? success
                : (this.overall >= 40 ? amber : error);

            this._gaugeChart = new Chart(el, {
                type: 'doughnut',
                data: {
                    datasets: [{
                        data: [this.overall, 100 - this.overall],
                        backgroundColor: [color, muted],
                        borderWidth: 0,
                        borderRadius: 6,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    cutout: '78%',
                    plugins: { legend: { display: false }, tooltip: { enabled: false } },
                    animation: { animateRotate: true, duration: 800 },
                },
            });
        },

        renderTrend() {
            const el = document.getElementById('trendChart');
            if (!el || this.dailyRates.length < 2) return;
            if (this._trendChart) this._trendChart.destroy();

            const ctx = el.getContext('2d');
            const accent = this.css('--app-accent');
            const gradient = ctx.createLinearGradient(0, 0, 0, 200);
            gradient.addColorStop(0, accent + '40');
            gradient.addColorStop(1, accent + '05');

            this._trendChart = new Chart(el, {
                type: 'line',
                data: {
                    labels: this.dailyRates.map(d => 'D' + d.day),
                    datasets: [{
                        data: this.dailyRates.map(d => d.rate),
                        borderColor: accent,
                        backgroundColor: gradient,
                        borderWidth: 2.5,
                        pointRadius: this.dailyRates.length > 14 ? 0 : 3,
                        pointHoverRadius: 5,
                        pointBackgroundColor: accent,
                        tension: 0.35,
                        fill: true,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: this.css('--app-card'),
                            titleColor: this.css('--app-primary'),
                            bodyColor: this.css('--app-secondary'),
                            borderColor: this.css('--app-border'),
                            borderWidth: 1,
                            padding: 10,
                            cornerRadius: 8,
                            callbacks: {
                                label: (ctx) => ctx.parsed.y + '%',
                            },
                        },
                    },
                    scales: {
                        y: {
                            min: 0,
                            max: 100,
                            ticks: {
                                callback: v => v + '%',
                                color: this.css('--app-muted-text'),
                                font: { size: 10 },
                                maxTicksLimit: 5,
                            },
                            grid: {
                                color: this.css('--app-border-muted'),
                            },
                        },
                        x: {
                            ticks: {
                                color: this.css('--app-muted-text'),
                                font: { size: 10 },
                                maxRotation: 0,
                                autoSkip: true,
                                maxTicksLimit: window.innerWidth < 640 ? 8 : 15,
                            },
                            grid: { display: false },
                        },
                    },
                },
            });
        },

        renderActivityBars() {
            const el = document.getElementById('activityChart');
            if (!el || this.activityRates.length === 0) return;
            if (this._activityChart) this._activityChart.destroy();

            const success = this.css('--app-success');
            const amber = this.css('--app-accent-secondary');
            const error = this.css('--app-error');

            this._activityChart = new Chart(el, {
                type: 'bar',
                data: {
                    labels: this.activityRates.map(a => a.name),
                    datasets: [{
                        data: this.activityRates.map(a => a.rate),
                        backgroundColor: this.activityRates.map(a =>
                            a.rate >= 70 ? success
                                : (a.rate >= 40 ? amber : error)
                        ),
                        borderRadius: 6,
                        barThickness: 22,
                    }],
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: this.css('--app-card'),
                            titleColor: this.css('--app-primary'),
                            bodyColor: this.css('--app-secondary'),
                            borderColor: this.css('--app-border'),
                            borderWidth: 1,
                            padding: 10,
                            cornerRadius: 8,
                            callbacks: {
                                label: (ctx) => ctx.parsed.x + '%',
                            },
                        },
                    },
                    scales: {
                        x: {
                            min: 0,
                            max: 100,
                            ticks: {
                                callback: v => v + '%',
                                color: this.css('--app-muted-text'),
                                font: { size: 10 },
                            },
                            grid: {
                                color: this.css('--app-border-muted'),
                            },
                        },
                        y: {
                            ticks: {
                                color: this.css('--app-primary'),
                                font: { size: 11, weight: '600' },
                            },
                            grid: { display: false },
                        },
                    },
                },
            });
        },
    };
}
</script>
@endpush
