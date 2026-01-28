<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RejectedContent;
use App\Models\EmployeePost;
use App\Models\EmployeeReel;
use App\Models\EmployeeStory;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RejectedContentController extends Controller
{
    public function index(Request $request)
    {
        $items = RejectedContent::query()
            ->orderByDesc('created_at')
            ->get();

        // For JS rendering
        $payload = $items->map(function ($x) {
            return [
                'id' => $x->id,
                'content_type' => $x->content_type,
                'employee_user_id' => $x->employee_user_id,
                'event_id' => $x->event_id,
                'content' => $x->content,
                'caption' => $x->caption,
                'media_path' => $x->media_path,
                'media_url' => $x->media_path ? asset('storage/' . ltrim($x->media_path, '/')) : null,
                'ai_related' => (bool)$x->ai_related,
                'ai_category_id' => $x->ai_category_id,
                'ai_reason' => $x->ai_reason,
                'review_status' => $x->review_status,
                'created_at' => optional($x->created_at)->format('Y-m-d H:i'),
            ];
        })->values();

        return view('Admin.rejected-content', [
            'rejected' => $payload,
        ]);
    }

    public function approve(Request $request, RejectedContent $rejected)
    {
        // Only approve pending/rejected
        if ($rejected->review_status === 'approved') {
            return response()->json(['ok' => false, 'message' => 'Already approved.'], 422);
        }

        $adminId = $request->user()->id;

        return DB::transaction(function () use ($rejected, $adminId) {

            // Move media from rejected/... to the correct folder
            $finalPath = null;
            if ($rejected->media_path) {
                $finalPath = $this->moveRejectedMediaToPublished($rejected->content_type, $rejected->media_path);
            }

            // Publish into the correct table
            $publishedId = null;
            $publishedTable = null;

            if ($rejected->content_type === 'post') {
                $eventTitle = null;
                if ($rejected->event_id) {
                    $eventTitle = Event::where('event_id', $rejected->event_id)->value('title');
                }

                $post = EmployeePost::create([
                    'employee_user_id' => $rejected->employee_user_id,
                    'category_id'      => $rejected->ai_category_id,
                    'title'            => $eventTitle ?? 'Rejected Post',
                    'content'          => $rejected->content ?? '',
                    'media_path'       => $finalPath,
                ]);

                $publishedId = $post->id;
                $publishedTable = 'employee_posts';
            }

            elseif ($rejected->content_type === 'reel') {
                $reel = EmployeeReel::create([
                    'employee_user_id' => $rejected->employee_user_id,
                    'category_id'      => $rejected->ai_category_id,
                    'caption'          => $rejected->caption,
                    'video_path'       => $finalPath, // moved from rejected folder
                ]);

                $publishedId = $reel->id;
                $publishedTable = 'employee_reels';
            }

            elseif ($rejected->content_type === 'story') {
                $story = EmployeeStory::create([
                    'employee_user_id' => $rejected->employee_user_id,
                    'category_id'      => $rejected->ai_category_id,
                    'media_path'       => $finalPath,
                    'expires_at'       => now()->addHours(24),
                ]);

                $publishedId = $story->id;
                $publishedTable = 'employee_stories';
            }

            else {
                return response()->json(['ok' => false, 'message' => 'Unknown content type.'], 422);
            }

            // Mark rejected row as approved
            $rejected->update([
                'review_status'   => 'approved',
                'reviewed_by'     => $adminId,
                'reviewed_at'     => now(),
                'published_id'    => $publishedId,
                'published_table' => $publishedTable,
            ]);

            return response()->json([
                'ok' => true,
                'message' => 'Content approved and published.',
                'published_table' => $publishedTable,
                'published_id' => $publishedId,
            ]);
        });
    }

    public function reject(Request $request, RejectedContent $rejected)
    {
        if ($rejected->review_status === 'rejected') {
            return response()->json(['ok' => true, 'message' => 'Already rejected.']);
        }

        $rejected->update([
            'review_status' => 'rejected',
            'reviewed_by'   => $request->user()->id,
            'reviewed_at'   => now(),
        ]);

        return response()->json(['ok' => true, 'message' => 'Kept rejected.']);
    }

    private function moveRejectedMediaToPublished(string $type, string $rejectedPath): ?string
    {
        $disk = Storage::disk('public');

        if (!$disk->exists($rejectedPath)) {
            // media missing – keep null
            return null;
        }

        $fileName = basename($rejectedPath);

        $targetDir = match ($type) {
            'post'  => 'employee_posts',
            'reel'  => 'employee_reels',
            'story' => 'employee_stories',
            default => 'misc',
        };

        $finalPath = $targetDir . '/' . $fileName;

        // Move rejected/... -> published folder
        $disk->move($rejectedPath, $finalPath);

        return $finalPath;
    }
}
