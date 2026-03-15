@extends('layouts.enduser')

@section('page_title', 'Information Feed')
@section('page_subtitle', 'School announcements and official updates.')

@section('content')
@if($application)
    <section class="panel enrollment-status-panel">
        <div class="panel-head">
            <h2><span class="icon-inline"><x-icon name="timeline" /> Enrollment Status</span></h2>
        </div>
        @php $accountRole = auth()->user()->role ?? null; @endphp
        <div class="enduser-status-strip enrollment-status-card">
            <div class="enrollment-status-main">
                <p class="muted enrollment-status-label">Your Current Enrollment Status</p>
                @if(($enrolledCount ?? 0) > 0)
                    @if($accountRole === 'parent')
                        <strong class="enrollment-status-message">
                            Congaratulations! Enrollment confirmed for S.Y. {{ $currentSchoolYearLabel ?? 'CURRENT SCHOOL YEAR' }}:
                            {{ $enrolledLearnerNamesText ?: 'your child/children' }}.
                        </strong>
                    @elseif($accountRole === 'student')
                        <strong class="enrollment-status-message">Congratulations! You are now enrolled for S.Y. {{ $currentSchoolYearLabel ?? 'CURRENT SCHOOL YEAR' }}.</strong>
                    @else
                        <strong class="enrollment-status-message">Congratulations! You are now enrolled for S.Y. {{ $currentSchoolYearLabel ?? 'CURRENT SCHOOL YEAR' }}.</strong>
                    @endif
                @else
                    <span class="badge {{ $application->status }}">{{ \App\Support\StatusLabel::for($application->status) }}</span>
                @endif
            </div>
        </div>
    </section>
@endif

<section class="panel">
    <div class="panel-head">
        <h2><span class="icon-inline"><x-icon name="announcements" /> Announcement Feed</span></h2>
    </div>

    @forelse($announcements as $a)
        <article class="feed-post">
            <div class="feed-post-head">
                <h4>
                    <a class="link-plain" href="{{ route('homepage.announcements.show', $a) }}">
                        {{ $a->title }}
                    </a>
                </h4>
            </div>
            <div class="feed-meta">{{ optional($a->publish_at)->format('m/d/Y h:i A') ?? $a->created_at->format('m/d/Y h:i A') }}</div>
            <div class="feed-content feed-content--clamp" data-readmore>
                {!! $a->renderedContent() !!}
            </div>
            <button type="button" class="feed-readmore" data-readmore-btn hidden>Read more</button>
            @if($a->image_path)
                <button type="button" class="media-thumb" data-media-src="{{ '/storage/'.ltrim($a->image_path, '/') }}" data-media-caption="{{ $a->image_caption ?? '' }}" aria-label="View announcement image">
                    <img src="{{ '/storage/'.ltrim($a->image_path, '/') }}" alt="Announcement image">
                </button>
                @if($a->image_caption)
                    <div class="feed-caption">{{ $a->image_caption }}</div>
                @endif
            @endif
        </article>
    @empty
        <p>No announcements available.</p>
    @endforelse
</section>

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
