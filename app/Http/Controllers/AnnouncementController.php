<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use App\Notifications\AnnouncementPublished;

class AnnouncementController extends Controller
{
    private function canPin(Announcement $announcement): bool
    {
        if ($announcement->trashed()) {
            return false;
        }

        if ((bool) $announcement->is_draft) {
            return false;
        }

        if ($announcement->publish_at && $announcement->publish_at->isFuture()) {
            return false;
        }

        return true;
    }

    private function duplicatePublicImage(?string $imagePath): ?string
    {
        if (!$imagePath) {
            return null;
        }

        $disk = Storage::disk('public');
        if (!$disk->exists($imagePath)) {
            return null;
        }

        $ext = (string) pathinfo($imagePath, PATHINFO_EXTENSION);
        $suffix = $ext !== '' ? '.'.$ext : '';

        do {
            $copyPath = 'announcements/'.Str::uuid().$suffix;
        } while ($disk->exists($copyPath));

        $disk->copy($imagePath, $copyPath);

        return $copyPath;
    }

    private function deletePublicImageIfUnused(?string $imagePath): void
    {
        if (!$imagePath) {
            return;
        }

        $stillUsed = Announcement::withTrashed()->where('image_path', $imagePath)->exists();
        if ($stillUsed) {
            return;
        }

        Storage::disk('public')->delete($imagePath);
    }

