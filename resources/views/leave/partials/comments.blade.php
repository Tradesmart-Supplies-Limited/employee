{{--
    Comment thread on a leave request. Anyone in the approval chain, and the
    applicant, can add to it, before or after a decision.
    Vars: $leave, $canComment (bool), $commentUrl (string)
--}}
@php $thread = $leave->relationLoaded('comments') ? $leave->comments : $leave->comments()->get(); @endphp
<div class="lc" id="comments">
    <style>
        .lc-head { display: flex; align-items: center; gap: 8px; margin-bottom: 12px; }
        .lc-head h6 { margin: 0; font-weight: 700; }
        .lc-count { font-size: .75rem; padding: 1px 8px; border-radius: 999px; background: var(--admin-surface-soft, #f1f5f9); color: var(--admin-muted, #64748b); }
        .lc-item { display: flex; gap: 10px; margin-bottom: 12px; }
        .lc-av { width: 34px; height: 34px; flex-shrink: 0; border-radius: 50%; display: grid; place-items: center; font-size: .75rem; font-weight: 800; color: #fff; background: var(--brand, #185FA5); }
        .lc-item.is-override .lc-av { background: #f59e0b; }
        .lc-bubble { flex: 1; min-width: 0; padding: 9px 12px; border-radius: 4px 12px 12px 12px; background: var(--admin-surface-soft, #f8fafc); border: 1px solid var(--admin-border, #e2e8f0); }
        .lc-item.is-override .lc-bubble { background: rgba(245, 158, 11, .08); border-color: rgba(245, 158, 11, .35); }
        .lc-meta { font-size: .76rem; color: var(--admin-muted, #64748b); margin-bottom: 2px; }
        .lc-meta b { color: var(--admin-text, #0f172a); }
        .lc-body { font-size: .9rem; white-space: pre-line; overflow-wrap: anywhere; }
        .lc-empty { font-size: .86rem; color: var(--admin-muted, #64748b); padding: 4px 0 12px; }
    </style>
    <div class="lc-head">
        <i class="bi bi-chat-left-text"></i><h6>Comments</h6><span class="lc-count">{{ $thread->count() }}</span>
    </div>

    @forelse($thread as $c)
        <div class="lc-item {{ $c->kind === 'override' ? 'is-override' : '' }}">
            <div class="lc-av" aria-hidden="true">{{ $c->initials }}</div>
            <div class="lc-bubble">
                <div class="lc-meta">
                    <b>{{ $c->author_name }}</b> · {{ $c->author_role }}
                    @if($c->kind === 'override') · <span class="text-warning-emphasis fw-semibold"><i class="bi bi-arrow-repeat"></i> Override</span>@endif
                    · {{ $c->created_at->timezone(config('attendance.timezone', 'Africa/Lusaka'))->format('d M Y, H:i') }}
                </div>
                <div class="lc-body">{{ $c->body }}</div>
            </div>
        </div>
    @empty
        <div class="lc-empty">No comments yet.@if($canComment) You're welcome to start the conversation.@endif</div>
    @endforelse

    @if($canComment)
        <form method="POST" action="{{ $commentUrl }}" class="mt-2">
            @csrf
            <label for="lcBody" class="form-label small fw-semibold mb-1">Add a comment</label>
            <textarea id="lcBody" name="body" rows="2" class="form-control" maxlength="3000" required placeholder="Share a thought, a question or some helpful context…">{{ old('body') }}</textarea>
            @error('body')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            <div class="d-flex align-items-center gap-2 mt-2 flex-wrap">
                <button class="btn btn-sm btn-primary"><i class="bi bi-send me-1"></i> Post comment</button>
                <small class="text-muted">The applicant and everyone in the approval chain will be emailed.</small>
            </div>
        </form>
    @endif
</div>
