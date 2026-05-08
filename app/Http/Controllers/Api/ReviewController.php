<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Paper;
use App\Models\ActivityLog;
use App\Models\Notification;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request, Paper $paper)
    {
        $user = $request->user();

        // Reviewers can only see reviews for their assigned papers
        if ($user->isReviewer() && $paper->assigned_reviewer_id !== $user->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        // Authors can only see public comments on their own papers
        if ($user->isAuthor()) {
            if ($paper->author_id !== $user->id) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }
            return response()->json(
                $paper->reviews()->with('reviewer')->select(['id', 'paper_id', 'reviewer_id', 'comment', 'decision', 'file_path', 'file_name', 'word_file_path', 'word_file_name', 'created_at'])->get()
            );
        }

        return response()->json(
            $paper->reviews()->with('reviewer')->latest()->get()
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'paper_id' => 'required|exists:papers,id',
            'comment' => 'required|string',
            'private_comment' => 'nullable|string',
            'decision' => 'required|in:accept,minor_revision,major_revision,reject',
            'file' => 'nullable|file|mimes:pdf|max:20480',
            'word_file' => 'nullable|file|mimes:doc,docx|max:20480',
        ]);

        $paper = Paper::findOrFail($request->paper_id);
        $user = $request->user();

        // Only assigned reviewer can review
        if ($user->isReviewer() && $paper->assigned_reviewer_id !== $user->id) {
            return response()->json(['message' => 'You are not assigned to review this paper.'], 403);
        }

        $filePath = null;
        $fileName = null;
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();
            $filePath = $file->store('reviews', 'local');
        }

        $wordFilePath = null;
        $wordFileName = null;
        if ($request->hasFile('word_file')) {
            $wordFile = $request->file('word_file');
            $wordFileName = $wordFile->getClientOriginalName();
            $wordFilePath = $wordFile->store('reviews/word', 'local');
        }

        $review = Review::create([
            'paper_id' => $request->paper_id,
            'reviewer_id' => $user->id,
            'comment' => $request->comment,
            'private_comment' => $request->private_comment,
            'decision' => $request->decision,
            'status' => 'completed',
            'file_path' => $filePath,
            'file_name' => $fileName,
            'word_file_path' => $wordFilePath,
            'word_file_name' => $wordFileName,
        ]);

        // Auto-update paper status based on decision
        $statusMap = [
            'accept' => Paper::STATUS_ACCEPTED,
            'minor_revision' => Paper::STATUS_REVISION,
            'major_revision' => Paper::STATUS_REVISION,
            'reject' => Paper::STATUS_REJECTED,
        ];

        $paper->update(['status' => $statusMap[$request->decision]]);

        ActivityLog::log('review_submitted', "Review submitted for paper '{$paper->title}' with decision: {$request->decision}", $paper);

        // Notify the author of the review
        $decisionMessages = [
            'accept'         => '🎉 Paper Anda mendapat keputusan: Diterima.',
            'minor_revision' => '📝 Paper Anda memerlukan revisi minor.',
            'major_revision' => '📝 Paper Anda memerlukan revisi mayor.',
            'reject'         => '❌ Paper Anda mendapat keputusan: Ditolak.',
        ];
        Notification::notify(
            $paper->author_id,
            'review_submitted',
            'Hasil Review Tersedia',
            ($decisionMessages[$request->decision] ?? 'Review baru untuk paper Anda.') . " (\"" . $paper->title . "\")",
            '/my-papers'
        );

        return response()->json($review->load('reviewer'), 201);
    }

    public function update(Request $request, Review $review)
    {
        $request->validate([
            'comment' => 'sometimes|string',
            'private_comment' => 'nullable|string',
            'decision' => 'sometimes|in:accept,minor_revision,major_revision,reject',
        ]);

        $review->update($request->only(['comment', 'private_comment', 'decision']));

        return response()->json($review->load('reviewer'));
    }

    public function myQueue(Request $request)
    {
        $papers = Paper::with(['author', 'coAuthors', 'latestReview'])
            ->where('assigned_reviewer_id', $request->user()->id)
            ->whereIn('status', ['under_review'])
            ->latest()
            ->get();

        return response()->json($papers);
    }

    public function download(Request $request, Review $review)
    {
        $user = $request->user();
        $paper = $review->paper;
        $canDownload = false;

        if ($user) {
            if ($user->isAdmin() || $paper->author_id === $user->id || $paper->assigned_reviewer_id === $user->id) {
                $canDownload = true;
            }
        }

        if (!$canDownload) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if (!$review->file_path || !\Illuminate\Support\Facades\Storage::disk('local')->exists($review->file_path)) {
            return response()->json(['message' => 'File not found.'], 404);
        }

        return \Illuminate\Support\Facades\Storage::disk('local')->download($review->file_path, $review->file_name);
    }

    public function downloadWord(Request $request, Review $review)
    {
        $user = $request->user();
        $paper = $review->paper;
        $canDownload = false;

        if ($user) {
            if ($user->isAdmin() || $paper->author_id === $user->id || $paper->assigned_reviewer_id === $user->id) {
                $canDownload = true;
            }
        }

        if (!$canDownload) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if (!$review->word_file_path || !\Illuminate\Support\Facades\Storage::disk('local')->exists($review->word_file_path)) {
            return response()->json(['message' => 'Word file not found.'], 404);
        }

        return \Illuminate\Support\Facades\Storage::disk('local')->download($review->word_file_path, $review->word_file_name);
    }

    public function myHistory(Request $request)
    {
        $papers = Paper::with(['author', 'coAuthors', 'reviews' => function($query) use ($request) {
            $query->where('reviewer_id', $request->user()->id)->with('reviewer');
        }])
            ->whereHas('reviews', function($query) use ($request) {
                $query->where('reviewer_id', $request->user()->id);
            })
            ->latest()
            ->get();

        return response()->json($papers);
    }
}