    public function index()
    {
        $tab = (string) request()->query('tab', 'drafts');
        if (!in_array($tab, ['all', 'drafts', 'scheduled', 'published', 'deleted'], true)) {
            $tab = 'drafts';
        }

        $filterKeys = ['q', 'from', 'to', 'sort', 'per_page', 'pinned'];
        $reset = (bool) request()->boolean('reset');
        $sessionKey = 'announcements.filters.'.((string) auth()->user()->role).'.'.$tab;

        if ($reset) {
            session()->forget($sessionKey);
            $redirectQuery = array_filter(['tab' => $tab], fn ($value) => $value !== null && $value !== '');
            return redirect()->route(auth()->user()->role === 'super_admin' ? 'master.announcements.index' : 'admin.announcements.index', $redirectQuery);
        }

        $hasAnyFilter = false;
        foreach ($filterKeys as $key) {
            if (request()->has($key)) {
                $hasAnyFilter = true;
                break;
            }
        }

        if ($hasAnyFilter) {
            session()->put($sessionKey, request()->only($filterKeys));
        } else {
            $saved = (array) session()->get($sessionKey, []);
            if ($saved) {
                $redirectQuery = array_merge(['tab' => $tab], array_filter($saved, fn ($value) => $value !== null && $value !== ''));
                return redirect()->route(auth()->user()->role === 'super_admin' ? 'master.announcements.index' : 'admin.announcements.index', $redirectQuery);
            }
        }

        $sort = (string) request()->query('sort', 'newest');
        if (!in_array($sort, ['newest', 'oldest', 'title_az', 'title_za', 'publish_newest', 'publish_oldest'], true)) {
            $sort = 'newest';
        }

        $q = trim((string) request()->query('q', ''));
        $from = trim((string) request()->query('from', ''));
        $to = trim((string) request()->query('to', ''));
        $pinned = (string) request()->query('pinned', 'all');
        if (!in_array($pinned, ['all', 'pinned', 'unpinned'], true)) {
            $pinned = 'all';
        }

        $perPage = (int) request()->query('per_page', 10);
        if (!in_array($perPage, [10, 25, 50], true)) {
            $perPage = 10;
        }

        $announcementsQuery = match ($tab) {
            'drafts' => Announcement::query()->where('is_draft', true),
            'scheduled' => Announcement::query()
                ->where('is_draft', false)
                ->whereNotNull('publish_at')
                ->where('publish_at', '>', now()),
            'published' => Announcement::query()
                ->where('is_draft', false)
                ->where(function ($query) {
                    $query->whereNull('publish_at')->orWhere('publish_at', '<=', now());
                }),
            'deleted' => Announcement::onlyTrashed(),
            default => Announcement::query(),
        };

        $announcementsQuery->with(['author', 'updatedBy', 'deletedBy', 'restoredBy']);

        if ($tab !== 'deleted') {
            $announcementsQuery->whereNull('deleted_at');
        }

        if ($pinned === 'pinned') {
            $announcementsQuery->where('is_pinned', true);
        } elseif ($pinned === 'unpinned') {
            $announcementsQuery->where('is_pinned', false);
        }

        if ($q !== '') {
            $announcementsQuery->where(function ($query) use ($q) {
                $like = '%'.$q.'%';
                $query
                    ->where('title', 'like', $like)
                    ->orWhere('content', 'like', $like)
                    ->orWhereHas('author', fn ($authorQuery) => $authorQuery->where('full_name', 'like', $like)->orWhere('email', 'like', $like));
            });
        }

        if ($from !== '') {
            $announcementsQuery->whereDate('created_at', '>=', $from);
        }

        if ($to !== '') {
            $announcementsQuery->whereDate('created_at', '<=', $to);
        }

        if ($tab === 'deleted') {
            $announcementsQuery->orderByDesc('deleted_at');
        } else {
            $announcementsQuery->orderByDesc('pinned_at');
        }

        match ($sort) {
            'oldest' => $announcementsQuery->orderBy('created_at'),
            'title_az' => $announcementsQuery->orderBy('title'),
            'title_za' => $announcementsQuery->orderByDesc('title'),
            'publish_newest' => $announcementsQuery->orderByDesc('publish_at')->orderByDesc('created_at'),
            'publish_oldest' => $announcementsQuery->orderBy('publish_at')->orderBy('created_at'),
            default => $announcementsQuery->orderByDesc('created_at'),
        };

        $announcements = $announcementsQuery->paginate($perPage)->withQueryString();

        $counts = [
            'all' => Announcement::count(),
            'drafts' => Announcement::query()->whereNull('deleted_at')->where('is_draft', true)->count(),
            'scheduled' => Announcement::query()
                ->whereNull('deleted_at')
                ->where('is_draft', false)
                ->whereNotNull('publish_at')
                ->where('publish_at', '>', now())
                ->count(),
            'published' => Announcement::query()
                ->whereNull('deleted_at')
                ->where('is_draft', false)
                ->where(function ($query) {
                    $query->whereNull('publish_at')->orWhere('publish_at', '<=', now());
                })
                ->count(),
            'deleted' => Announcement::onlyTrashed()->count(),
        ];

        return view('announcements.index', compact('announcements', 'tab', 'counts', 'q', 'from', 'to', 'sort', 'perPage', 'pinned'));
    }

    public function show(Announcement $announcement)
    {
        abort_unless(in_array(auth()->user()->role, ['admin', 'super_admin'], true), 403);

        $announcement->loadMissing(['author', 'updatedBy', 'deletedBy', 'restoredBy']);

        return view('announcements.show', compact('announcement'));
    }

    public function create()
    {
        return view('announcements.create');
    }

