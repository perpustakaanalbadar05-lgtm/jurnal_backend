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
        $defaultSettings = [
            'university_name' => 'Universitas Teknologi Jurnal',
            'description' => 'Sistem Manajemen Publikasi Akademik Resmi',
            'contact_email' => 'admin@kampus.ac.id',
            'address' => 'Jl. Pendidikan No. 1, Kota Akademik',
            'categories' => ['Penelitian', 'Pengabdian Kepada Masyarakat'],
        ];

        if (!Storage::disk('local')->exists($this->settingsFile)) {
            return response()->json($defaultSettings);
        }

        $settings = json_decode(Storage::disk('local')->get($this->settingsFile), true);
        
        // Merge with defaults to ensure missing fields (like categories) are populated
        $settings = array_merge($defaultSettings, $settings);
        
        return response()->json($settings);
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'university_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'contact_email' => 'required|email',
            'address' => 'nullable|string',
            'categories' => 'nullable|array',
            'categories.*' => 'required|string|max:255',
        ]);

        $settings = $request->only(['university_name', 'description', 'contact_email', 'address', 'categories']);
        
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
