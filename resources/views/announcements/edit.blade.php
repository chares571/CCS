@php($prefix = auth()->user()->role === 'super_admin' ? 'master' : 'admin')
@extends(auth()->user()->role === 'super_admin' ? 'layouts.master-admin' : 'layouts.admin')

@section('page_title', 'Edit Announcement')
@section('page_subtitle', 'Update existing announcement content')

@section('content')
<section class="panel">
    <div class="panel-head">
        <div class="panel-head__title">
            <a class="btn btn-secondary btn-icon" href="{{ route($prefix.'.announcements.index') }}" aria-label="Back to announcements">
                <x-icon name="back" />
                <span class="sr-only">Back</span>
            </a>
            <h2>Edit Announcement</h2>
        </div>
    </div>
    <form method="POST" action="{{ route($prefix.'.announcements.update', $announcement) }}" enctype="multipart/form-data" class="js-announcement-form">
        @csrf
        @method('PUT')

        <input type="hidden" name="remove_image" value="{{ old('remove_image') ? 1 : 0 }}" data-remove-image>

        @if($errors->any())
            <div class="alert alert-error" role="alert">
                <strong>Please fix the errors below.</strong>
                <ul style="margin: 8px 0 0 18px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <label>Title</label>
        <input type="text" name="title" value="{{ old('title', $announcement->title) }}" required class="announcement-title-input">

        <label>Content</label>
        <div class="md-toolbar" data-md-toolbar hidden>
            <button type="button" data-md="undo" title="Undo" aria-label="Undo">
                <x-icon name="undo" />
                <span class="sr-only">Undo</span>
            </button>
            <button type="button" data-md="redo" title="Redo" aria-label="Redo">
                <x-icon name="redo" />
                <span class="sr-only">Redo</span>
            </button>
            <span class="md-toolbar__divider" aria-hidden="true"></span>
            <button type="button" data-md="bold" title="Bold">Bold</button>
            <button type="button" data-md="italic" title="Italic">Italic</button>
            <button type="button" data-md="link" title="Insert link">Link</button>
            <button type="button" data-md="ul" title="Bulleted list">List</button>
            <button type="button" data-md="quote" title="Block quote">Quote</button>
        </div>
        <textarea name="content" rows="7" required>{{ old('content', $announcement->content) }}</textarea>
        <div class="md-editor" data-content-editor hidden contenteditable="true" role="textbox" aria-multiline="true" spellcheck="true"></div>

        <div class="action-inline mt-10">
            <input type="hidden" name="content_format" value="{{ old('content_format', $announcement->content_format ?? 'plain') }}">
            <input type="hidden" name="is_draft" value="{{ old('is_draft', (bool) ($announcement->is_draft ?? false)) ? 1 : 0 }}">
            <div style="min-width: 240px;">
                <label>Schedule Publish Time (optional)</label>
                <input type="datetime-local" lang="en-US" name="publish_at" value="{{ old('publish_at', optional($announcement->publish_at)->format('Y-m-d\\TH:i')) }}">
            </div>
        </div>

        @if($announcement->image_path)
            <div class="mt-10">
                <label>Current Image</label>
                <div data-current-image>
                    <button type="button" class="media-thumb" data-media-src="{{ '/storage/'.ltrim($announcement->image_path, '/') }}" data-media-caption="{{ $announcement->image_caption ?? '' }}" aria-label="View announcement image">
                        <img src="{{ '/storage/'.ltrim($announcement->image_path, '/') }}" alt="Announcement image" class="img-preview">
                    </button>
                    @if($announcement->image_caption)
                        <div class="feed-caption">{{ $announcement->image_caption }}</div>
                    @endif
                </div>
                <div class="action-inline mt-8" style="justify-content:flex-end;">
                    <button type="button" class="btn btn-danger btn-icon" data-remove-image-btn aria-label="Delete photo" title="Delete photo">
                        <x-icon name="delete" />
                    </button>
                </div>
                <p class="muted" data-image-removed-note hidden style="margin: 8px 0 0;">Photo marked for removal. Save to apply.</p>
            </div>
        @endif

        <label>Replace Image (optional, JPG/JPEG/PNG)</label>
        <input type="file" name="image" accept=".jpg,.jpeg,.png">

        <label>Image Caption (optional)</label>
        <input type="text" name="image_caption" value="{{ old('image_caption', $announcement->image_caption) }}">

        <div class="action-inline action-inline--equal mt-10">
            <button class="btn btn-secondary" type="button" data-save-draft>Save Draft</button>
            <button class="btn" type="button" data-save>Save Changes</button>
        </div>
        <p class="muted" data-autosave-status style="margin: 8px 0 0;"></p>
    </form>
</section>

