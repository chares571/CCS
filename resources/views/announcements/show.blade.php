@php($prefix = auth()->user()->role === 'super_admin' ? 'master' : 'admin')
@extends(auth()->user()->role === 'super_admin' ? 'layouts.master-admin' : 'layouts.admin')

@section('page_title', 'Announcement')
@section('page_subtitle', 'Preview and manage announcement')

@section('content')
<section class="panel">
    <div class="panel-head">
        <div class="panel-head__title">
            <a class="btn btn-secondary" href="{{ route($prefix.'.announcements.index') }}">Back</a>
            <h2>{{ $announcement->title }}</h2>
        </div>

        <div class="panel-head__meta">
            @if(!$announcement->trashed() && $announcement->is_pinned)
                <span class="badge reviewed">PINNED</span>
            @endif
            <span class="badge {{ strtolower($announcement->statusLabel()) === 'published' ? 'approved' : (strtolower($announcement->statusLabel()) === 'deleted' ? 'rejected' : 'reviewed') }}">
                {{ $announcement->statusLabel() }}
            </span>
        </div>
    </div>

    <div class="feed-meta">
        By {{ $announcement->author?->full_name ?? 'User' }}
        &bull; Created {{ optional($announcement->created_at)->format('m/d/Y h:i A') ?? '-' }}
        @if($announcement->updated_at && $announcement->updated_at->ne($announcement->created_at))
            &bull; Updated {{ optional($announcement->updated_at)->format('m/d/Y h:i A') ?? '-' }}
        @endif
    </div>
    <div class="feed-meta">Publish: {{ optional($announcement->publish_at)->format('m/d/Y h:i A') ?? 'Immediate' }}</div>

    @if($announcement->updatedBy)
        <div class="feed-meta">Updated by: {{ $announcement->updatedBy->full_name }}</div>
    @endif

    @if($announcement->image_path)
        <button type="button" class="media-thumb mt-10" data-media-src="{{ '/storage/'.ltrim($announcement->image_path, '/') }}" data-media-caption="{{ $announcement->image_caption ?? '' }}" aria-label="View announcement image">
            <img src="{{ '/storage/'.ltrim($announcement->image_path, '/') }}" alt="Announcement image" class="img-preview">
        </button>
        @if($announcement->image_caption)
            <div class="feed-caption">{{ $announcement->image_caption }}</div>
        @endif
    @endif

    <div class="feed-content mt-10">
        {!! $announcement->renderedContent() !!}
    </div>

    <div class="action-inline action-inline--equal mt-12 announcement-actions">
        @if(!$announcement->trashed())
            <a class="btn btn-action-edit" href="{{ route($prefix.'.announcements.edit', $announcement) }}">Edit</a>

            @if($announcement->is_draft)
                <form method="POST" action="{{ route($prefix.'.announcements.publish', $announcement) }}" class="inline js-announcement-confirm" data-confirm-title="Publish Announcement" data-confirm-desc="Publish this announcement?" data-confirm-action="Publish">
                    @csrf
                    <button class="btn btn-action-publish" type="submit">Publish</button>
                </form>
            @else
                <form method="POST" action="{{ route($prefix.'.announcements.unpublish', $announcement) }}" class="inline js-announcement-confirm" data-confirm-title="Unpublish Announcement" data-confirm-desc="Move this announcement back to Drafts?" data-confirm-action="Unpublish">
                    @csrf
                    <button class="btn btn-action-unpublish" type="submit">Unpublish</button>
                </form>
            @endif

            @if(auth()->user()->role === 'super_admin')
                <form method="POST" action="{{ route($prefix.'.announcements.pin', $announcement) }}" class="inline">
                    @csrf
                    <button class="btn btn-action-pin" type="submit">{{ $announcement->is_pinned ? 'Unpin' : 'Pin' }}</button>
                </form>
            @endif

            <form
                method="POST"
                action="{{ route($prefix.'.announcements.destroy', $announcement) }}"
                class="inline js-announcement-confirm"
                data-confirm-title="Delete Announcement"
                data-confirm-desc="Delete this announcement and move it to the Deleted tab?"
                data-confirm-action="Delete"
            >
                @csrf
                @method('DELETE')
                <button class="btn btn-danger" type="submit">Delete</button>
            </form>
        @else
            <div class="feed-meta">
                Deleted: {{ optional($announcement->deleted_at)->format('m/d/Y h:i A') ?? '-' }}
                @if($announcement->deletedBy)
                    &bull; Deleted by {{ $announcement->deletedBy->full_name }}
                @endif
            </div>

            <form method="POST" action="{{ route($prefix.'.announcements.restore', $announcement->id) }}" class="inline">
                @csrf
                <button class="btn btn-success" type="submit">Restore</button>
            </form>

            @if(auth()->user()->role === 'super_admin')
                <form
                    method="POST"
                    action="{{ route($prefix.'.announcements.force-destroy', $announcement->id) }}"
                    class="inline js-announcement-confirm"
                    data-confirm-title="Delete Announcement Permanently"
                    data-confirm-desc="Permanently delete this announcement? This cannot be undone."
                    data-confirm-action="Delete Permanently"
                >
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-danger" type="submit">Delete Permanently</button>
                </form>
            @endif
        @endif
    </div>
