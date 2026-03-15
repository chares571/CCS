<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            if (!Schema::hasColumn('announcements', 'is_draft')) {
                $table->boolean('is_draft')->default(false)->after('content');
            }

            if (!Schema::hasColumn('announcements', 'content_format')) {
                $table->string('content_format', 20)->default('plain')->after('is_draft');
            }

            if (!Schema::hasColumn('announcements', 'image_caption')) {
                $table->string('image_caption')->nullable()->after('image_path');
            }

            if (!Schema::hasColumn('announcements', 'is_pinned')) {
                $table->boolean('is_pinned')->default(false)->after('publish_at');
            }

            if (!Schema::hasColumn('announcements', 'pinned_at')) {
                $table->timestamp('pinned_at')->nullable()->after('is_pinned');
            }

            if (!Schema::hasColumn('announcements', 'updated_by')) {
                $table->foreignId('updated_by')->nullable()->after('author_id')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('announcements', 'deleted_by')) {
                $table->foreignId('deleted_by')->nullable()->after('updated_by')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('announcements', 'restored_by')) {
                $table->foreignId('restored_by')->nullable()->after('deleted_by')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            foreach (['restored_by', 'deleted_by', 'updated_by'] as $column) {
                if (Schema::hasColumn('announcements', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }

            foreach (['pinned_at', 'is_pinned', 'image_caption', 'content_format', 'is_draft'] as $column) {
                if (Schema::hasColumn('announcements', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

