<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Paper;
use App\Models\User;
use App\Models\Review;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return $this->adminDashboard();
        }

        if ($user->isReviewer()) {
            return $this->reviewerDashboard($user);
        }

        return $this->authorDashboard($user);
    }

    private function adminDashboard()
    {
        return Cache::remember('admin_dashboard_data', 60, function () {
            $totalPapers = Paper::count();
            $totalUsers = User::count();
            $totalReviewers = User::where('role', 'reviewer')->count();

            $statusCounts = Paper::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status');

            // Submissions per month (last 6 months)
            $driver = DB::getDriverName();
            $dateFormat = $driver === 'sqlite' ? "strftime('%Y-%m', created_at)" : "DATE_FORMAT(created_at, '%Y-%m')";
            
            $submissionTrend = Paper::select(
                    DB::raw("{$dateFormat} as month"),
                    DB::raw('count(*) as count')
                )
                ->where('created_at', '>=', now()->subMonths(6))
                ->groupBy('month')
                ->orderBy('month')
                ->get();

            // Acceptance rate
            $accepted = Paper::whereIn('status', ['accepted', 'published'])->count();
            $reviewed = Paper::whereIn('status', ['accepted', 'published', 'rejected'])->count();
            $acceptanceRate = $reviewed > 0 ? round(($accepted / $reviewed) * 100, 1) : 0;

            $recentPapers = Paper::with(['author'])->latest()->take(5)->get();
            $recentActivity = ActivityLog::with('user')->latest()->take(10)->get();

            // Category distribution
            $categoryDistribution = Paper::whereNotNull('category')
                ->select('category', DB::raw('count(*) as count'))
                ->groupBy('category')
                ->orderByDesc('count')
                ->get();

            return response()->json([
                'total_papers' => $totalPapers,
                'total_users' => $totalUsers,
                'total_reviewers' => $totalReviewers,
                'status_counts' => $statusCounts,
                'submission_trend' => $submissionTrend,
                'acceptance_rate' => $acceptanceRate,
                'category_distribution' => $categoryDistribution,
                'recent_papers' => $recentPapers,
                'recent_activity' => $recentActivity,
            ]);
        });
    }

    private function reviewerDashboard(User $user)
    {
        return Cache::remember("reviewer_dashboard_{$user->id}", 60, function () use ($user) {
            $assigned = Paper::where('assigned_reviewer_id', $user->id)->count();
            $reviewed = Review::where('reviewer_id', $user->id)->count();
            $pending = Paper::where('assigned_reviewer_id', $user->id)->where('status', 'under_review')->count();

            $recentAssignments = Paper::with(['author'])
                ->where('assigned_reviewer_id', $user->id)
                ->where('status', 'under_review')
                ->latest()
                ->take(5)
                ->get();

            return response()->json([
                'total_assigned' => $assigned,
                'total_reviewed' => $reviewed,
                'pending_reviews' => $pending,
                'recent_assignments' => $recentAssignments,
            ]);
        });
    }

    private function authorDashboard(User $user)
    {
        return Cache::remember("author_dashboard_{$user->id}", 60, function () use ($user) {
            $statusCounts = Paper::where('author_id', $user->id)
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status');

            $recentPapers = Paper::with(['latestReview'])
                ->where('author_id', $user->id)
                ->latest()
                ->take(5)
                ->get();

            return response()->json([
                'status_counts' => $statusCounts,
                'recent_papers' => $recentPapers,
                'total_papers' => array_sum($statusCounts->toArray()),
            ]);
        });
    }
}
