<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Paper;
use App\Models\PaperAuthor;
use App\Models\Review;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admin
        $superAdmin = User::create([
            'name' => 'Super Administrator',
            'email' => 'superadmin@abdimu.ac.id',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'is_active' => true,
            'institution' => 'Institut Agama Islam Miftahul Ulum (IAIMU)',
        ]);

        // Admin
        $admin = User::create([
            'name' => 'Ahmad Fauzi',
            'email' => 'admin@abdimu.ac.id',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
            'institution' => 'Universitas Teknologi Indonesia',
        ]);

        // Reviewers
        $reviewer1 = User::create([
            'name' => 'Dr. Siti Rahma',
            'email' => 'reviewer1@abdimu.ac.id',
            'password' => Hash::make('password'),
            'role' => 'reviewer',
            'is_active' => true,
            'institution' => 'Institut Teknologi Bandung',
            'bio' => 'Pakar di bidang Kecerdasan Buatan dan Machine Learning.',
        ]);

        $reviewer2 = User::create([
            'name' => 'Prof. Budi Santoso',
            'email' => 'reviewer2@abdimu.ac.id',
            'password' => Hash::make('password'),
            'role' => 'reviewer',
            'is_active' => true,
            'institution' => 'Universitas Gadjah Mada',
            'bio' => 'Peneliti senior di bidang Sistem Informasi.',
        ]);

        // Authors
        $author1 = User::create([
            'name' => 'Mustofa Fikri',
            'email' => 'author1@abdimu.ac.id',
            'password' => Hash::make('password'),
            'role' => 'author',
            'is_active' => true,
            'institution' => 'Universitas Hasanuddin',
        ]);

        $author2 = User::create([
            'name' => 'Dewi Lestari',
            'email' => 'author2@abdimu.ac.id',
            'password' => Hash::make('password'),
            'role' => 'author',
            'is_active' => true,
            'institution' => 'Universitas Indonesia',
        ]);

        // Papers
        $paper1 = Paper::create([
            'title' => 'Implementasi Deep Learning untuk Klasifikasi Penyakit Tanaman',
            'abstract' => 'Penelitian ini mengusulkan metode berbasis deep learning menggunakan Convolutional Neural Network (CNN) untuk mengklasifikasikan penyakit tanaman padi secara otomatis. Dataset yang digunakan terdiri dari 10.000 gambar daun padi yang dikumpulkan dari berbagai daerah di Indonesia.',
            'keywords' => 'deep learning, CNN, klasifikasi, penyakit tanaman, padi',
            'author_id' => $author1->id,
            'status' => 'published',
            'version' => 2,
            'assigned_reviewer_id' => $reviewer1->id,
        ]);

        PaperAuthor::create(['paper_id' => $paper1->id, 'name' => 'Mustofa Fikri', 'institution' => 'Universitas Hasanuddin', 'order' => 1, 'is_corresponding' => true]);
        PaperAuthor::create(['paper_id' => $paper1->id, 'name' => 'Andi Rahman', 'institution' => 'Universitas Hasanuddin', 'order' => 2]);

        Review::create([
            'paper_id' => $paper1->id,
            'reviewer_id' => $reviewer1->id,
            'comment' => 'Penelitian ini sangat baik dan metodologinya solid. Hasil eksperimen menunjukkan akurasi yang tinggi. Saya merekomendasikan untuk diterima setelah perbaikan minor pada bagian kesimpulan.',
            'decision' => 'accept',
            'status' => 'completed',
        ]);

        $paper2 = Paper::create([
            'title' => 'Analisis Keamanan Sistem Informasi Berbasis Cloud menggunakan Zero-Trust Architecture',
            'abstract' => 'Paper ini membahas implementasi Zero-Trust Architecture pada sistem informasi berbasis cloud. Penelitian dilakukan dengan menganalisis kerentanan pada model keamanan tradisional dan membandingkannya dengan pendekatan Zero-Trust.',
            'keywords' => 'keamanan, cloud, zero-trust, sistem informasi',
            'author_id' => $author2->id,
            'status' => 'under_review',
            'version' => 1,
            'assigned_reviewer_id' => $reviewer2->id,
        ]);

        PaperAuthor::create(['paper_id' => $paper2->id, 'name' => 'Dewi Lestari', 'institution' => 'Universitas Indonesia', 'order' => 1, 'is_corresponding' => true]);

        $paper3 = Paper::create([
            'title' => 'Pengembangan Sistem Rekomendasi Produk E-Commerce menggunakan Collaborative Filtering',
            'abstract' => 'Penelitian ini mengembangkan sistem rekomendasi produk untuk platform e-commerce menggunakan algoritma Collaborative Filtering berbasis Matrix Factorization.',
            'keywords' => 'sistem rekomendasi, collaborative filtering, e-commerce, machine learning',
            'author_id' => $author1->id,
            'status' => 'pending',
            'version' => 1,
        ]);

        PaperAuthor::create(['paper_id' => $paper3->id, 'name' => 'Mustofa Fikri', 'institution' => 'Universitas Hasanuddin', 'order' => 1, 'is_corresponding' => true]);

        $paper4 = Paper::create([
            'title' => 'Optimasi Algoritma Genetika untuk Penjadwalan Mata Kuliah di Universitas',
            'abstract' => 'Penelitian ini mengusulkan pendekatan baru menggunakan Algoritma Genetika yang dioptimasi untuk menyelesaikan masalah penjadwalan mata kuliah yang kompleks.',
            'keywords' => 'algoritma genetika, penjadwalan, optimasi, universitas',
            'author_id' => $author2->id,
            'status' => 'revision',
            'version' => 1,
            'assigned_reviewer_id' => $reviewer1->id,
        ]);

        PaperAuthor::create(['paper_id' => $paper4->id, 'name' => 'Dewi Lestari', 'institution' => 'Universitas Indonesia', 'order' => 1, 'is_corresponding' => true]);

        Review::create([
            'paper_id' => $paper4->id,
            'reviewer_id' => $reviewer1->id,
            'comment' => 'Paper ini memiliki konsep yang menarik namun perlu perbaikan pada bagian metodologi. Tolong perjelas parameter yang digunakan dalam algoritma genetika dan tambahkan perbandingan dengan metode lain.',
            'decision' => 'minor_revision',
            'status' => 'completed',
        ]);

        $this->command->info('✅ Database seeded successfully!');
        $this->command->info('');
        $this->command->info('📋 Test Accounts:');
        $this->command->info('   Super Admin : superadmin@abdimu.ac.id / password');
        $this->command->info('   Admin       : admin@abdimu.ac.id / password');
        $this->command->info('   Reviewer 1  : reviewer1@abdimu.ac.id / password');
        $this->command->info('   Reviewer 2  : reviewer2@abdimu.ac.id / password');
        $this->command->info('   Author 1    : author1@abdimu.ac.id / password');
        $this->command->info('   Author 2    : author2@abdimu.ac.id / password');
    }
}