<script>
(() => {
    const mediaModalId = 'mediaModal';
    if (!document.getElementById(mediaModalId)) {
        const wrapper = document.createElement('div');
        wrapper.innerHTML = `
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
</div>`;
        document.body.appendChild(wrapper.firstElementChild);
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

    const form = document.querySelector('.js-announcement-form');
    if (!form) return;

    const title = form.querySelector('input[name="title"]');
    const contentTextarea = form.querySelector('textarea[name="content"]');
    const contentEditor = form.querySelector('[data-content-editor]');
    const publish = form.querySelector('input[name="publish_at"]');
    const draft = form.querySelector('input[name="is_draft"]');
    const format = form.querySelector('input[name="content_format"]');
    const caption = form.querySelector('input[name="image_caption"]');
    const toolbar = form.querySelector('[data-md-toolbar]');
    const autosaveStatus = form.querySelector('[data-autosave-status]');
    const removeImage = form.querySelector('[data-remove-image]');
    const removeImageBtn = form.querySelector('[data-remove-image-btn]');
    const currentImage = form.querySelector('[data-current-image]');
    const imageRemovedNote = form.querySelector('[data-image-removed-note]');

    const escapeHtml = (value) => {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    };

    const renderInlineEscaped = (escaped) => {
        let html = String(escaped || '');

        html = html.replace(/\[([^\]]+)\]\(([^)\s]+)\)/g, (match, text, url) => {
            const href = String(url || '');
            if (!/^https?:\/\//i.test(href)) {
                return match;
            }
            return `<a href="${href}" target="_blank" rel="noopener noreferrer">${text}</a>`;
        });

        html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        html = html.replace(/_(.+?)_/g, '<em>$1</em>');

        return html;
    };

    const renderMarkdown = (value) => {
        const lines = escapeHtml(value).split(/\r?\n/);
        let html = '';
        let inList = false;
        let inQuote = false;

        const closeList = () => {
            if (inList) {
                html += '</ul>';
                inList = false;
            }
        };

        const closeQuote = () => {
            if (inQuote) {
                html += '</blockquote>';
                inQuote = false;
            }
        };

        lines.forEach((line) => {
            if (/^\s*-\s+/.test(line)) {
                closeQuote();
                if (!inList) {
                    html += '<ul>';
                    inList = true;
                }
                html += `<li>${renderInlineEscaped(line.replace(/^\s*-\s+/, ''))}</li>`;
                return;
            }

            closeList();

            if (/^\s*>\s?/.test(line)) {
                if (!inQuote) {
                    html += '<blockquote>';
                    inQuote = true;
                }
                html += `<p>${renderInlineEscaped(line.replace(/^\s*>\s?/, ''))}</p>`;
                return;
            }

            closeQuote();

            if (line.trim() === '') {
                html += '<br>';
                return;
            }

            html += `<p>${renderInlineEscaped(line)}</p>`;
        });

        closeList();
        closeQuote();

        return html;
    };

    const editorToMarkdown = (root) => {
        const normalize = (value) => {
            return String(value || '')
                .replace(/\u00A0/g, ' ')
                .replace(/[ \t]+\n/g, '\n')
                .replace(/\n{3,}/g, '\n\n')
                .trimEnd();
        };

        const prefixQuote = (value) => {
            const lines = String(value || '').split('\n');
            return lines.map((l) => (l.trim() === '' ? '' : '> ' + l)).join('\n');
        };

        const nodeToMd = (node) => {
            if (!node) return '';

            if (node.nodeType === Node.TEXT_NODE) {
                return node.nodeValue || '';
            }

            if (node.nodeType !== Node.ELEMENT_NODE) {
                return '';
            }

            const el = node;
            const tag = (el.tagName || '').toLowerCase();
            const children = Array.from(el.childNodes).map(nodeToMd).join('');

            if (tag === 'br') return '\n';
            if (tag === 'strong' || tag === 'b') return `**${children}**`;
            if (tag === 'em' || tag === 'i') return `_${children}_`;

            if (tag === 'a') {
                const href = (el.getAttribute('href') || '').trim();
                if (!href) return children;
                return `[${children}](${href})`;
            }

            if (tag === 'li') {
                return children.replace(/\n+/g, ' ').trim();
            }

            if (tag === 'ul') {
                const items = Array.from(el.querySelectorAll(':scope > li')).map((li) => `- ${nodeToMd(li)}`);
                return items.join('\n') + '\n\n';
            }

            if (tag === 'blockquote') {
                const inner = normalize(children);
                return prefixQuote(inner) + '\n\n';
            }

            if (tag === 'p' || tag === 'div') {
                const inner = normalize(children);
                return inner === '' ? '' : inner + '\n\n';
            }

            return children;
        };

        return normalize(nodeToMd(root));
    };

    const refreshToolbar = () => {
        const isMarkdown = (format?.value || 'plain') === 'markdown';
        if (!toolbar) return;
        toolbar.hidden = false;
        toolbar.classList.toggle('md-toolbar--disabled', !isMarkdown);

        if (contentTextarea && contentEditor) {
            if (isMarkdown) {
                contentEditor.hidden = false;
                contentTextarea.hidden = true;
                contentEditor.innerHTML = renderMarkdown(contentTextarea.value || '');
            } else {
                contentEditor.hidden = true;
                contentTextarea.hidden = false;
            }
        }
    };

    format && format.addEventListener('change', () => {
        refreshToolbar();
    });

    const saveDraftBtn = form.querySelector('[data-save-draft]');
    const saveBtn = form.querySelector('[data-save]');

    saveDraftBtn && saveDraftBtn.addEventListener('click', () => {
        if (draft) draft.value = '1';
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            form.submit();
        }
    });

    saveBtn && saveBtn.addEventListener('click', () => {
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            form.submit();
        }
    });

    const applyRemoveImageUi = () => {
        if (currentImage) currentImage.style.display = 'none';
        if (imageRemovedNote) imageRemovedNote.hidden = false;
        if (caption) caption.value = '';
    };

    if (removeImage && String(removeImage.value || '') === '1') {
        applyRemoveImageUi();
    }

    removeImageBtn && removeImageBtn.addEventListener('click', () => {
        if (removeImage) removeImage.value = '1';
        applyRemoveImageUi();
        if (typeof scheduleAutosave === 'function') {
            scheduleAutosave();
        }
    });

    const storageKey = `ccs.announcements.autosave.edit.${form.getAttribute('action')}`;
    let dirty = false;
    let autosaveTimer = null;

    const saveAutosave = () => {
        try {
            const payload = {
                title: title?.value || '',
                content: contentTextarea?.value || '',
                content_format: format?.value || 'plain',
                publish_at: publish?.value || '',
                is_draft: (draft?.value || '0') === '1',
                image_caption: caption?.value || '',
                saved_at: Date.now(),
            };
            localStorage.setItem(storageKey, JSON.stringify(payload));
            if (autosaveStatus) autosaveStatus.textContent = `Autosaved ${new Date(payload.saved_at).toLocaleTimeString()}`;
        } catch (e) {}
    };

    const scheduleAutosave = () => {
        dirty = true;
        if (autosaveTimer) window.clearTimeout(autosaveTimer);
        autosaveTimer = window.setTimeout(saveAutosave, 450);
    };

    const loadAutosave = () => {
        try {
            const raw = localStorage.getItem(storageKey);
            if (!raw) return null;
            return JSON.parse(raw);
        } catch (e) {
            return null;
        }
    };

    const applyAutosave = (payload) => {
        if (!payload) return;
        if (title) title.value = payload.title || '';
        if (contentTextarea) contentTextarea.value = payload.content || '';
        if (format) format.value = payload.content_format || 'plain';
        if (publish) publish.value = payload.publish_at || '';
        if (draft) draft.value = payload.is_draft ? '1' : '0';
        if (caption) caption.value = payload.image_caption || '';
        refreshToolbar();
    };

    // Note: autosave banner intentionally removed (keeps UI clean); autosave still runs silently.

    [title, contentTextarea, publish, caption, format].forEach((el) => el && el.addEventListener('input', scheduleAutosave));
    format && format.addEventListener('change', scheduleAutosave);

    window.addEventListener('beforeunload', (event) => {
        if (!dirty) return;
        event.preventDefault();
        event.returnValue = '';
    });

    form.addEventListener('submit', () => {
        dirty = false;
        try { localStorage.removeItem(storageKey); } catch (e) {}
    });

    if (toolbar && contentTextarea) {
        const ensureMarkdown = () => {
            if (!format) return;
            if ((format.value || 'plain') === 'markdown') return;
            format.value = 'markdown';
            refreshToolbar();
            scheduleAutosave();
        };

        const syncFromEditor = () => {
            if (!contentEditor || !contentTextarea) return;
            if (contentEditor.hidden) return;
            contentTextarea.value = editorToMarkdown(contentEditor);
            scheduleAutosave();
        };

        const exec = (command, value = null) => {
            if (!contentEditor || contentEditor.hidden) return false;
            contentEditor.focus();
            try {
                return document.execCommand(command, false, value);
            } catch (e) {
                return false;
            }
        };

        if (contentEditor) {
            contentEditor.addEventListener('input', syncFromEditor);
            contentEditor.addEventListener('blur', syncFromEditor);
            contentEditor.addEventListener('paste', (event) => {
                event.preventDefault();
                const text = (event.clipboardData || window.clipboardData)?.getData('text/plain') || '';
                exec('insertText', text);
                syncFromEditor();
            });
        }

        toolbar.querySelectorAll('button[data-md]').forEach((btn) => {
            btn.addEventListener('click', () => {
                ensureMarkdown();
                const action = btn.dataset.md;
                if (action === 'bold') {
                    exec('bold');
                    return syncFromEditor();
                }
                if (action === 'undo') {
                    exec('undo');
                    return syncFromEditor();
                }
                if (action === 'redo') {
                    exec('redo');
                    return syncFromEditor();
                }
                if (action === 'italic') {
                    exec('italic');
                    return syncFromEditor();
                }
                if (action === 'ul') {
                    exec('insertUnorderedList');
                    return syncFromEditor();
                }
                if (action === 'quote') {
                    exec('formatBlock', 'blockquote');
                    return syncFromEditor();
                }
                if (action === 'link') {
                    const url = window.prompt('Enter link URL', 'https://');
                    if (!url) return;
                    exec('createLink', url);
                    return syncFromEditor();
                }
            });
        });
    }

    refreshToolbar();
})();
</script>
@endsection
