<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

use App\Models\EmployeePost;
use App\Models\EmployeeReel;
use App\Models\EmployeeStory;
use App\Models\Comment;
use App\Services\AiEventGuard;
use App\Models\RejectedContent;

use App\Models\Event;
use App\Models\Employee;

class ContentController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        // ✅ Get employee_id (your employees table doesn't have "id")
        $employeeId = Employee::where('user_id', $userId)->value('employee_id');

        // ✅ Load events created by this employee
        $events = collect();
        if ($employeeId) {
            $events = Event::where('created_by', $employeeId)
                ->orderByDesc('starts_at')
                ->get(['event_id', 'title']);
        }

        // ✅ Posts + reels: count likes/comments WITHOUT changing normal data
        $posts = EmployeePost::where('employee_user_id', $userId)
            ->withCount(['likes', 'comments'])
            ->latest()
            ->get();

        $reels = EmployeeReel::where('employee_user_id', $userId)
            ->withCount(['likes', 'comments'])
            ->latest()
            ->get();

        // ✅ Stories: keep same (no likes/comments)
        $stories = EmployeeStory::where('employee_user_id', $userId)
            ->latest()
            ->get();

        // JSON response for your JS
        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'posts' => $posts->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'title' => $p->title, // (kept for your feed rendering, even if you no longer input title)
                        'content' => $p->content,
                        'media_url' => $p->media_path ? asset('storage/' . $p->media_path) : null,
                        'created_at_formatted' => optional($p->created_at)->format('Y-m-d H:i'),
                        'likes_count' => (int) ($p->likes_count ?? 0),
                        'comments_count' => (int) ($p->comments_count ?? 0),
                    ];
                })->values(),

                'reels' => $reels->map(function ($r) {
                    return [
                        'id' => $r->id,
                        'caption' => $r->caption,
                        'video_url' => $r->video_path ? asset('storage/' . $r->video_path) : null,
                        'created_at_formatted' => optional($r->created_at)->format('Y-m-d H:i'),
                        'likes_count' => (int) ($r->likes_count ?? 0),
                        'comments_count' => (int) ($r->comments_count ?? 0),
                    ];
                })->values(),

                'stories' => $stories->map(function ($s) {
                    $path = $s->media_path ?? '';
                    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                    $isVideo = in_array($ext, ['mp4', 'mov', 'webm']);

                    return [
                        'id' => $s->id,
                        'media_url' => $s->media_path ? asset('storage/' . $s->media_path) : null,
                        'media_type' => $isVideo ? 'video' : 'image',
                        'created_at_formatted' => optional($s->created_at)->format('Y-m-d H:i'),
                        'expires_at_formatted' => optional($s->expires_at)->format('Y-m-d H:i'),
                    ];
                })->values(),
            ]);
        }

        return view('employee.content', compact('posts', 'reels', 'stories', 'events'));
    }

    public function comments(Request $request)
    {
        $userId = $request->user()->id;

        $data = $request->validate([
            'type' => ['required', 'in:post,reel'],
            'id'   => ['required', 'integer'],
        ]);

        if ($data['type'] === 'post') {
            $item = EmployeePost::findOrFail($data['id']);
            if ((int)$item->employee_user_id !== (int)$userId) {
                abort(403, 'Unauthorized');
            }
            $commentableType = EmployeePost::class;
            $commentableId = $item->id;
        } else {
            $item = EmployeeReel::findOrFail($data['id']);
            if ((int)$item->employee_user_id !== (int)$userId) {
                abort(403, 'Unauthorized');
            }
            $commentableType = EmployeeReel::class;
            $commentableId = $item->id;
        }

        $comments = Comment::where('commentable_type', $commentableType)
            ->where('commentable_id', $commentableId)
            ->with(['user:id,first_name,last_name'])
            ->latest()
            ->get()
            ->map(function ($c) {
                $u = $c->user;
                $name = trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''));
                if ($name === '') $name = 'User #' . $c->user_id;

                return [
                    'id' => $c->id,
                    'user_name' => $name,
                    'body' => $c->body,
                    'created_at_formatted' => optional($c->created_at)->format('Y-m-d H:i'),
                ];
            })
            ->values();

        return response()->json([
            'ok' => true,
            'comments' => $comments,
        ]);
    }

    public function storePost(Request $request, AiEventGuard $ai)
    {
        $userId = $request->user()->id;

        // ✅ Get employee_id for ownership check (created_by is employee_id)
        $employeeId = Employee::where('user_id', $userId)->value('employee_id');
        if (!$employeeId) {
            return back()->withErrors(['event_id' => 'Employee profile not found.'])->withInput();
        }

        $data = $request->validate([
            'event_id' => ['required', 'integer'],
            'content'  => ['required', 'string', 'max:5000'],
            'media'    => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        // ✅ Ensure selected event belongs to this employee
        $eventOk = Event::where('event_id', $data['event_id'])
            ->where('created_by', $employeeId)
            ->exists();

        if (!$eventOk) {
            return back()->withErrors([
                'event_id' => 'Invalid event selection.',
            ])->withInput();
        }

        $tempPath = null;
        if ($request->hasFile('media')) {
            $tempPath = $request->file('media')->store('temp/employee_posts', 'public');
        }

        $result = $ai->check("Post upload", $tempPath);

        if (($result['related'] ?? null) === null || ($result['reason'] ?? '') !== '') {
            $techReasons = [
                'ai_unreachable','image_too_large_for_ai','ai_http_error','ai_bad_json',
                'ai_missing_output','bad_ai_format','file_missing',
            ];

            if (in_array(($result['reason'] ?? ''), $techReasons, true)) {
                if ($tempPath) Storage::disk('public')->delete($tempPath);
                return $this->handleAiFailure('media', $result, true);
            }
        }

        if (!($result['related'] ?? false) || empty($result['category_id'])) {

    // Save in rejected_contents (and move media to rejected folder)
    $this->saveRejected('post', $userId, [
        'event_id' => $data['event_id'],
        'content'  => $data['content'],
    ], $tempPath, $result);

    return back()->with('warning', 'Your post was sent to Admin review (not published).');
}


        $finalPath = null;
        if ($tempPath) {
            $finalPath = str_replace('temp/employee_posts', 'employee_posts', $tempPath);
            Storage::disk('public')->move($tempPath, $finalPath);
        }
$eventTitle = Event::where('event_id', $data['event_id'])->value('title');

        EmployeePost::create([
            'employee_user_id' => $userId,
            'category_id'      => $result['category_id'],
            'title'            => $eventTitle, 
            'content'          => $data['content'],
            'media_path'       => $finalPath,
        ]);

        return back()->with('ok', 'Post created successfully.');
    }

    public function storeReel(Request $request, AiEventGuard $ai)
    {
        $userId = $request->user()->id;

        $data = $request->validate([
            'caption' => ['nullable', 'string', 'max:1000'],
            'video'   => ['required', 'file', 'mimes:mp4,mov,webm', 'max:51200'],
        ]);

        $tempVideo = $request->file('video')->store('temp/employee_reels', 'public');

        $captionText = trim($data['caption'] ?? '');
        $result = $ai->check("Reel caption:\n" . $captionText, null);

        $techReasons = ['ai_unreachable', 'ai_http_error', 'ai_bad_json', 'ai_missing_output', 'bad_ai_format'];
        if (in_array(($result['reason'] ?? ''), $techReasons, true)) {
            Storage::disk('public')->delete($tempVideo);
            return $this->handleAiFailure('video', $result, true);
        }

        if (!($result['related'] ?? false) || empty($result['category_id'])) {

    $this->saveRejected('reel', $userId, [
        'caption' => $data['caption'] ?? null,
    ], $tempVideo, $result);

    return back()->with('warning', 'Your reel was sent to Admin review (not published).')->withInput();
}


        $finalPath = str_replace('temp/employee_reels', 'employee_reels', $tempVideo);
        Storage::disk('public')->move($tempVideo, $finalPath);

        EmployeeReel::create([
            'employee_user_id' => $userId,
            'category_id'      => $result['category_id'],
            'caption'          => $data['caption'] ?? null,
            'video_path'       => $finalPath,
        ]);

        return back()->with('ok', 'Reel uploaded successfully.');
    }

    public function storeStory(Request $request, AiEventGuard $ai)
    {
        $userId = $request->user()->id;

        $data = $request->validate([
            'media'      => ['required', 'file', 'mimes:jpg,jpeg,png,webp,mp4,mov,webm', 'max:51200'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $tempPath = $request->file('media')->store('temp/employee_stories', 'public');

        $ext = strtolower($request->file('media')->getClientOriginalExtension());
        $isVideo = in_array($ext, ['mp4', 'mov', 'webm']);

        if ($isVideo) {
            Storage::disk('public')->delete($tempPath);
            return back()->withErrors([
                'media' => 'Video stories AI-check not enabled yet. Upload an image story for now.',
            ]);
        }

        $result = $ai->check("Story upload", $tempPath);

        $techReasons = [
            'ai_unreachable','image_too_large_for_ai','ai_http_error','ai_bad_json',
            'ai_missing_output','bad_ai_format','file_missing',
        ];
        if (in_array(($result['reason'] ?? ''), $techReasons, true)) {
            Storage::disk('public')->delete($tempPath);
            return $this->handleAiFailure('media', $result, true);
        }

        if (!($result['related'] ?? false) || empty($result['category_id'])) {

    $this->saveRejected('story', $userId, [], $tempPath, $result);

    return back()->with('warning', 'Your story was sent to Admin review (not published).');
}


        $finalPath = str_replace('temp/employee_stories', 'employee_stories', $tempPath);
        Storage::disk('public')->move($tempPath, $finalPath);

        $expiresAt = isset($data['expires_at'])
            ? Carbon::parse($data['expires_at'])
            : now()->addHours(24);

        EmployeeStory::create([
            'employee_user_id' => $userId,
            'category_id'      => $result['category_id'],
            'media_path'       => $finalPath,
            'expires_at'       => $expiresAt,
        ]);

        return back()->with('ok', 'Story uploaded successfully.');
    }

    private function handleAiFailure(string $field, array $result, bool $withInput = false)
    {
        Log::error('AI Guard failure', [
            'reason'      => $result['reason'] ?? null,
            'http_status' => $result['http_status'] ?? null,
            'http_body'   => $result['http_body'] ?? null,
        ]);

        $reason = $result['reason'] ?? 'unknown';

        $msg = match ($reason) {
            'ai_unreachable' => 'AI service is currently unavailable. Please try again later.',
            'image_too_large_for_ai' => 'Image is too large for AI check. Please upload a smaller image (max 2MB).',
            'ai_http_error' => 'AI returned an invalid response. Please try again.',
            'ai_bad_json', 'bad_ai_format', 'ai_missing_output' => 'AI returned an invalid response. Please try again.',
            'file_missing' => 'Uploaded file could not be read. Please upload again.',
            default => 'AI returned an invalid response. Please try again.',
        };

        $resp = back()->withErrors([$field => $msg]);
        return $withInput ? $resp->withInput() : $resp;
    }

    public function destroyPost(Request $request, EmployeePost $post)
    {
        $this->authorizeOwner($request->user()->id, $post->employee_user_id);

        if ($post->media_path) Storage::disk('public')->delete($post->media_path);
        $post->delete();

        return response()->json(['ok' => true]);
    }

    public function destroyReel(Request $request, EmployeeReel $reel)
    {
        $this->authorizeOwner($request->user()->id, $reel->employee_user_id);

        if ($reel->video_path) Storage::disk('public')->delete($reel->video_path);
        $reel->delete();

        return response()->json(['ok' => true]);
    }

    public function destroyStory(Request $request, EmployeeStory $story)
    {
        $this->authorizeOwner($request->user()->id, $story->employee_user_id);

        if ($story->media_path) Storage::disk('public')->delete($story->media_path);
        $story->delete();

        return response()->json(['ok' => true]);
    }

    private function authorizeOwner(int $currentUserId, int $rowUserId): void
    {
        if ($currentUserId !== $rowUserId) {
            abort(403, 'Unauthorized');
        }
    }
    private function saveRejected(
    string $contentType,
    int $employeeUserId,
    array $payload,
    ?string $tempPath,
    array $aiResult
): RejectedContent {
    // Move media to a permanent "rejected" folder (so it won’t disappear)
    $finalRejectedPath = null;

    if ($tempPath) {
        $folder = match ($contentType) {
            'post'  => 'rejected/employee_posts',
            'reel'  => 'rejected/employee_reels',
            'story' => 'rejected/employee_stories',
            default => 'rejected/misc',
        };

        // temp/... -> rejected/...
        $fileName = basename($tempPath);
        $finalRejectedPath = $folder . '/' . $fileName;

        Storage::disk('public')->move($tempPath, $finalRejectedPath);
    }

    return RejectedContent::create([
        'employee_user_id' => $employeeUserId,
        'content_type'     => $contentType,

        'event_id'         => $payload['event_id'] ?? null,
        'content'          => $payload['content'] ?? null,
        'caption'          => $payload['caption'] ?? null,
        'media_path'       => $finalRejectedPath,

        'ai_related'       => (bool)($aiResult['related'] ?? false),
        'ai_category_id'   => $aiResult['category_id'] ?? null,
        'ai_reason'        => $aiResult['reason'] ?? null,
        'ai_raw'           => $aiResult,

        'review_status'    => 'pending',
    ]);
}

}
