import re
import os

sql_path = '/home/mustofa/Music/project/pengelola_jurnal/backend/database/abdimu.sql'
output_path = '/home/mustofa/Music/project/pengelola_jurnal/backend/database/ABDIMU_SIAP_IMPOR_MYSQL.sql'

with open(sql_path, 'r') as f:
    content = f.read()

# Step 1: Header and Transaction fixes
content = content.replace('BEGIN TRANSACTION;', 'SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\nSTART TRANSACTION;')

# Step 2: Convert double quotes to backticks (MySQL compliant)
content = re.sub(r'"([a-zA-Z0-9_]+)"', r'`\1`', content)

# Step 3: Handle AUTOINCREMENT Syntax differences
content = re.sub(r'PRIMARY KEY\(`id`\s+AUTOINCREMENT\)', 'PRIMARY KEY (`id`)', content)
content = re.sub(r'PRIMARY KEY\(`id` AUTOINCREMENT\)', 'PRIMARY KEY (`id`)', content)

# Step 4: Handle datatypes
# Map `id` declarations to auto_incrementing bigints
content = re.sub(r'`id`(\s+)integer NOT NULL,', r'`id`\1bigint(20) unsigned NOT NULL AUTO_INCREMENT,', content)
content = content.replace('`id`\tinteger NOT NULL,', '`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,')

# IMPORTANT FIX FOR MYSQL FOREIGN KEYS:
# Any column that is an ID field (e.g. `user_id`, `paper_id`) MUST match the parent type (bigint unsigned)
content = re.sub(r'`([a-zA-Z0-9_]+_id)`\s+int\(11\)', r'`\1` bigint(20) unsigned', content)
content = re.sub(r'`([a-zA-Z0-9_]+_id)`\s+integer', r'`\1` bigint(20) unsigned', content)

# Map standalone `integer` to standard `int(11)` or just `int`
content = re.sub(r'\binteger\b', 'int(11)', content)

# Map `varchar` (without length in SQLite) to `varchar(255)`
content = re.sub(r'\bvarchar\b', 'varchar(255)', content)

# Fix boolean type for TinyInt default
content = content.replace("NOT NULL DEFAULT '0'", "NOT NULL DEFAULT 0")
content = content.replace("NOT NULL DEFAULT '1'", "NOT NULL DEFAULT 1")

# Step 5: Remove sqlite check constraints and autoindexes
content = re.sub(r' CHECK\(`.*?` IN \(.*?\)\)', '', content)

# Step 6: Footer and foreign keys enable
content = content.replace('COMMIT;', 'COMMIT;\nSET FOREIGN_KEY_CHECKS = 1;')

# Clean up any remaining raw references
content = content.replace('DEFAULT CURRENT_TIMESTAMP', 'DEFAULT CURRENT_TIMESTAMP()')

with open(output_path, 'w') as f:
    f.write(content)

print(f"SUCCESS! Created file at {output_path}")
