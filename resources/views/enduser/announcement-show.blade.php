@extends('layouts.enduser')

@section('page_title', 'Announcement')
@section('page_subtitle', 'Official update')

@section('content')
<section class="panel">
    <div class="panel-head">
        <div class="panel-head__title">
            <a class="btn btn-secondary btn-icon" href="{{ route('homepage.feed') }}" aria-label="Back to feed">
                <x-icon name="back" />
                <span class="sr-only">Back</span>
            </a>
            <h2>{{ $announcement->title }}</h2>
        </div>
        <span class="badge approved">PUBLISHED</span>
    </div>

    <div class="feed-meta">
        Publish: {{ optional($announcement->publish_at)->format('m/d/Y h:i A') ?? 'Immediate' }}
    </div>

    @if($announcement->image_path)
        <button type="button" class="media-thumb" data-media-src="{{ '/storage/'.ltrim($announcement->image_path, '/') }}" data-media-caption="{{ $announcement->image_caption ?? '' }}" aria-label="View announcement image">
            <img src="{{ '/storage/'.ltrim($announcement->image_path, '/') }}" alt="Announcement image" class="img-preview">
        </button>
        @if($announcement->image_caption)
            <div class="feed-caption">{{ $announcement->image_caption }}</div>
        @endif
    @endif

    <div class="feed-content mt-10">
        {!! $announcement->renderedContent() !!}
    </div>
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
    const modal = document.getElementById('mediaModal');
    if (!modal) return;

    const img = modal.querySelector('.media-modal__image');
    const caption = modal.querySelector('.media-modal__caption');
    const dismiss = modal.querySelectorAll('[data-dismiss-media]');

    const open = (src, text) => {
        img.src = src;
        caption.textContent = text || '';
        caption.hidden = !text;
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
    };

    const close = () => {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
        img.removeAttribute('src');
    };

    document.querySelectorAll('[data-media-src]').forEach((button) => {
        button.addEventListener('click', () => open(button.dataset.mediaSrc, button.dataset.mediaCaption || ''));
    });

    dismiss.forEach((b) => b.addEventListener('click', close));
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.classList.contains('is-open')) close();
    });
})();
</script>
@endsection
