<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operator\StorePublicPostRequest;
use App\Models\PublicPost;
use App\Models\Report;
use App\Services\FileUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class PublicPostController extends Controller
{
    protected $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Display a listing of public posts.
     */
    public function index(Request $request)
    {
        try {
            // Check if this is an API request (for AJAX calls)
            if ($request->expectsJson()) {
                return $this->getPublicPostsJson($request);
            }

            // For web requests, return Inertia response
            $query = PublicPost::with([
                'postable',
                'publishedBy',
            ])->orderBy('created_at', 'desc');

            // Filter by publication status
            if ($request->has('status')) {
                switch ($request->status) {
                    case 'published':
                        $query->published();
                        break;
                    case 'unpublished':
                        $query->unpublished();
                        break;
                    case 'scheduled':
                        $query->where('published_at', '>', now());
                        break;
                }
            }

            // Filter by date range
            if ($request->has('from_date')) {
                $query->whereDate('created_at', '>=', $request->from_date);
            }
            if ($request->has('to_date')) {
                $query->whereDate('created_at', '<=', $request->to_date);
            }

            // Search functionality
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('content', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%");
                });
            }

            $publicPosts = $query->paginate($request->get('per_page', 15));

            return Inertia::render('public-post', [
                'data' => $publicPosts,
            ]);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to retrieve public posts: '.$e->getMessage()]);
        }
    }

    /**
     * Get public posts data as JSON (for API requests)
     */
    private function getPublicPostsJson(Request $request): JsonResponse
    {
        try {
            $query = PublicPost::with([
                'postable',
                'publishedBy',
            ])->orderBy('created_at', 'desc');

            // Filter by publication status
            if ($request->has('status')) {
                switch ($request->status) {
                    case 'published':
                        $query->published();
                        break;
                    case 'unpublished':
                        $query->unpublished();
                        break;
                    case 'scheduled':
                        $query->where('published_at', '>', now());
                        break;
                }
            }

            // Filter by date range
            if ($request->has('from_date')) {
                $query->whereDate('created_at', '>=', $request->from_date);
            }
            if ($request->has('to_date')) {
                $query->whereDate('created_at', '<=', $request->to_date);
            }

            // Search functionality
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('content', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%");
                });
            }

            $publicPosts = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'status' => 'success',
                'message' => 'Public posts retrieved successfully',
                'data' => $publicPosts,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve public posts',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created public post.
     */
    public function store(StorePublicPostRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            // Handle Image Upload
            $imagePath = null;
            if ($request->hasFile('image')) {
                $uploadResult = $this->fileUploadService->uploadSingle($request->file('image'), 'public_posts');
                $imagePath = $uploadResult['public_url']; // Storing the full URL or relative path depending on your preference. Usually relative path 'storage_path' or full url 'public_url'.
                // Based on model 'image_path', let's store the relative path if preferred, or public_url if that's how it's used.
                // Looking at other parts, typically public_url is convenient.
                // However, let's use public_url as it was returned.
            }

            // Handle Published At
            $publishedAt = null;
            if ($data['status'] === 'published') {
                $publishedAt = now();
            } elseif ($data['status'] === 'scheduled') {
                $publishedAt = $data['published_at'];
            }

            // Check duplicate for postable (if provided)
            if (! empty($data['postable_id']) && ! empty($data['postable_type'])) {
                if (PublicPost::where('postable_id', $data['postable_id'])
                    ->where('postable_type', $data['postable_type'])
                    ->exists()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'A public post already exists for this item',
                    ], 409);
                }
            }

            $publicPost = PublicPost::create([
                'title' => $data['title'],
                'content' => $data['content'],
                'image_path' => $imagePath, // Save the image path
                'category' => $data['category'] ?? 'General',
                'postable_id' => $data['postable_id'] ?? null,
                'postable_type' => $data['postable_type'] ?? null,
                'published_by' => Auth::id(),
                'published_at' => $publishedAt,
                'status' => $data['status'],
            ]);

            $publicPost->load(['postable', 'publishedBy']);

            // Return success response (Inertia handles redirects usually, but for modals often JSON is returned or Inertia visit)
            // Since the user asked for "manual post", and usually this is a form submission,
            // returning a redirect with flash message is standard for Inertia,
            // BUT the existing controller was returning JSON for store.
            // I will keep returning JSON as the frontend might expect it or I can change the frontend to use router.post
            // The existing store method returned JSON. I'll stick to that to be safe, or I can update it to support both.

            // Actually, for Inertia apps, we usually redirect back.
            // But if the frontend uses axios manually, JSON is good.
            // Let's return JSON as before, but if it's an Inertia request, we might want to redirect.
            // The previous code returned JSON. I will continue to return JSON.

            return response()->json([
                'status' => 'success',
                'message' => 'Public post created successfully',
                'data' => $publicPost,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create public post',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified public post.
     */
    public function show(PublicPost $publicPost): JsonResponse
    {
        try {
            $publicPost->load(['postable', 'publishedBy']);

            return response()->json([
                'status' => 'success',
                'message' => 'Public post retrieved successfully',
                'data' => $publicPost,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve public post',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified public post.
     */
    public function update(Request $request, PublicPost $publicPost)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'nullable|string|max:255',
                'content' => 'nullable|string',
                'image_path' => 'nullable|string',
                'category' => 'nullable|string',
                'published_at' => 'nullable|date',
                'status' => 'nullable|string|in:draft,published,scheduled',
            ]);

            if ($validator->fails()) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Validation failed',
                        'errors' => $validator->errors(),
                    ], 422);
                }

                return back()->withErrors($validator->errors());
            }

            // Update the public post fields
            $publicPost->update($request->only([
                'title',
                'content',
                'image_path',
                'category',
                'published_at',
                'status',
            ]));

            $publicPost->load(['postable', 'publishedBy']);

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Public post updated successfully',
                    'data' => $publicPost,
                ]);
            }

            return redirect()->route('public-posts')->with('success', 'Public post updated successfully');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to update public post',
                    'error' => $e->getMessage(),
                ], 500);
            }

            return back()->withErrors(['error' => 'Failed to update public post: '.$e->getMessage()]);
        }
    }

    /**
     * Remove the specified public post from storage (soft delete).
     */
    public function destroy(Request $request, PublicPost $publicPost)
    {
        try {
            $publicPost->delete();

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Public post deleted successfully',
                ]);
            }

            return redirect()->route('public-posts')->with('success', 'Public post deleted successfully');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to delete public post',
                    'error' => $e->getMessage(),
                ], 500);
            }

            return back()->withErrors(['error' => 'Failed to delete public post: '.$e->getMessage()]);
        }
    }

    /**
     * Publish a public post immediately.
     */
    public function publish(Request $request, PublicPost $publicPost)
    {
        try {
            $publicPost->publish();
            $publicPost->load(['postable', 'publishedBy']);

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Public post published successfully',
                    'data' => $publicPost,
                ]);
            }

            return redirect()->route('public-posts')->with('success', 'Public post published successfully');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to publish public post',
                    'error' => $e->getMessage(),
                ], 500);
            }

            return back()->withErrors(['error' => 'Failed to publish public post: '.$e->getMessage()]);
        }
    }

    /**
     * Unpublish a public post (set published_at to null).
     */
    public function unpublish(Request $request, PublicPost $publicPost)
    {
        try {
            $publicPost->update(['published_at' => null]);
            $publicPost->load(['postable', 'publishedBy']);

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Public post unpublished successfully',
                    'data' => $publicPost,
                ]);
            }

            return redirect()->route('public-posts')->with('success', 'Public post unpublished successfully');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to unpublish public post',
                    'error' => $e->getMessage(),
                ], 500);
            }

            return back()->withErrors(['error' => 'Failed to unpublish public post: '.$e->getMessage()]);
        }
    }

    /**
     * Get available reports that can be turned into public posts.
     */
    public function getAvailableReports(Request $request): JsonResponse
    {
        try {
            $query = Report::with('user')
                ->whereDoesntHave('publicPost')
                ->where('is_acknowledge', true)
                ->where('status', 'Ongoing')
                ->orderBy('created_at', 'desc');

            // Search functionality
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('transcript', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('report_type', 'like', "%{$search}%");
                });
            }

            // Filter by report type
            if ($request->has('report_type')) {
                $query->where('report_type', $request->report_type);
            }

            $reports = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'status' => 'success',
                'message' => 'Available reports retrieved successfully',
                'data' => $reports,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve available reports',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get public posts statistics.
     */
    public function getStats(): JsonResponse
    {
        try {
            $stats = [
                'total_posts' => PublicPost::count(),
                'published_posts' => PublicPost::published()->count(),
                'unpublished_posts' => PublicPost::unpublished()->count(),
                'scheduled_posts' => PublicPost::where('published_at', '>', now())->count(),
                'posts_this_month' => PublicPost::whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
                'recent_posts' => PublicPost::with(['postable', 'publishedBy'])
                    ->published()
                    ->limit(5)
                    ->latest('published_at')
                    ->get(),
            ];

            return response()->json([
                'status' => 'success',
                'message' => 'Statistics retrieved successfully',
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk actions on public posts.
     */
    public function bulkAction(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'action' => 'required|in:publish,unpublish,delete',
                'post_ids' => 'required|array|min:1',
                'post_ids.*' => 'exists:public_posts,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $posts = PublicPost::whereIn('id', $request->post_ids);
            $count = $posts->count();

            switch ($request->action) {
                case 'publish':
                    $posts->update(['published_at' => now()]);
                    $message = "{$count} posts published successfully";
                    break;
                case 'unpublish':
                    $posts->update(['published_at' => null]);
                    $message = "{$count} posts unpublished successfully";
                    break;
                case 'delete':
                    $posts->delete();
                    $message = "{$count} posts deleted successfully";
                    break;
            }

            return response()->json([
                'status' => 'success',
                'message' => $message,
                'affected_count' => $count,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bulk action failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Resolve the accident associated with a public post
     */
    public function resolve(PublicPost $publicPost)
    {
        try {
            // Check if the post is associated with an accident
            if (! $publicPost->postable || ! ($publicPost->postable instanceof \App\Models\Accident)) {
                return back()->with('error', 'This post is not associated with an accident.');
            }

            $accident = $publicPost->postable;

            // Check if already resolved
            if ($accident->status === 'resolved') {
                return back()->with('error', 'This accident is already resolved.');
            }

            // Check if status is ongoing
            if ($accident->status !== 'ongoing') {
                return back()->with('error', 'Only ongoing accidents can be resolved.');
            }

            \Illuminate\Support\Facades\DB::beginTransaction();
            try {
                // Update accident status to resolved
                $accident->update([
                    'status' => 'resolved',
                ]);

                // Update the public post title to indicate it's resolved
                $publicPost->update([
                    'title' => str_contains($publicPost->title, '[RESOLVED]')
                        ? $publicPost->title
                        : '[RESOLVED] '.$publicPost->title,
                ]);

                \Illuminate\Support\Facades\DB::commit();

                return back()->with('success', 'Accident resolved successfully.');
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to resolve accident: '.$e->getMessage());
        }
    }
}
