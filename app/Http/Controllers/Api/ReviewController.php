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
                $paper->reviews()->with('reviewer')->select(['id', 'paper_id', 'reviewer_id', 'comment', 'decision', 'created_at'])->get()
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
        ]);

        $paper = Paper::findOrFail($request->paper_id);
        $user = $request->user();

        // Only assigned reviewer can review
        if ($user->isReviewer() && $paper->assigned_reviewer_id !== $user->id) {
            return response()->json(['message' => 'You are not assigned to review this paper.'], 403);
        }

        $review = Review::create([
            'paper_id' => $request->paper_id,
            'reviewer_id' => $user->id,
            'comment' => $request->comment,
            'private_comment' => $request->private_comment,
            'decision' => $request->decision,
            'status' => 'completed',
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
}
