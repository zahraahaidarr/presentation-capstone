<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rejected_contents', function (Blueprint $table) {
            $table->id();

            // who uploaded it
            $table->unsignedBigInteger('employee_user_id');

            // post / reel / story
            $table->string('content_type', 20);

            // optional event link (for posts)
            $table->unsignedBigInteger('event_id')->nullable();

            // store text fields
            $table->text('content')->nullable();   // post content
            $table->text('caption')->nullable();   // reel caption

            // store media path (image/video) in /storage/app/public/...
            $table->string('media_path')->nullable();

            // what AI returned
            $table->boolean('ai_related')->default(false);
            $table->unsignedBigInteger('ai_category_id')->nullable();
            $table->string('ai_reason')->nullable();
            $table->json('ai_raw')->nullable();

            // admin review flow
            $table->string('review_status', 20)->default('pending'); // pending/approved/rejected
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();

            // if approved, we store where it was published
            $table->unsignedBigInteger('published_id')->nullable();
            $table->string('published_table')->nullable(); // employee_posts / employee_reels / employee_stories

            $table->timestamps();

            $table->index(['employee_user_id', 'content_type', 'review_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rejected_contents');
    }
};