</section>

<div class="logout-modal" id="announcementConfirmModal" aria-hidden="true">
    <div class="logout-modal__backdrop" data-dismiss-announcement-confirm></div>
    <div class="logout-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="announcementConfirmTitle" aria-describedby="announcementConfirmDesc">
        <div class="logout-modal__icon" aria-hidden="true">!</div>
        <h2 id="announcementConfirmTitle">Confirm Action</h2>
        <p id="announcementConfirmDesc">Are you sure?</p>
        <div class="logout-modal__actions">
            <button type="button" class="btn btn-danger" data-dismiss-announcement-confirm>Cancel</button>
            <button type="button" class="btn btn-success" id="announcementConfirmBtn">Confirm</button>
        </div>
    </div>
</div>

<div class="logout-modal media-modal" id="mediaModal" aria-hidden="true">
    <div class="logout-modal__backdrop" data-dismiss-media></div>
    <div class="logout-modal__dialog media-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="mediaModalTitle">
        <h2 id="mediaModalTitle" class="sr-only">Image preview</h2>
        <img class="media-modal__image" alt="Preview" />
        <p class="media-modal__caption muted" hidden></p>
        <div class="logout-modal__actions">
            <button type="button" class="btn btn-secondary" data-dismiss-media>Close</button>
        </div>
    </div>
</div>

<script>
(() => {
    const modal = document.getElementById('announcementConfirmModal');
    const confirmBtn = document.getElementById('announcementConfirmBtn');
    const titleEl = document.getElementById('announcementConfirmTitle');
    const descEl = document.getElementById('announcementConfirmDesc');
    const forms = document.querySelectorAll('.js-announcement-confirm');

    if (modal && confirmBtn && titleEl && descEl) {
        const dismissButtons = modal.querySelectorAll('[data-dismiss-announcement-confirm]');
        let pendingForm = null;
        let pendingCallback = null;

        const openModal = ({ title, desc, actionLabel, form, callback } = {}) => {
            pendingForm = form || null;
            pendingCallback = typeof callback === 'function' ? callback : null;

            titleEl.textContent = title || 'Confirm Action';
            descEl.textContent = desc || 'Are you sure?';
            confirmBtn.textContent = actionLabel || 'Confirm';
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
            confirmBtn.focus();
        };

        const closeModal = () => {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');
            pendingForm = null;
            pendingCallback = null;
        };

        forms.forEach((form) => {
            form.addEventListener('submit', (event) => {
                if (form.dataset.skipConfirm === '1') return;
                event.preventDefault();
                openModal({
                    form,
                    title: form.dataset.confirmTitle || 'Confirm Action',
                    desc: form.dataset.confirmDesc || 'Are you sure?',
                    actionLabel: form.dataset.confirmAction || 'Confirm',
                });
            });
        });

        confirmBtn.addEventListener('click', () => {
            if (!pendingForm && pendingCallback) {
                pendingCallback();
                closeModal();
                return;
            }

            if (pendingForm) {
                pendingForm.dataset.skipConfirm = '1';
                pendingForm.submit();
            }
        });

        dismissButtons.forEach((button) => button.addEventListener('click', closeModal));
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && modal.classList.contains('is-open')) closeModal();
        });

        window.__openAnnouncementAlert = (title, desc, actionLabel = 'OK') => {
            openModal({
                title,
                desc,
                actionLabel,
                callback: () => {},
            });
        };
    }

    const mediaModal = document.getElementById('mediaModal');
    if (mediaModal) {
        const img = mediaModal.querySelector('.media-modal__image');
        const caption = mediaModal.querySelector('.media-modal__caption');
        const dismiss = mediaModal.querySelectorAll('[data-dismiss-media]');

        const open = (src, text) => {
            img.src = src;
            caption.textContent = text || '';
            caption.hidden = !text;
            mediaModal.classList.add('is-open');
            mediaModal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
        };

        const close = () => {
            mediaModal.classList.remove('is-open');
            mediaModal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');
            img.removeAttribute('src');
        };

        document.querySelectorAll('[data-media-src]').forEach((button) => {
            button.addEventListener('click', () => open(button.dataset.mediaSrc, button.dataset.mediaCaption || ''));
        });

        dismiss.forEach((b) => b.addEventListener('click', close));
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && mediaModal.classList.contains('is-open')) close();
        });
    }
})();
</script>
@endsection
