<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AnnouncementImageHandlingTest extends TestCase
{
    use RefreshDatabase;

    private function createSuperAdmin(): User
    {
        return User::query()->create([
            'full_name' => 'Super Admin',
            'username' => 'superadmin',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('Password123'),
            'role' => 'super_admin',
            'is_active' => true,
            'force_password_change' => false,
        ]);
    }

    public function test_duplicate_copies_image_file_instead_of_reusing_path(): void
    {
        Storage::fake('public');

        $user = $this->createSuperAdmin();
        $announcement = Announcement::query()->create([
            'author_id' => $user->id,
            'updated_by' => $user->id,
            'title' => 'Original',
            'content' => 'Hello',
            'content_format' => 'plain',
            'is_draft' => false,
            'publish_at' => null,
            'image_path' => 'announcements/original.png',
            'image_caption' => 'Caption',
            'is_pinned' => false,
            'pinned_at' => null,
        ]);

        Storage::disk('public')->put('announcements/original.png', 'image-bytes');

        $this->actingAs($user)
            ->post(route('master.announcements.duplicate', $announcement))
            ->assertRedirect();

        $copy = Announcement::query()->where('title', 'Original (Copy)')->latest('id')->first();
        $this->assertNotNull($copy);
        $this->assertNotSame($announcement->image_path, $copy->image_path);
        $this->assertSame('Caption', $copy->image_caption);

        Storage::disk('public')->assertExists('announcements/original.png');
        Storage::disk('public')->assertExists((string) $copy->image_path);
    }

    public function test_remove_image_does_not_delete_shared_image_file(): void
    {
        Storage::fake('public');

        $user = $this->createSuperAdmin();
        Storage::disk('public')->put('announcements/shared.png', 'image-bytes');

        $a1 = Announcement::query()->create([
            'author_id' => $user->id,
            'updated_by' => $user->id,
            'title' => 'A1',
            'content' => 'Hello',
            'content_format' => 'plain',
            'is_draft' => true,
            'publish_at' => null,
            'image_path' => 'announcements/shared.png',
            'image_caption' => 'Cap',
            'is_pinned' => false,
            'pinned_at' => null,
        ]);

        $a2 = Announcement::query()->create([
            'author_id' => $user->id,
            'updated_by' => $user->id,
            'title' => 'A2',
            'content' => 'Hello',
            'content_format' => 'plain',
            'is_draft' => true,
            'publish_at' => null,
            'image_path' => 'announcements/shared.png',
            'image_caption' => 'Cap2',
            'is_pinned' => false,
            'pinned_at' => null,
        ]);

        $this->actingAs($user)
            ->put(route('master.announcements.update', $a1), [
                'title' => 'A1',
                'content' => 'Hello',
                'content_format' => 'plain',
                'is_draft' => true,
                'publish_at' => '',
                'image_caption' => '',
                'remove_image' => 1,
            ])
            ->assertRedirect();

        $a1->refresh();
        $a2->refresh();

        $this->assertNull($a1->image_path);
        $this->assertSame('announcements/shared.png', $a2->image_path);
        Storage::disk('public')->assertExists('announcements/shared.png');
    }

    public function test_remove_image_deletes_file_when_unreferenced(): void
    {
        Storage::fake('public');

        $user = $this->createSuperAdmin();
        Storage::disk('public')->put('announcements/unique.png', 'image-bytes');

        $a1 = Announcement::query()->create([
            'author_id' => $user->id,
            'updated_by' => $user->id,
            'title' => 'A1',
            'content' => 'Hello',
            'content_format' => 'plain',
            'is_draft' => true,
            'publish_at' => null,
            'image_path' => 'announcements/unique.png',
            'image_caption' => 'Cap',
            'is_pinned' => false,
            'pinned_at' => null,
        ]);

        $this->actingAs($user)
            ->put(route('master.announcements.update', $a1), [
                'title' => 'A1',
                'content' => 'Hello',
                'content_format' => 'plain',
                'is_draft' => true,
                'publish_at' => '',
                'image_caption' => '',
                'remove_image' => 1,
            ])
            ->assertRedirect();

        $a1->refresh();
        $this->assertNull($a1->image_path);
        Storage::disk('public')->assertMissing('announcements/unique.png');
    }
}
