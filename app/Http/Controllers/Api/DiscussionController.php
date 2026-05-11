<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Discussion;
use App\Models\Paper;
use App\Models\ActivityLog;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;

class DiscussionController extends Controller
{
    public function index(Request $request, Paper $paper)
    {
        $user = $request->user();

        // Check if user has access to this paper's discussions
        if (!$user->isAdmin()) {
            if ($user->isReviewer() && $paper->assigned_reviewer_id != $user->id) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }
            if ($user->isAuthor() && $paper->author_id != $user->id) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }
        }

        $discussions = $paper->discussions()
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($discussions);
    }

    public function store(Request $request, Paper $paper)
    {
        $user = $request->user();

        // Check if user has access to this paper's discussions
        if (!$user->isAdmin()) {
            if ($user->isReviewer() && $paper->assigned_reviewer_id != $user->id) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }
            if ($user->isAuthor() && $paper->author_id != $user->id) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }
        }

        $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $discussion = Discussion::create([
            'paper_id' => $paper->id,
            'user_id' => $user->id,
            'message' => $request->message,
        ]);

        ActivityLog::log('discussion_posted', "Discussion message posted on paper '{$paper->title}' by {$user->name}", $paper);

        // Notify other participants
        if ($user->isAuthor()) {
            $admins = User::whereIn('role', ['admin', 'super_admin'])->get();
            foreach ($admins as $admin) {
                Notification::notify(
                    $admin->id,
                    'discussion_posted',
                    'Diskusi Baru dari Author',
                    "Dosen {$user->name} mengirim pesan baru pada naskah: \"{$paper->title}\"",
                    '/papers'
                );
            }

            if ($paper->assigned_reviewer_id) {
                Notification::notify(
                    $paper->assigned_reviewer_id,
                    'discussion_posted',
                    'Diskusi Baru dari Author',
                    "Dosen {$user->name} mengirim pesan baru pada naskah: \"{$paper->title}\"",
                    '/review-queue'
                );
            }
        } elseif ($user->isReviewer()) {
            $admins = User::whereIn('role', ['admin', 'super_admin'])->get();
            foreach ($admins as $admin) {
                Notification::notify(
                    $admin->id,
                    'discussion_posted',
                    'Diskusi Baru dari Reviewer',
                    "Reviewer {$user->name} mengirim pesan baru pada naskah: \"{$paper->title}\"",
                    '/papers'
                );
            }

            Notification::notify(
                $paper->author_id,
                'discussion_posted',
                'Diskusi Baru dari Reviewer',
                "Reviewer {$user->name} mengirim pesan baru pada naskah: \"{$paper->title}\"",
                '/my-papers'
            );
        } else {
            Notification::notify(
                $paper->author_id,
                'discussion_posted',
                'Pesan Baru dari Pengelola Jurnal',
                "Pengelola Jurnal mengirim pesan baru pada naskah: \"{$paper->title}\"",
                '/my-papers'
            );

            if ($paper->assigned_reviewer_id) {
                Notification::notify(
                    $paper->assigned_reviewer_id,
                    'discussion_posted',
                    'Pesan Baru dari Pengelola Jurnal',
                    "Pengelola Jurnal mengirim pesan baru pada naskah: \"{$paper->title}\"",
                    '/review-queue'
                );
            }
        }

        return response()->json($discussion->load('user'), 201);
    }
}
