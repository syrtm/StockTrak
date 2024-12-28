-- Users tablosuna phone kolonu ekleme
ALTER TABLE users ADD COLUMN phone VARCHAR(20) AFTER email;
