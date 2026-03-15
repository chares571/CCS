@php($prefix = auth()->user()->role === 'super_admin' ? 'master' : 'admin')
@extends(auth()->user()->role === 'super_admin' ? 'layouts.master-admin' : 'layouts.admin')

@section('page_title', 'Announcements')
@section('page_subtitle', 'Create, schedule, and manage official posts')

@section('content')
<section class="panel">
    <div class="panel-head">
        <h2>Announcements Management</h2>
        <a class="btn" href="{{ route($prefix.'.announcements.create') }}">Create Announcement</a>
    </div>

    <nav class="grade-quick-nav announcement-quick-nav" aria-label="Announcements filters">
        <a class="grade-quick-nav-link {{ ($tab ?? 'drafts') === 'drafts' ? 'is-active' : '' }}" href="{{ route($prefix.'.announcements.index', ['tab' => 'drafts']) }}">
            Drafts
            @if(isset($counts['drafts']))
                <span class="announcement-quick-nav-count">{{ $counts['drafts'] }}</span>
            @endif
        </a>
        <a class="grade-quick-nav-link {{ ($tab ?? 'drafts') === 'scheduled' ? 'is-active' : '' }}" href="{{ route($prefix.'.announcements.index', ['tab' => 'scheduled']) }}">
            Scheduled
            @if(isset($counts['scheduled']))
                <span class="announcement-quick-nav-count">{{ $counts['scheduled'] }}</span>
            @endif
        </a>
        <a class="grade-quick-nav-link {{ ($tab ?? 'drafts') === 'published' ? 'is-active' : '' }}" href="{{ route($prefix.'.announcements.index', ['tab' => 'published']) }}">
            Published
            @if(isset($counts['published']))
                <span class="announcement-quick-nav-count">{{ $counts['published'] }}</span>
            @endif
        </a>
        <span class="announcement-quick-nav-divider" aria-hidden="true"></span>
        <a class="grade-quick-nav-link announcement-quick-nav-link--danger {{ ($tab ?? 'drafts') === 'deleted' ? 'is-active' : '' }}" href="{{ route($prefix.'.announcements.index', ['tab' => 'deleted']) }}">
            Deleted
            @if(isset($counts['deleted']))
                <span class="announcement-quick-nav-count">{{ $counts['deleted'] }}</span>
            @endif
        </a>
    </nav>

    <form method="GET" action="{{ route($prefix.'.announcements.index') }}" class="action-inline mt-10">
        <input type="hidden" name="tab" value="{{ $tab ?? 'drafts' }}">
        <div style="min-width: 240px;">
            <label>Search</label>
            <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Title, content, author...">
        </div>
        <div style="min-width: 180px;">
            <label>Pinned</label>
            @php($pinnedValue = $pinned ?? 'all')
            <select name="pinned">
                <option value="all" {{ $pinnedValue === 'all' ? 'selected' : '' }}>All</option>
                <option value="pinned" {{ $pinnedValue === 'pinned' ? 'selected' : '' }}>Pinned</option>
                <option value="unpinned" {{ $pinnedValue === 'unpinned' ? 'selected' : '' }}>Not pinned</option>
            </select>
        </div>
        <div style="min-width: 180px;">
            <label>From</label>
            <input type="date" name="from" value="{{ $from ?? '' }}">
        </div>
        <div style="min-width: 180px;">
            <label>To</label>
            <input type="date" name="to" value="{{ $to ?? '' }}">
        </div>
        <div style="min-width: 200px;">
            <label>Sort</label>
            <select name="sort">
                @php($sortValue = $sort ?? 'newest')
                <option value="newest" {{ $sortValue === 'newest' ? 'selected' : '' }}>Newest</option>
                <option value="oldest" {{ $sortValue === 'oldest' ? 'selected' : '' }}>Oldest</option>
                <option value="publish_newest" {{ $sortValue === 'publish_newest' ? 'selected' : '' }}>Publish time (Newest)</option>
                <option value="publish_oldest" {{ $sortValue === 'publish_oldest' ? 'selected' : '' }}>Publish time (Oldest)</option>
                <option value="title_az" {{ $sortValue === 'title_az' ? 'selected' : '' }}>Title (A-Z)</option>
                <option value="title_za" {{ $sortValue === 'title_za' ? 'selected' : '' }}>Title (Z-A)</option>
            </select>
        </div>
        <div style="min-width: 160px;">
            <label>Per page</label>
            @php($perPageValue = (int) ($perPage ?? 10))
            <select name="per_page">
                <option value="10" {{ $perPageValue === 10 ? 'selected' : '' }}>10</option>
                <option value="25" {{ $perPageValue === 25 ? 'selected' : '' }}>25</option>
                <option value="50" {{ $perPageValue === 50 ? 'selected' : '' }}>50</option>
            </select>
        </div>
        <div style="align-self: end;">
            <button class="btn announcement-filter-btn" type="submit">Apply</button>
        </div>
        <div style="align-self: end;">
            <a class="btn btn-secondary announcement-filter-btn" href="{{ route($prefix.'.announcements.index', ['tab' => $tab ?? 'drafts', 'reset' => 1]) }}">Reset</a>
        </div>
    </form>

    <form
        method="POST"
        action="{{ route($prefix.'.announcements.bulk') }}"
        class="panel announcement-bulk-bar js-announcement-bulk js-announcement-confirm mt-12"
        data-confirm-title="Apply Bulk Action"
        data-confirm-desc="Apply this action to the selected announcements?"
        data-confirm-action="Confirm"
    >
        @csrf
        <div class="announcement-bulk-bar__left">
            <label class="announcement-bulk-check" title="Select all">
                <input type="checkbox" data-bulk-select-all>
                <span class="muted">Select all</span>
            </label>
            <span class="muted"><span data-bulk-selected>0</span> selected</span>
        </div>
        <div class="announcement-bulk-bar__right">
            <select name="action" required>
                <option value="" selected disabled>Bulk action...</option>
                <option value="publish">Publish</option>
                <option value="unpublish">Unpublish</option>
                <option value="delete">Delete</option>
                <option value="restore">Restore</option>
                @if(auth()->user()->role === 'super_admin')
                    <option value="pin">Pin</option>
                    <option value="unpin">Unpin</option>
                    <option value="force_delete">Delete permanently</option>
                @endif
            </select>
            <button class="btn" type="submit">Apply</button>
        </div>
        <div data-bulk-ids></div>
    </form>

    @forelse($announcements as $a)
        <article class="feed-post">
            <div class="feed-post-head">
                <div style="display:flex; align-items:center; gap:10px;">
                    <label class="announcement-select" aria-label="Select announcement">
                        <input type="checkbox" value="{{ $a->id }}" data-bulk-item>
                    </label>
                    <h3>
                        @if(($tab ?? 'drafts') !== 'deleted')
                            <a class="link-plain" href="{{ route($prefix.'.announcements.show', $a) }}">{{ $a->title }}</a>
                        @else
                            {{ $a->title }}
                        @endif
                    </h3>
                </div>
                <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap; justify-content:flex-end;">
                    @if(($tab ?? 'drafts') !== 'deleted' && !$a->trashed() && $a->is_pinned)
                        <span class="badge reviewed">PINNED</span>
                    @endif
                    <span class="badge {{ strtolower($a->statusLabel()) === 'published' ? 'approved' : (strtolower($a->statusLabel()) === 'deleted' ? 'rejected' : 'reviewed') }}">
                        {{ $a->statusLabel() }}
                    </span>
                </div>
            </div>
            @if(($tab ?? 'drafts') === 'deleted')
                <div class="feed-meta">Deleted: {{ optional($a->deleted_at)->format('m/d/Y h:i A') ?? '-' }}</div>
            @endif
            <div class="feed-meta">
                By {{ $a->author?->full_name ?? 'User' }}
                &bull; Created {{ optional($a->created_at)->format('m/d/Y h:i A') ?? '-' }}
                @if($a->updated_at && $a->updated_at->ne($a->created_at))
                    &bull; Updated {{ optional($a->updated_at)->format('m/d/Y h:i A') ?? '-' }}
                @endif
            </div>
            <div class="feed-meta">Publish: {{ optional($a->publish_at)->format('m/d/Y h:i A') ?? 'Immediate' }}</div>
            <div class="feed-content feed-content--clamp" data-readmore>
                {!! $a->renderedContent() !!}
            </div>
            <button type="button" class="feed-readmore" data-readmore-btn hidden>Read more</button>
            @if($a->image_path)
                <button type="button" class="media-thumb" data-media-src="{{ '/storage/'.ltrim($a->image_path, '/') }}" data-media-caption="{{ $a->image_caption ?? '' }}" aria-label="View announcement image">
                    <img src="{{ '/storage/'.ltrim($a->image_path, '/') }}" alt="Announcement image" class="img-preview">
                </button>
                @if($a->image_caption)
                    <div class="feed-caption">{{ $a->image_caption }}</div>
                @endif
            @endif
            <div class="action-inline action-inline--equal mt-10 announcement-actions">
                @if(($tab ?? 'drafts') === 'deleted')
                    <form method="POST" action="{{ route($prefix.'.announcements.restore', $a->id) }}" class="inline">
                        @csrf
                        <button class="btn btn-success" type="submit">Restore</button>
                    </form>
                    @if(auth()->user()->role === 'super_admin')
                        <form method="POST" action="{{ route($prefix.'.announcements.force-destroy', $a->id) }}" class="inline js-announcement-confirm" data-confirm-title="Delete Announcement Permanently" data-confirm-desc="Permanently delete this announcement? This cannot be undone." data-confirm-action="Delete Permanently">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger" type="submit">Delete Permanently</button>
                        </form>
                    @endif
                @else
                    @if($a->is_draft)
                        <form method="POST" action="{{ route($prefix.'.announcements.publish', $a) }}" class="inline js-announcement-confirm" data-confirm-title="Publish Announcement" data-confirm-desc="Publish this announcement?" data-confirm-action="Publish">
                            @csrf
                            <button class="btn btn-action-publish" type="submit">Publish</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route($prefix.'.announcements.unpublish', $a) }}" class="inline js-announcement-confirm" data-confirm-title="Unpublish Announcement" data-confirm-desc="Move this announcement back to Drafts?" data-confirm-action="Unpublish">
                            @csrf
                            <button class="btn btn-action-unpublish" type="submit">Unpublish</button>
                        </form>
                    @endif
                    <a class="btn btn-action-view" href="{{ route($prefix.'.announcements.show', $a) }}">View</a>
                    <a class="btn btn-action-edit" href="{{ route($prefix.'.announcements.edit', $a) }}">Edit</a>
                    @if(auth()->user()->role === 'super_admin')
                        <form method="POST" action="{{ route($prefix.'.announcements.pin', $a) }}" class="inline">
                            @csrf
                            <button class="btn btn-action-pin" type="submit">{{ $a->is_pinned ? 'Unpin' : 'Pin' }}</button>
                        </form>
                    @endif
                    <form
                        method="POST"
                        action="{{ route($prefix.'.announcements.destroy', $a) }}"
                        class="inline js-announcement-confirm"
                        data-confirm-title="Delete Announcement"
                        data-confirm-desc="Delete this announcement and move it to the Deleted tab?"
                        data-confirm-action="Delete"
                    >
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger" type="submit">Delete</button>
                    </form>
                @endif
            </div>
        </article>
    @empty
        <p>No announcements found.</p>
    @endforelse

    <div class="pagination-wrap">{{ $announcements->links() }}</div>
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
                if (form.dataset.skipConfirm === '1') {
                    return;
                }

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
            if (event.key === 'Escape' && modal.classList.contains('is-open')) {
                closeModal();
            }
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

    const bulkForm = document.querySelector('.js-announcement-bulk');
    const bulkIds = bulkForm ? bulkForm.querySelector('[data-bulk-ids]') : null;
    const bulkSelected = bulkForm ? bulkForm.querySelector('[data-bulk-selected]') : null;
    const bulkSelectAll = bulkForm ? bulkForm.querySelector('[data-bulk-select-all]') : null;
    const bulkItems = Array.from(document.querySelectorAll('[data-bulk-item]'));

    const getCheckedIds = () => bulkItems.filter((c) => c.checked).map((c) => c.value);

    const refreshBulk = () => {
        const count = getCheckedIds().length;
        if (bulkSelected) bulkSelected.textContent = String(count);

        if (bulkSelectAll) {
            bulkSelectAll.checked = count > 0 && count === bulkItems.length;
            bulkSelectAll.indeterminate = count > 0 && count < bulkItems.length;
        }
    };

    if (bulkForm && bulkIds && bulkItems.length) {
        bulkItems.forEach((c) => c.addEventListener('change', refreshBulk));
        refreshBulk();

        bulkSelectAll && bulkSelectAll.addEventListener('change', () => {
            bulkItems.forEach((c) => (c.checked = bulkSelectAll.checked));
            refreshBulk();
        });

        bulkForm.addEventListener('submit', (event) => {
            const ids = getCheckedIds();
            if (!ids.length) {
                event.preventDefault();
                if (typeof window.__openAnnouncementAlert === 'function') {
                    window.__openAnnouncementAlert('Selection Required', 'Select at least one announcement.', 'OK');
                }
                return;
            }

            bulkIds.innerHTML = '';
            ids.forEach((id) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = id;
                bulkIds.appendChild(input);
            });
        }, { capture: true });

        bulkForm.addEventListener('submit', () => {
            const actionSelect = bulkForm.querySelector('select[name="action"]');
            const action = actionSelect ? actionSelect.value : '';
            const count = getCheckedIds().length;

            const labels = {
                publish: ['Publish announcements', `Publish ${count} announcement(s)?`, 'Publish'],
                unpublish: ['Unpublish announcements', `Move ${count} announcement(s) back to Drafts?`, 'Unpublish'],
                delete: ['Delete announcements', `Delete ${count} announcement(s) and move them to Deleted?`, 'Delete'],
                restore: ['Restore announcements', `Restore ${count} announcement(s)?`, 'Restore'],
                pin: ['Pin announcements', `Pin ${count} announcement(s)?`, 'Pin'],
                unpin: ['Unpin announcements', `Unpin ${count} announcement(s)?`, 'Unpin'],
                force_delete: ['Delete permanently', `Permanently delete ${count} announcement(s)? This cannot be undone.`, 'Delete Permanently'],
            };

            const payload = labels[action] || ['Apply Bulk Action', `Apply this action to ${count} announcement(s)?`, 'Confirm'];
            bulkForm.dataset.confirmTitle = payload[0];
            bulkForm.dataset.confirmDesc = payload[1];
            bulkForm.dataset.confirmAction = payload[2];
        }, { capture: true });
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

    const blocks = document.querySelectorAll('[data-readmore]');
    blocks.forEach((block) => {
        const btn = block.parentElement?.querySelector('[data-readmore-btn]');
        if (!btn) return;

        const refresh = () => {
            const clipped = block.scrollHeight > block.clientHeight + 2;
            btn.hidden = !clipped && !block.classList.contains('is-expanded');
        };

        btn.addEventListener('click', () => {
            block.classList.toggle('is-expanded');
            btn.textContent = block.classList.contains('is-expanded') ? 'Show less' : 'Read more';
            btn.hidden = false;
        });

        requestAnimationFrame(refresh);
        window.addEventListener('resize', refresh);
    });
})();
</script>
@endsection
