{{-- Banner Section --}}
@if($banners->isNotEmpty())
<section class="space-y-3">
    <h2 class="text-xs font-bold text-muted-text uppercase tracking-wider">{{ __('app.banner_section_title') }}</h2>

    <div class="space-y-4">
        @foreach($banners as $banner)
        <div class="rounded-2xl shadow-lg border border-border overflow-hidden bg-card"
             x-data="{ showForm: false, submitted: false, sending: false, errors: {} }">

            {{-- Banner image --}}
            @if($banner->imageUrl())
            <div class="aspect-[16/9] overflow-hidden">
                <img src="{{ $banner->imageUrl() }}"
                     alt="{{ $banner->localizedTitle() }}"
                     class="w-full h-full object-cover">
            </div>
            @endif

            {{-- Banner content --}}
            <div class="p-4 space-y-3">
                <h3 class="text-base font-bold text-primary leading-snug">{{ $banner->localizedTitle() }}</h3>

                @if($banner->localizedDescription())
                    <p class="text-sm text-secondary leading-relaxed">{{ $banner->localizedDescription() }}</p>
                @endif

                {{-- CTA Button --}}
                @if($banner->button_url)
                    <a href="{{ $banner->button_url }}" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-2 px-5 py-2.5 bg-accent text-on-accent text-sm font-semibold rounded-xl hover:opacity-90 transition active:scale-95">
                        {{ $banner->localizedButtonLabel() }}
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                @else
                    {{-- Form toggle button --}}
                    <button x-show="!submitted" @click="showForm = !showForm"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-accent text-on-accent text-sm font-semibold rounded-xl hover:opacity-90 transition active:scale-95">
                        {{ $banner->localizedButtonLabel() }}
                    </button>

                    {{-- Thank you message --}}
                    <div x-show="submitted" x-cloak
                         class="p-4 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-center space-y-1">
                        <p class="text-sm font-semibold text-green-700 dark:text-green-400">{{ __('app.banner_thankyou_title') }}</p>
                        <p class="text-xs text-green-600 dark:text-green-500">{{ __('app.banner_thankyou_desc') }}</p>
                    </div>

                    {{-- Inline form --}}
                    <div x-show="showForm && !submitted" x-cloak x-transition
                         class="mt-3 p-4 rounded-xl border border-border bg-surface space-y-3">
                        <p class="text-sm font-semibold text-primary">{{ __('app.banner_form_title') }}</p>
                        <p class="text-xs text-muted-text">{{ __('app.banner_form_desc') }}</p>

                        <div>
                            <input type="text" x-ref="name_{{ $banner->id }}"
                                   class="w-full px-3 py-2.5 rounded-xl border border-border bg-card text-primary text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent"
                                   placeholder="{{ __('app.banner_name_placeholder') }}">
                            <template x-if="errors.contact_name">
                                <p class="mt-1 text-xs text-red-500" x-text="errors.contact_name[0]"></p>
                            </template>
                        </div>
                        <div>
                            <input type="tel" x-ref="phone_{{ $banner->id }}"
                                   class="w-full px-3 py-2.5 rounded-xl border border-border bg-card text-primary text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent"
                                   placeholder="{{ __('app.banner_phone_placeholder') }}">
                            <template x-if="errors.contact_phone">
                                <p class="mt-1 text-xs text-red-500" x-text="errors.contact_phone[0]"></p>
                            </template>
                        </div>

                        <div class="flex items-center gap-2">
                            <button @click="
                                let name = $refs['name_{{ $banner->id }}'].value.trim();
                                let phone = $refs['phone_{{ $banner->id }}'].value.trim();
                                errors = {};
                                if (!name) { errors.contact_name = ['{{ __('app.banner_name_required') }}']; }
                                if (!phone) { errors.contact_phone = ['{{ __('app.banner_phone_required') }}']; }
                                if (Object.keys(errors).length) return;
                                sending = true;
                                fetch('/api/member/banner/{{ $banner->id }}/respond', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                    },
                                    body: JSON.stringify({ contact_name: name, contact_phone: phone })
                                })
                                .then(r => r.json())
                                .then(d => { sending = false; if (d.success) { submitted = true; showForm = false; } else if (d.errors) { errors = d.errors; } })
                                .catch(() => { sending = false; });
                            "
                            :disabled="sending"
                            class="px-4 py-2.5 bg-accent text-on-accent text-sm font-semibold rounded-xl hover:opacity-90 transition active:scale-95 disabled:opacity-50">
                                <span x-show="!sending">{{ __('app.banner_submit') }}</span>
                                <span x-show="sending" x-cloak>{{ __('app.loading') }}</span>
                            </button>
                            <button @click="showForm = false; errors = {}"
                                    class="px-3 py-2.5 text-sm text-muted-text hover:text-primary transition">
                                {{ __('app.cancel') }}
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</section>
@endif
