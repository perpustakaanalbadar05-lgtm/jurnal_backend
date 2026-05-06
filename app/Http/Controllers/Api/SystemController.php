<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class SystemController extends Controller
{
    private $settingsFile = 'settings.json';

    public function getSettings()
    {
        if (!Storage::disk('local')->exists($this->settingsFile)) {
            return response()->json([
                'university_name' => 'Universitas Teknologi Jurnal',
                'description' => 'Sistem Manajemen Publikasi Akademik Resmi',
                'contact_email' => 'admin@kampus.ac.id',
                'address' => 'Jl. Pendidikan No. 1, Kota Akademik',
            ]);
        }

        $settings = json_decode(Storage::disk('local')->get($this->settingsFile), true);
        return response()->json($settings);
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'university_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'contact_email' => 'required|email',
            'address' => 'nullable|string',
        ]);

        $settings = $request->only(['university_name', 'description', 'contact_email', 'address']);
        
        Storage::disk('local')->put($this->settingsFile, json_encode($settings, JSON_PRETTY_PRINT));

        // Log the activity
        \App\Models\ActivityLog::log('settings_updated', "System settings updated by " . $request->user()->name, null);

        return response()->json(['message' => 'Settings updated successfully', 'settings' => $settings]);
    }

    public function downloadBackup(Request $request)
    {
        // Only super_admin is allowed (middleware will handle this, but just to be safe)
        if ($request->user()->role !== 'super_admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $dbPath = database_path('database.sqlite');
        
        if (!File::exists($dbPath)) {
            return response()->json(['message' => 'Database file not found'], 404);
        }

        $filename = 'backup_apms_' . date('Y_m_d_His') . '.sqlite';
        
        \App\Models\ActivityLog::log('database_backup', "Database backup downloaded by " . $request->user()->name, null);

        return response()->download($dbPath, $filename, [
            'Content-Type' => 'application/x-sqlite3',
        ]);
    }
}
