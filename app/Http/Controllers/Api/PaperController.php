<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Paper;
use App\Models\PaperAuthor;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Notification;

class PaperController extends Controller
{
    public function index(Request $request)
    {
        $query = Paper::with(['author', 'assignedReviewer', 'coAuthors', 'latestReview']);

        // Authors can only see their own papers
        if ($request->user()->isAuthor()) {
            $query->where('author_id', $request->user()->id);
        }

        // Reviewers can only see papers assigned to them
        if ($request->user()->isReviewer()) {
            $query->where('assigned_reviewer_id', $request->user()->id);
        }

        // Filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $searchTerm = strtolower($request->search);
            $query->where(function ($q) use ($searchTerm) {
                $q->whereRaw('LOWER(title) LIKE ?', ['%' . $searchTerm . '%'])
                  ->orWhereRaw('LOWER(abstract) LIKE ?', ['%' . $searchTerm . '%'])
                  ->orWhereRaw('LOWER(keywords) LIKE ?', ['%' . $searchTerm . '%']);
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        return response()->json(
            $query->latest()->paginate($request->get('per_page', 15))
        );
    }

    public function publicIndex(Request $request)
    {
        $query = Paper::with(['author', 'coAuthors'])
            ->where('status', 'published');

        if ($request->filled('search')) {
            $searchTerm = strtolower($request->search);
            $query->where(function ($q) use ($searchTerm) {
                $q->whereRaw('LOWER(title) LIKE ?', ['%' . $searchTerm . '%'])
                  ->orWhereRaw('LOWER(abstract) LIKE ?', ['%' . $searchTerm . '%'])
                  ->orWhereRaw('LOWER(keywords) LIKE ?', ['%' . $searchTerm . '%']);
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        return response()->json(
            $query->latest()->paginate($request->get('per_page', 12))
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:500',
            'abstract' => 'required|string',
            'keywords' => 'nullable|string|max:500',
            'category' => 'nullable|string|max:255',
            'file' => 'nullable|file|mimes:pdf|max:20480', // 20MB max
            'word_file' => 'nullable|file|mimes:doc,docx|max:20480', // 20MB max
            'co_authors' => 'nullable|array',
            'co_authors.*.name' => 'required_with:co_authors|string',
            'co_authors.*.email' => 'nullable|email',
            'co_authors.*.institution' => 'nullable|string',
        ]);

        $filePath = null;
        $fileName = null;
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();
            $filePath = $file->store('papers', 'local');
        }

        $wordFilePath = null;
        $wordFileName = null;
        if ($request->hasFile('word_file')) {
            $wordFile = $request->file('word_file');
            $wordFileName = $wordFile->getClientOriginalName();
            $wordFilePath = $wordFile->store('papers/word', 'local');
        }

        $paper = Paper::create([
            'title' => $request->title,
            'abstract' => $request->abstract,
            'category' => $request->category,
            'keywords' => $request->keywords,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'word_file_path' => $wordFilePath,
            'word_file_name' => $wordFileName,
            'author_id' => $request->user()->id,
            'status' => Paper::STATUS_PENDING,
            'version' => 1,
        ]);

        if ($request->has('co_authors')) {
            foreach ($request->co_authors as $i => $author) {
                PaperAuthor::create([
                    'paper_id' => $paper->id,
                    'name' => $author['name'],
                    'email' => $author['email'] ?? null,
                    'institution' => $author['institution'] ?? null,
                    'order' => $i + 1,
                ]);
            }
        }

        ActivityLog::log('paper_submitted', "Paper '{$paper->title}' submitted", $paper);

        // Notify all admins
        \App\Models\User::where('role', 'admin')->orWhere('role', 'super_admin')->get()->each(function ($admin) use ($paper) {
            Notification::notify(
                $admin->id,
                'paper_submitted',
                'Paper Baru Disubmit',
                "Paper \"" . $paper->title . "\" telah disubmit oleh {$paper->author->name}.",
                '/papers'
            );
        });

        return response()->json($paper->load(['author', 'coAuthors']), 201);
    }

    public function show(Request $request, Paper $paper)
    {
        // Authors can only view their own papers (unless published)
        if ($request->user() && $request->user()->isAuthor() && $paper->author_id !== $request->user()->id && $paper->status !== 'published') {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return response()->json($paper->load(['author', 'assignedReviewer', 'coAuthors', 'reviews.reviewer']));
    }

    public function update(Request $request, Paper $paper)
    {
        $user = $request->user();

        // Only author can update pending/revision papers
        if ($user->isAuthor()) {
            if ($paper->author_id !== $user->id) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }
            if (!in_array($paper->status, ['pending', 'revision'])) {
                return response()->json(['message' => 'Paper cannot be edited at this stage.'], 422);
            }
        }

        $request->validate([
            'title' => 'sometimes|string|max:500',
            'abstract' => 'sometimes|string',
            'category' => 'nullable|string|max:255',
            'keywords' => 'nullable|string|max:500',
            'file' => 'nullable|file|mimes:pdf|max:20480',
            'word_file' => 'nullable|file|mimes:doc,docx|max:20480',
            'status' => 'sometimes|in:pending,under_review,accepted,revision,rejected,published',
            'assigned_reviewer_id' => 'nullable|exists:users,id',
            'admin_notes' => 'nullable|string',
        ]);

        if ($request->hasFile('file')) {
            if ($paper->file_path) {
                Storage::disk('local')->delete($paper->file_path);
            }
            $file = $request->file('file');
            $paper->file_name = $file->getClientOriginalName();
            $paper->file_path = $file->store('papers', 'local');
            $paper->version += 1;
        }

        if ($request->hasFile('word_file')) {
            if ($paper->word_file_path) {
                Storage::disk('local')->delete($paper->word_file_path);
            }
            $wordFile = $request->file('word_file');
            $paper->word_file_name = $wordFile->getClientOriginalName();
            $paper->word_file_path = $wordFile->store('papers/word', 'local');
        }

        $paper->fill($request->only(['title', 'abstract', 'category', 'keywords', 'status', 'assigned_reviewer_id', 'admin_notes']));
        $paper->save();

        ActivityLog::log('paper_updated', "Paper '{$paper->title}' updated to status: {$paper->status}", $paper);

        // Notify author when status changes
        if ($request->has('status') && $paper->wasChanged('status')) {
            $statusMessages = [
                'accepted'     => '🎉 Paper Anda telah Diterima! Selamat.',
                'rejected'     => '❌ Paper Anda Ditolak. Silakan lihat catatan dari reviewer.',
                'revision'     => '📝 Paper Anda Perlu Direvisi. Mohon lakukan perbaikan.',
                'under_review' => '🔍 Paper Anda sedang Direview oleh reviewer.',
                'published'    => '🌐 Paper Anda telah Dipublikasikan!',
            ];
            $msg = $statusMessages[$paper->status] ?? "Status paper Anda berubah menjadi: {$paper->status}.";
            Notification::notify(
                $paper->author_id,
                'paper_status_changed',
                'Status Paper Diperbarui',
                $msg . " (\"" . $paper->title . "\")" ,
                '/my-papers'
            );
        }

        return response()->json($paper->load(['author', 'assignedReviewer', 'coAuthors']));
    }

    public function destroy(Paper $paper)
    {
        if ($paper->file_path) {
            Storage::disk('local')->delete($paper->file_path);
        }
        if ($paper->word_file_path) {
            Storage::disk('local')->delete($paper->word_file_path);
        }
        ActivityLog::log('paper_deleted', "Paper '{$paper->title}' deleted", null);
        $paper->delete();

        return response()->json(['message' => 'Paper berhasil dihapus.']);
    }

    public function assignReviewer(Request $request, Paper $paper)
    {
        $request->validate([
            'reviewer_id' => 'required|exists:users,id',
        ]);

        $reviewer = \App\Models\User::findOrFail($request->reviewer_id);
        if (!$reviewer->isReviewer()) {
            return response()->json(['message' => 'User bukan reviewer.'], 422);
        }

        $paper->update([
            'assigned_reviewer_id' => $request->reviewer_id,
            'status' => Paper::STATUS_UNDER_REVIEW,
        ]);

        ActivityLog::log('reviewer_assigned', "Reviewer '{$reviewer->name}' assigned to paper '{$paper->title}'", $paper);

        // Notify the assigned reviewer
        Notification::notify(
            $reviewer->id,
            'reviewer_assigned',
            'Anda Ditugaskan Sebagai Reviewer',
            "Anda telah ditugaskan untuk mereview paper: \"" . $paper->title . "\".",
            '/review-queue'
        );

        // Notify author
        Notification::notify(
            $paper->author_id,
            'reviewer_assigned',
            'Reviewer Telah Ditetapkan',
            "Reviewer telah ditetapkan untuk paper Anda: \"" . $paper->title . "\".",
            '/my-papers'
        );

        // Simulate sending email to reviewer
        \Illuminate\Support\Facades\Log::info("EMAIL NOTIFICATION: Paper '{$paper->title}' assigned to Reviewer '{$reviewer->email}'.");

        return response()->json($paper->load(['author', 'assignedReviewer']));
    }

    public function download(Request $request, Paper $paper)
    {
        // Access control
        $user = $request->user();
        $canDownload = false;

        if ($paper->status === 'published') {
            $canDownload = true; // Public can download published papers
        } elseif ($user) {
            if ($user->isAdmin() || $paper->author_id === $user->id || $paper->assigned_reviewer_id === $user->id) {
                $canDownload = true;
            }
        }

        if (!$canDownload) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (!$paper->file_path || !Storage::disk('local')->exists($paper->file_path)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        return Storage::disk('local')->download($paper->file_path, $paper->file_name);
    }

    public function downloadWord(Request $request, Paper $paper)
    {
        // Access control
        $user = $request->user();
        $canDownload = false;

        if ($paper->status === 'published') {
            $canDownload = true; // Public can download published papers
        } elseif ($user) {
            if ($user->isAdmin() || $paper->author_id === $user->id || $paper->assigned_reviewer_id === $user->id) {
                $canDownload = true;
            }
        }

        if (!$canDownload) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (!$paper->word_file_path || !Storage::disk('local')->exists($paper->word_file_path)) {
            return response()->json(['message' => 'Word file not found'], 404);
        }

        return Storage::disk('local')->download($paper->word_file_path, $paper->word_file_name);
    }

    public function exportCsv()
    {
        $papers = Paper::with(['author', 'assignedReviewer'])->orderBy('created_at', 'desc')->get();
        $filename = 'papers_export_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['ID', 'Judul', 'Kategori', 'Status', 'Author', 'Institusi', 'Reviewer', 'Tanggal Submit'];

        $callback = function() use($papers, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($papers as $paper) {
                $row = [
                    $paper->id,
                    $paper->title,
                    $paper->category ?? '-',
                    $paper->status,
                    $paper->author ? $paper->author->name : '-',
                    $paper->author ? $paper->author->institution : '-',
                    $paper->assignedReviewer ? $paper->assignedReviewer->name : '-',
                    $paper->created_at->format('Y-m-d H:i:s')
                ];
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
