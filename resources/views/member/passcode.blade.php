@extends('layouts.member')

@section('title', __('app.passcode_title'))

@section('content')
<div class="min-h-screen flex flex-col items-center justify-center px-6" x-data="passcodeScreen()">
    <div class="text-center mb-8">
        <div class="w-16 h-16 bg-accent rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-accent-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        </div>
        <h1 class="text-xl font-bold text-primary">{{ __('app.passcode_title') }}</h1>
        <p class="text-sm text-muted-text mt-1">{{ __('app.passcode_subtitle') }}</p>
    </div>

    <div class="w-full max-w-xs space-y-4">
        <input type="password"
               x-model="passcode"
               maxlength="6"
               inputmode="numeric"
               pattern="[0-9]*"
               placeholder="{{ __('app.passcode_placeholder') }}"
               class="w-full text-center text-2xl tracking-[0.5em] py-4 border border-border rounded-xl bg-card text-primary focus:ring-2 focus:ring-accent focus:border-transparent outline-none"
               @keyup.enter="verify()">

        <p x-show="error" x-text="error" class="text-error text-sm text-center"></p>

        <button @click="verify()"
                :disabled="passcode.length < 4 || isLoading"
                class="w-full py-3 bg-accent text-on-accent rounded-xl font-semibold hover:bg-accent-hover disabled:opacity-50 transition">
            <span x-show="!isLoading">{{ __('app.unlock') }}</span>
            <span x-show="isLoading">{{ __('app.loading') }}</span>
        </button>

        <button type="button"
                @click="resetAndStartFresh()"
                class="w-full py-2.5 text-muted-text hover:text-secondary text-sm font-medium transition">
            {{ __('app.reset') }}
        </button>
    </div>
</div>
@endsection

@push('scripts')
<script>
function passcodeScreen() {
    return {
        passcode: '',
        error: '',
        isLoading: false,

        async verify() {
            if (this.passcode.length < 4) return;
            this.isLoading = true;
            this.error = '';

            try {
                const data = await AbiyTsom.api('/member/passcode/verify', {
                    passcode: this.passcode,
                });
                if (data.success) {
                    window.location.href = AbiyTsom.baseUrl + '/member/home?token=' + AbiyTsom.token;
                } else {
                    this.error = data.message || '{{ __("app.incorrect_passcode") }}';
                }
            } catch {
                this.error = '{{ __("app.incorrect_passcode") }}';
            }
            this.isLoading = false;
        },

        resetAndStartFresh() {
            localStorage.removeItem('member_token');
            localStorage.removeItem('member_name');
            document.cookie = 'member_token=;path=/;SameSite=Lax;Max-Age=0';
            window.location.href = AbiyTsom.baseUrl + '/';
        }
    };
}
</script>
@endpush
