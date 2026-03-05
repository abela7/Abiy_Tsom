@extends('layouts.admin')

@section('title', 'Feedback')

@section('content')
<div class="max-w-4xl">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-primary">Feedback</h1>
        <span class="text-sm text-muted-text">{{ $feedbacks->total() }} total</span>
    </div>

    @if(session('success'))
        <div class="mb-5 px-4 py-3 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-300 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if($feedbacks->isEmpty())
        <div class="bg-card rounded-2xl border border-border shadow-sm p-8 text-center">
            <svg class="w-12 h-12 mx-auto text-muted-text/30 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
            <p class="text-muted-text text-sm">No feedback yet.</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($feedbacks as $fb)
                <div x-data="{ expanded: false }"
                     class="bg-card rounded-2xl border shadow-sm overflow-hidden transition {{ $fb->is_read ? 'border-border' : 'border-accent/30' }}">
                    <div class="p-4 sm:p-5">
                        {{-- Header --}}
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                @if(!$fb->is_read)
                                    <span class="w-2.5 h-2.5 rounded-full bg-accent shrink-0"></span>
                                @endif
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-primary truncate">{{ $fb->name }}</p>
                                    @if($fb->email)
                                        <p class="text-xs text-muted-text">{{ $fb->email }}</p>
                                    @endif
                                </div>
                            </div>
                            <span class="text-xs text-muted-text shrink-0">{{ $fb->created_at->diffForHumans() }}</span>
                        </div>

                        {{-- Message preview / full --}}
                        <div class="mt-3">
                            <p class="text-sm text-secondary whitespace-pre-line" x-show="!expanded">{{ Str::limit($fb->message, 200) }}</p>
                            <p class="text-sm text-secondary whitespace-pre-line" x-show="expanded" x-cloak>{{ $fb->message }}</p>
                            @if(Str::length($fb->message) > 200)
                                <button type="button" @click="expanded = !expanded" class="text-xs text-accent font-medium mt-1 hover:underline" x-text="expanded ? 'Show less' : 'Read more'"></button>
                            @endif
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center gap-2 mt-4 pt-3 border-t border-border">
                            <form method="POST" action="{{ route('admin.feedback.toggle-read', $fb) }}">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-medium transition {{ $fb->is_read ? 'bg-muted text-muted-text hover:bg-accent/10 hover:text-accent' : 'bg-accent/10 text-accent hover:bg-accent/20' }}">
                                    {{ $fb->is_read ? 'Mark unread' : 'Mark read' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.feedback.destroy', $fb) }}" onsubmit="return confirm('Delete this feedback?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-medium bg-red-500/10 text-red-500 hover:bg-red-500/20 transition">
                                    Delete
                                </button>
                            </form>
                            @if($fb->ip_address)
                                <span class="text-[10px] text-muted-text/50 ml-auto">{{ $fb->ip_address }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $feedbacks->links() }}
        </div>
    @endif
</div>
@endsection
