SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
START TRANSACTION;

CREATE TABLE IF NOT EXISTS `activity_logs` (
	`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	`user_id` bigint(20) unsigned,
	`action` varchar(255) NOT NULL,
	`description` text,
	`subject_type` varchar(255),
	`subject_id` bigint(20) unsigned,
	`properties` text,
	`ip_address` varchar(255),
	`created_at` datetime,
	`updated_at` datetime,
	PRIMARY KEY (`id`),
	FOREIGN KEY(`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `cache` (
	`key` varchar(255) NOT NULL,
	`value` text NOT NULL,
	`expiration` int(11) NOT NULL,
	PRIMARY KEY(`key`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `cache_locks` (
	`key` varchar(255) NOT NULL,
	`owner` varchar(255) NOT NULL,
	`expiration` int(11) NOT NULL,
	PRIMARY KEY(`key`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `discussions` (
	`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	`paper_id` bigint(20) unsigned NOT NULL,
	`user_id` bigint(20) unsigned NOT NULL,
	`message` text NOT NULL,
	`created_at` datetime,
	`updated_at` datetime,
	PRIMARY KEY (`id`),
	FOREIGN KEY(`paper_id`) REFERENCES `papers`(`id`) ON DELETE CASCADE,
	FOREIGN KEY(`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `failed_jobs` (
	`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	`uuid` varchar(255) NOT NULL,
	`connection` text NOT NULL,
	`queue` text NOT NULL,
	`payload` text NOT NULL,
	`exception` text NOT NULL,
	`failed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP(),
	PRIMARY KEY (`id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `job_batches` (
	`id` varchar(255) NOT NULL,
	`name` varchar(255) NOT NULL,
	`total_jobs` int(11) NOT NULL,
	`pending_jobs` int(11) NOT NULL,
	`failed_jobs` int(11) NOT NULL,
	`failed_job_ids` text NOT NULL,
	`options` text,
	`cancelled_at` int(11),
	`created_at` int(11) NOT NULL,
	`finished_at` int(11),
	PRIMARY KEY(`id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `jobs` (
	`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	`queue` varchar(255) NOT NULL,
	`payload` text NOT NULL,
	`attempts` int(11) NOT NULL,
	`reserved_at` int(11),
	`available_at` int(11) NOT NULL,
	`created_at` int(11) NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `migrations` (
	`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	`migration` varchar(255) NOT NULL,
	`batch` int(11) NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `notifications` (
	`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	`user_id` bigint(20) unsigned NOT NULL,
	`type` varchar(255) NOT NULL,
	`title` varchar(255) NOT NULL,
	`message` text NOT NULL,
	`link` varchar(255),
	`read_at` datetime,
	`created_at` datetime,
	`updated_at` datetime,
	PRIMARY KEY (`id`),
	FOREIGN KEY(`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `paper_authors` (
	`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	`paper_id` bigint(20) unsigned NOT NULL,
	`name` varchar(255) NOT NULL,
	`email` varchar(255),
	`institution` varchar(255),
	`is_corresponding` tinyint(1) NOT NULL DEFAULT 0,
	`order` int(11) NOT NULL DEFAULT 0,
	`created_at` datetime,
	`updated_at` datetime,
	PRIMARY KEY (`id`),
	FOREIGN KEY(`paper_id`) REFERENCES `papers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `papers` (
	`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	`title` varchar(255) NOT NULL,
	`abstract` text NOT NULL,
	`keywords` varchar(255),
	`file_path` varchar(255),
	`file_name` varchar(255),
	`author_id` bigint(20) unsigned NOT NULL,
	`assigned_reviewer_id` bigint(20) unsigned,
	`status` varchar(255) NOT NULL DEFAULT 'pending',
	`version` int(11) NOT NULL DEFAULT 1,
	`admin_notes` text,
	`created_at` datetime,
	`updated_at` datetime,
	`word_file_path` varchar(255),
	`word_file_name` varchar(255),
	`category` varchar(255),
	PRIMARY KEY (`id`),
	FOREIGN KEY(`assigned_reviewer_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
	FOREIGN KEY(`author_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
	`email` varchar(255) NOT NULL,
	`token` varchar(255) NOT NULL,
	`created_at` datetime,
	PRIMARY KEY(`email`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `personal_access_tokens` (
	`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	`tokenable_type` varchar(255) NOT NULL,
	`tokenable_id` int(11) NOT NULL,
	`name` text NOT NULL,
	`token` varchar(255) NOT NULL,
	`abilities` text,
	`last_used_at` datetime,
	`expires_at` datetime,
	`created_at` datetime,
	`updated_at` datetime,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `reviews` (
	`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	`paper_id` bigint(20) unsigned NOT NULL,
	`reviewer_id` bigint(20) unsigned NOT NULL,
	`comment` text NOT NULL,
	`private_comment` text,
	`decision` varchar(255) NOT NULL,
	`status` varchar(255) NOT NULL DEFAULT 'completed',
	`created_at` datetime,
	`updated_at` datetime,
	`file_path` varchar(255),
	`file_name` varchar(255),
	`word_file_path` varchar(255),
	`word_file_name` varchar(255),
	PRIMARY KEY (`id`),
	FOREIGN KEY(`paper_id`) REFERENCES `papers`(`id`) ON DELETE CASCADE,
	FOREIGN KEY(`reviewer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `sessions` (
	`id` varchar(255) NOT NULL,
	`user_id` int(11),
	`ip_address` varchar(255),
	`user_agent` text,
	`payload` text NOT NULL,
	`last_activity` int(11) NOT NULL,
	PRIMARY KEY(`id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `users` (
	`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	`name` varchar(255) NOT NULL,
	`email` varchar(255) NOT NULL,
	`email_verified_at` datetime,
	`password` varchar(255) NOT NULL,
	`role` varchar(255) NOT NULL DEFAULT 'author',
	`is_active` tinyint(1) NOT NULL DEFAULT 1,
	`institution` varchar(255),
	`phone` varchar(255),
	`bio` text,
	`remember_token` varchar(255),
	`created_at` datetime,
	`updated_at` datetime,
	`avatar_path` varchar(255),
	PRIMARY KEY (`id`)
) ENGINE=InnoDB;

INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1);
INSERT INTO `migrations` VALUES (2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO `migrations` VALUES (3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO `migrations` VALUES (4,'2026_04_30_224851_create_paper_authors_table',1);
INSERT INTO `migrations` VALUES (5,'2026_04_30_224851_create_papers_table',1);
INSERT INTO `migrations` VALUES (6,'2026_04_30_224852_add_role_to_users_table',1);
INSERT INTO `migrations` VALUES (7,'2026_04_30_224852_create_activity_logs_table',1);
INSERT INTO `migrations` VALUES (8,'2026_04_30_224852_create_personal_access_tokens_table',1);
INSERT INTO `migrations` VALUES (9,'2026_04_30_224852_create_reviews_table',1);
INSERT INTO `migrations` VALUES (10,'2026_05_02_222731_add_word_file_to_papers_table',1);
INSERT INTO `migrations` VALUES (11,'2026_05_03_032256_add_category_to_papers_table',1);
INSERT INTO `migrations` VALUES (12,'2026_05_03_100000_create_notifications_table',1);
INSERT INTO `migrations` VALUES (13,'2026_05_03_100001_add_avatar_to_users_table',1);
INSERT INTO `migrations` VALUES (14,'2026_05_07_100000_add_file_to_reviews_table',1);
INSERT INTO `migrations` VALUES (15,'2026_05_08_100000_add_word_file_to_reviews_table',1);
INSERT INTO `migrations` VALUES (16,'2026_05_09_100000_create_discussions_table',2);

INSERT INTO `paper_authors` VALUES (1,1,'Mustofa Fikri',NULL,'Universitas Hasanuddin',1,1,'2026-05-08 17:11:30','2026-05-08 17:11:30');
INSERT INTO `paper_authors` VALUES (2,1,'Andi Rahman',NULL,'Universitas Hasanuddin',0,2,'2026-05-08 17:11:30','2026-05-08 17:11:30');
INSERT INTO `paper_authors` VALUES (3,2,'Dewi Lestari',NULL,'Universitas Indonesia',1,1,'2026-05-08 17:11:30','2026-05-08 17:11:30');
INSERT INTO `paper_authors` VALUES (4,3,'Mustofa Fikri',NULL,'Universitas Hasanuddin',1,1,'2026-05-08 17:11:30','2026-05-08 17:11:30');
INSERT INTO `paper_authors` VALUES (5,4,'Dewi Lestari',NULL,'Universitas Indonesia',1,1,'2026-05-08 17:11:30','2026-05-08 17:11:30');

INSERT INTO `papers` VALUES (1,'Implementasi Deep Learning untuk Klasifikasi Penyakit Tanaman','Penelitian ini mengusulkan metode berbasis deep learning menggunakan Convolutional Neural Network (CNN) untuk mengklasifikasikan penyakit tanaman padi secara otomatis. Dataset yang digunakan terdiri dari 10.000 gambar daun padi yang dikumpulkan dari berbagai daerah di Indonesia.','deep learning, CNN, klasifikasi, penyakit tanaman, padi',NULL,NULL,5,3,'published',2,NULL,'2026-05-08 17:11:30','2026-05-08 17:11:30',NULL,NULL,NULL);
INSERT INTO `papers` VALUES (2,'Analisis Keamanan Sistem Informasi Berbasis Cloud menggunakan Zero-Trust Architecture','Paper ini membahas implementasi Zero-Trust Architecture pada sistem informasi berbasis cloud. Penelitian dilakukan dengan menganalisis kerentanan pada model keamanan tradisional dan membandingkannya dengan pendekatan Zero-Trust.','keamanan, cloud, zero-trust, sistem informasi',NULL,NULL,6,4,'under_review',1,NULL,'2026-05-08 17:11:30','2026-05-08 17:11:30',NULL,NULL,NULL);
INSERT INTO `papers` VALUES (3,'Pengembangan Sistem Rekomendasi Produk E-Commerce menggunakan Collaborative Filtering','Penelitian ini mengembangkan sistem rekomendasi produk untuk platform e-commerce menggunakan algoritma Collaborative Filtering berbasis Matrix Factorization.','sistem rekomendasi, collaborative filtering, e-commerce, machine learning',NULL,NULL,5,NULL,'pending',1,NULL,'2026-05-08 17:11:30','2026-05-08 17:11:30',NULL,NULL,NULL);
INSERT INTO `papers` VALUES (4,'Optimasi Algoritma Genetika untuk Penjadwalan Mata Kuliah di Universitas','Penelitian ini mengusulkan pendekatan baru menggunakan Algoritma Genetika yang dioptimasi untuk menyelesaikan masalah penjadwalan mata kuliah yang kompleks.','algoritma genetika, penjadwalan, optimasi, universitas',NULL,NULL,6,3,'revision',1,NULL,'2026-05-08 17:11:30','2026-05-08 17:11:30',NULL,NULL,NULL);

INSERT INTO `reviews` VALUES (1,1,3,'Penelitian ini sangat baik dan metodologinya solid. Hasil eksperimen menunjukkan akurasi yang tinggi. Saya merekomendasikan untuk diterima setelah perbaikan minor pada bagian kesimpulan.',NULL,'accept','completed','2026-05-08 17:11:30','2026-05-08 17:11:30',NULL,NULL,NULL,NULL);
INSERT INTO `reviews` VALUES (2,4,3,'Paper ini memiliki konsep yang menarik namun perlu perbaikan pada bagian metodologi. Tolong perjelas parameter yang digunakan dalam algoritma genetika dan tambahkan perbandingan dengan metode lain.',NULL,'minor_revision','completed','2026-05-08 17:11:30','2026-05-08 17:11:30',NULL,NULL,NULL,NULL);

INSERT INTO `users` VALUES (1,'Super Administrator','superadmin@abdimu.ac.id',NULL,'$2y$12$F8eEoyVyOjy.Wp1KcOHbYeiMuCLMUToVC73lFli3BcZNfDXQufDVS','super_admin',1,'Institut Agama Islam Miftahul Ulum (IAIMU)',NULL,NULL,NULL,'2026-05-08 17:11:29','2026-05-08 17:11:29',NULL);
INSERT INTO `users` VALUES (2,'Ahmad Fauzi','admin@abdimu.ac.id',NULL,'$2y$12$JWC.8nXiEEl/FXzWzQEh9uqpAF.EKDz4OCCVcCqzHo/bCTmkbGIE2','admin',1,'Universitas Teknologi Indonesia',NULL,NULL,NULL,'2026-05-08 17:11:29','2026-05-08 17:11:29',NULL);
INSERT INTO `users` VALUES (3,'Dr. Siti Rahma','reviewer1@abdimu.ac.id',NULL,'$2y$12$mv6NJWyyHV6Mx0PIWcIcOO4loG1psTqiOzt09/.z6FCpt6SyAEr8i','reviewer',1,'Institut Teknologi Bandung',NULL,'Pakar di bidang Kecerdasan Buatan dan Machine Learning.',NULL,'2026-05-08 17:11:29','2026-05-08 17:11:29',NULL);
INSERT INTO `users` VALUES (4,'Prof. Budi Santoso','reviewer2@abdimu.ac.id',NULL,'$2y$12$ui2Dl6qdqZ.LpLjCqxDNo.PczV2J4g5kdlW3Kd6kyHt9C1/w4NIii','reviewer',1,'Universitas Gadjah Mada',NULL,'Peneliti senior di bidang Sistem Informasi.',NULL,'2026-05-08 17:11:29','2026-05-08 17:11:29',NULL);
INSERT INTO `users` VALUES (5,'Mustofa Fikri','author1@abdimu.ac.id',NULL,'$2y$12$RaK3DUfVcm5fR3uJ0sxm9O/hXI65aPTvXJqI0Z3ahnKGJ1AcZwmYu','author',1,'Universitas Hasanuddin',NULL,NULL,NULL,'2026-05-08 17:11:30','2026-05-08 17:11:30',NULL);
INSERT INTO `users` VALUES (6,'Dewi Lestari','author2@abdimu.ac.id',NULL,'$2y$12$L3V0KAyYAk3KS.odE80bS.6lT6nU.mPsSLkpkkKhU5hRkepWi7kW6','author',1,'Universitas Indonesia',NULL,NULL,NULL,'2026-05-08 17:11:30','2026-05-08 17:11:30',NULL);

CREATE INDEX `cache_expiration_index` ON `cache` (`expiration`);
CREATE INDEX `cache_locks_expiration_index` ON `cache_locks` (`expiration`);
CREATE UNIQUE INDEX `failed_jobs_uuid_unique` ON `failed_jobs` (`uuid`);
CREATE INDEX `jobs_queue_index` ON `jobs` (`queue`);
CREATE INDEX `personal_access_tokens_expires_at_index` ON `personal_access_tokens` (`expires_at`);
CREATE UNIQUE INDEX `personal_access_tokens_token_unique` ON `personal_access_tokens` (`token`);
CREATE INDEX `personal_access_tokens_tokenable_type_tokenable_id_index` ON `personal_access_tokens` (`tokenable_type`, `tokenable_id`);
CREATE INDEX `sessions_last_activity_index` ON `sessions` (`last_activity`);
CREATE INDEX `sessions_user_id_index` ON `sessions` (`user_id`);
CREATE UNIQUE INDEX `users_email_unique` ON `users` (`email`);

COMMIT;
SET FOREIGN_KEY_CHECKS = 1;