    public function store(Request $request)
    {
        abort_unless(in_array(auth()->user()->role, ['admin', 'super_admin'], true), 403);
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'content_format' => 'nullable|in:plain,markdown',
            'is_draft' => 'nullable|boolean',
            'publish_at' => 'nullable|date',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'image_caption' => 'nullable|string|max:255',
        ]);

        $publishAt = $request->filled('publish_at') ? Carbon::parse((string) $request->input('publish_at')) : null;
        if ((bool) $request->boolean('is_draft') && $publishAt && $publishAt->isPast()) {
            return back()->withErrors(['publish_at' => 'Publish time must be in the future for drafts.'])->withInput();
        }

        $path = $request->hasFile('image')
            ? $request->file('image')->store('announcements', 'public')
            : null;

        $announcement = Announcement::create([
            'author_id' => auth()->id(),
            'updated_by' => auth()->id(),
            'title' => $request->title,
            'content' => $request->content,
            'content_format' => $request->input('content_format', 'plain'),
            'is_draft' => (bool) $request->boolean('is_draft'),
            'publish_at' => $publishAt,
            'image_path' => $path,
            'image_caption' => $path ? $request->input('image_caption') : null,
        ]);

        AuditLogger::log('announcement_created', 'announcement', $announcement->id);

        $isScheduled = (bool) ($announcement->publish_at && $announcement->publish_at->isFuture());
        if (!(bool) $announcement->is_draft) {
            AuditLogger::log('announcement_published', 'announcement', $announcement->id, [
                'is_scheduled' => $isScheduled,
            ]);

            if (!$isScheduled && (bool) config('ccs.announcements.notify_on_publish', false)) {
                $recipients = User::query()
                    ->whereNull('deleted_at')
                    ->where('is_active', true)
                    ->whereIn('role', ['parent', 'student'])
                    ->get();

                if ($recipients->isNotEmpty()) {
                    $delivery = (string) config('ccs.announcements.notification_delivery', 'sync');
                    $notification = new AnnouncementPublished($announcement);

                    if ($delivery === 'queue') {
                        Notification::send($recipients, $notification);
                    } else {
                        Notification::sendNow($recipients, $notification);
                    }
                }
            }
        }

        $indexRoute = auth()->user()->role === 'super_admin' ? 'master.announcements.index' : 'admin.announcements.index';
        $message = $announcement->is_draft
            ? 'Announcement created.'
            : ($isScheduled ? 'Announcement created and scheduled.' : 'Announcement created and published.');

        $redirectTab = $announcement->is_draft ? 'drafts' : ($isScheduled ? 'scheduled' : 'published');
        return redirect()->route($indexRoute, ['tab' => $redirectTab])->with('success', $message);
    }

    public function edit(Announcement $announcement)
    {
        abort_unless(in_array(auth()->user()->role, ['admin', 'super_admin'], true), 403);
        return view('announcements.edit', compact('announcement'));
    }

    public function update(Request $request, Announcement $announcement)
    {
        abort_unless(in_array(auth()->user()->role, ['admin', 'super_admin'], true), 403);
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'content_format' => 'nullable|in:plain,markdown',
            'is_draft' => 'nullable|boolean',
            'publish_at' => 'nullable|date',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'image_caption' => 'nullable|string|max:255',
            'remove_image' => 'nullable|boolean',
        ]);

        $publishAt = $request->filled('publish_at') ? Carbon::parse((string) $request->input('publish_at')) : null;
        $nextIsDraft = (bool) $request->boolean('is_draft');
        if ($nextIsDraft && $publishAt && $publishAt->isPast()) {
            return back()->withErrors(['publish_at' => 'Publish time must be in the future for drafts.'])->withInput();
        }

        $wasDraft = (bool) $announcement->is_draft;
        $deleteAfterSave = [];

        if ($request->boolean('remove_image')) {
            $oldPath = $announcement->image_path;
            if ($announcement->image_path) {
                $announcement->image_path = null;
            }
            $announcement->image_caption = null;
            if ($oldPath) {
                $deleteAfterSave[] = $oldPath;
            }
        }

        if ($request->hasFile('image')) {
            $oldPath = $announcement->image_path;
            $announcement->image_path = $request->file('image')->store('announcements', 'public');
            if ($oldPath) {
                $deleteAfterSave[] = $oldPath;
            }
        }

        $announcement->title = $request->title;
        $announcement->content = $request->content;
        $announcement->content_format = $request->input('content_format', 'plain');
        $announcement->is_draft = $nextIsDraft;
        $announcement->publish_at = $publishAt;
        $announcement->image_caption = $announcement->image_path ? $request->input('image_caption') : null;

        if (!$wasDraft && $nextIsDraft) {
            $announcement->is_pinned = false;
            $announcement->pinned_at = null;
        }

        $announcement->updated_by = auth()->id();
        $announcement->save();

        foreach (array_values(array_unique($deleteAfterSave)) as $oldPath) {
            $this->deletePublicImageIfUnused($oldPath);
        }

        AuditLogger::log('announcement_updated', 'announcement', $announcement->id);

        $isScheduled = (bool) ($announcement->publish_at && $announcement->publish_at->isFuture());
        if ($wasDraft && !$nextIsDraft) {
            AuditLogger::log('announcement_published', 'announcement', $announcement->id, [
                'is_scheduled' => $isScheduled,
            ]);

            if (!$isScheduled && (bool) config('ccs.announcements.notify_on_publish', false)) {
                $recipients = User::query()
                    ->whereNull('deleted_at')
                    ->where('is_active', true)
                    ->whereIn('role', ['parent', 'student'])
                    ->get();

                if ($recipients->isNotEmpty()) {
                    $delivery = (string) config('ccs.announcements.notification_delivery', 'sync');
                    $notification = new AnnouncementPublished($announcement);

                    if ($delivery === 'queue') {
                        Notification::send($recipients, $notification);
                    } else {
                        Notification::sendNow($recipients, $notification);
                    }
                }
            }
        } elseif (!$wasDraft && $nextIsDraft) {
            AuditLogger::log('announcement_unpublished', 'announcement', $announcement->id);
        }

        $message = $wasDraft && !$nextIsDraft
            ? ($isScheduled ? 'Announcement updated and scheduled.' : 'Announcement updated and published.')
            : ($wasDraft !== $nextIsDraft ? 'Announcement status updated.' : 'Announcement updated.');

        return back()->with('success', $message);
    }

    public function destroy(Announcement $announcement)
    {
        abort_unless(in_array(auth()->user()->role, ['admin', 'super_admin'], true), 403);

        $announcement->is_pinned = false;
        $announcement->pinned_at = null;
        $announcement->deleted_by = auth()->id();
        $announcement->save();
        $announcement->delete();
        AuditLogger::log('announcement_deleted', 'announcement', $announcement->id);

        return back()->with('success', 'Announcement deleted.');
    }

    public function restore(int $announcementId)
    {
        abort_unless(in_array(auth()->user()->role, ['admin', 'super_admin'], true), 403);

        $announcement = Announcement::onlyTrashed()->findOrFail($announcementId);
        $announcement->restored_by = auth()->id();
        $announcement->deleted_by = null;
        $announcement->restore();

        AuditLogger::log('announcement_restored', 'announcement', $announcement->id);

        return back()->with('success', 'Announcement restored.');
    }

    public function forceDestroy(int $announcementId)
    {
        abort_unless((string) auth()->user()->role === 'super_admin', 403);

        $announcement = Announcement::onlyTrashed()->findOrFail($announcementId);
        $imagePath = $announcement->image_path;

        $announcement->forceDelete();
        $this->deletePublicImageIfUnused($imagePath);

        AuditLogger::log('announcement_force_deleted', 'announcement', $announcementId);

        return back()->with('success', 'Announcement permanently deleted.');
    }

    public function togglePin(Announcement $announcement)
    {
        abort_unless((string) auth()->user()->role === 'super_admin', 403);

        if (!$this->canPin($announcement)) {
            return back()->with('error', 'Only published announcements can be pinned.');
        }

        $announcement->is_pinned = !$announcement->is_pinned;
        $announcement->pinned_at = $announcement->is_pinned ? now() : null;
        $announcement->save();

        AuditLogger::log('announcement_pin_toggled', 'announcement', $announcement->id, [
            'is_pinned' => (bool) $announcement->is_pinned,
        ]);

        return back()->with('success', $announcement->is_pinned ? 'Announcement pinned.' : 'Announcement unpinned.');
    }

    public function publish(Announcement $announcement)
    {
        abort_unless(in_array((string) auth()->user()->role, ['admin', 'super_admin'], true), 403);

        if ($announcement->trashed()) {
            return back()->with('error', 'Cannot publish a deleted announcement.');
        }

        if (!$announcement->is_draft) {
            return back()->with('info', 'Announcement is already published.');
        }

        if (trim((string) $announcement->title) === '' || trim((string) $announcement->content) === '') {
            return back()->with('error', 'Cannot publish: title and content are required.');
        }

        $announcement->is_draft = false;
        $announcement->updated_by = auth()->id();
        $announcement->save();

        AuditLogger::log('announcement_published', 'announcement', $announcement->id);

        $isScheduled = (bool) ($announcement->publish_at && $announcement->publish_at->isFuture());
        $message = $isScheduled ? 'Announcement published (scheduled).' : 'Announcement published.';

        if (!$isScheduled && (bool) config('ccs.announcements.notify_on_publish', false)) {
            $recipients = User::query()
                ->whereNull('deleted_at')
                ->where('is_active', true)
                ->whereIn('role', ['parent', 'student'])
                ->get();

            if ($recipients->isNotEmpty()) {
                $delivery = (string) config('ccs.announcements.notification_delivery', 'sync');
                $notification = new AnnouncementPublished($announcement);

                if ($delivery === 'queue') {
                    Notification::send($recipients, $notification);
                } else {
                    Notification::sendNow($recipients, $notification);
                }
            }
        }

        return back()->with('success', $message);
    }

    public function unpublish(Announcement $announcement)
    {
        abort_unless(in_array((string) auth()->user()->role, ['admin', 'super_admin'], true), 403);

        if ($announcement->trashed()) {
            return back()->with('error', 'Cannot unpublish a deleted announcement.');
        }

        if ((bool) $announcement->is_draft) {
            return back()->with('info', 'Announcement is already a draft.');
        }

        $announcement->is_draft = true;
        $announcement->is_pinned = false;
        $announcement->pinned_at = null;
        $announcement->updated_by = auth()->id();
        $announcement->save();

        AuditLogger::log('announcement_unpublished', 'announcement', $announcement->id);

        return back()->with('success', 'Announcement moved back to draft.');
    }

    public function duplicate(Announcement $announcement)
    {
        abort_unless(in_array((string) auth()->user()->role, ['admin', 'super_admin'], true), 403);

        if ($announcement->trashed()) {
            return back()->with('error', 'Cannot duplicate a deleted announcement.');
        }

        $copyImagePath = $this->duplicatePublicImage($announcement->image_path);
        $copy = Announcement::create([
            'author_id' => auth()->id(),
            'updated_by' => auth()->id(),
            'title' => $announcement->title.' (Copy)',
            'content' => $announcement->content,
            'content_format' => $announcement->content_format ?? 'plain',
            'is_draft' => true,
            'publish_at' => null,
            'image_path' => $copyImagePath,
            'image_caption' => $copyImagePath ? $announcement->image_caption : null,
            'is_pinned' => false,
            'pinned_at' => null,
        ]);

        AuditLogger::log('announcement_duplicated', 'announcement', $copy->id, [
            'source_id' => $announcement->id,
        ]);

        $editRoute = auth()->user()->role === 'super_admin' ? 'master.announcements.edit' : 'admin.announcements.edit';
        return redirect()->route($editRoute, $copy)->with('success', 'Announcement duplicated as a draft.');
    }

    public function preview(Request $request)
    {
        abort_unless(in_array((string) auth()->user()->role, ['admin', 'super_admin'], true), 403);

        $validated = $request->validate([
            'content' => 'nullable|string',
            'content_format' => 'nullable|in:plain,markdown',
        ]);

        $content = (string) ($validated['content'] ?? '');
        $format = (string) ($validated['content_format'] ?? 'plain');

        $html = match ($format) {
            'markdown' => Str::markdown($content, [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ]),
            default => new HtmlString(nl2br(e($content))),
        };

        return response()->json([
            'html' => (string) $html,
        ]);
    }

    public function bulk(Request $request)
    {
        abort_unless(in_array((string) auth()->user()->role, ['admin', 'super_admin'], true), 403);

        $request->validate([
            'action' => 'required|string|in:publish,unpublish,delete,restore,force_delete,pin,unpin,duplicate',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $action = (string) $request->input('action');
        $ids = array_values(array_unique(array_map('intval', (array) $request->input('ids', []))));

        $announcements = Announcement::withTrashed()->whereIn('id', $ids)->get();
        if ($announcements->isEmpty()) {
            return back()->with('error', 'No announcements selected.');
        }

        $isSuper = (string) auth()->user()->role === 'super_admin';
        if (in_array($action, ['force_delete', 'pin', 'unpin'], true) && !$isSuper) {
            abort(403);
        }

        $changed = 0;
        $created = 0;

        foreach ($announcements as $announcement) {
            if ($action === 'publish') {
                if (!$announcement->trashed() && (bool) $announcement->is_draft) {
                    $announcement->is_draft = false;
                    $announcement->updated_by = auth()->id();
                    $announcement->save();
                    AuditLogger::log('announcement_published', 'announcement', $announcement->id);
                    $changed++;
                }
            } elseif ($action === 'unpublish') {
                if (!$announcement->trashed() && !(bool) $announcement->is_draft) {
                    $announcement->is_draft = true;
                    $announcement->is_pinned = false;
                    $announcement->pinned_at = null;
                    $announcement->updated_by = auth()->id();
                    $announcement->save();
                    AuditLogger::log('announcement_unpublished', 'announcement', $announcement->id);
                    $changed++;
                }
            } elseif ($action === 'delete') {
                if (!$announcement->trashed()) {
                    $announcement->is_pinned = false;
                    $announcement->pinned_at = null;
                    $announcement->deleted_by = auth()->id();
                    $announcement->save();
                    $announcement->delete();
                    AuditLogger::log('announcement_deleted', 'announcement', $announcement->id);
                    $changed++;
                }
            } elseif ($action === 'restore') {
                if ($announcement->trashed()) {
                    $announcement->restored_by = auth()->id();
                    $announcement->deleted_by = null;
                    $announcement->restore();
                    AuditLogger::log('announcement_restored', 'announcement', $announcement->id);
                    $changed++;
                }
            } elseif ($action === 'force_delete') {
                if ($announcement->trashed()) {
                    $imagePath = $announcement->image_path;
                    $id = $announcement->id;
                    $announcement->forceDelete();
                    $this->deletePublicImageIfUnused($imagePath);
                    AuditLogger::log('announcement_force_deleted', 'announcement', $id);
                    $changed++;
                }
            } elseif ($action === 'pin') {
                if ($this->canPin($announcement) && !(bool) $announcement->is_pinned) {
                    $announcement->is_pinned = true;
                    $announcement->pinned_at = now();
                    $announcement->save();
                    AuditLogger::log('announcement_pin_toggled', 'announcement', $announcement->id, ['is_pinned' => true]);
                    $changed++;
                }
            } elseif ($action === 'unpin') {
                if (!$announcement->trashed() && (bool) $announcement->is_pinned) {
                    $announcement->is_pinned = false;
                    $announcement->pinned_at = null;
                    $announcement->save();
                    AuditLogger::log('announcement_pin_toggled', 'announcement', $announcement->id, ['is_pinned' => false]);
                    $changed++;
                }
            } elseif ($action === 'duplicate') {
                if (!$announcement->trashed()) {
                    $copyImagePath = $this->duplicatePublicImage($announcement->image_path);
                    Announcement::create([
                        'author_id' => auth()->id(),
                        'updated_by' => auth()->id(),
                        'title' => $announcement->title.' (Copy)',
                        'content' => $announcement->content,
                        'content_format' => $announcement->content_format ?? 'plain',
                        'is_draft' => true,
                        'publish_at' => null,
                        'image_path' => $copyImagePath,
                        'image_caption' => $copyImagePath ? $announcement->image_caption : null,
                        'is_pinned' => false,
                        'pinned_at' => null,
                    ]);
                    $created++;
                }
            }
        }

        $message = $created > 0
            ? "Bulk action complete. Updated: {$changed}. Duplicated: {$created}."
            : "Bulk action complete. Updated: {$changed}.";

        return back()->with('success', $message);
    }
}
