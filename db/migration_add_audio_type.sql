ALTER TABLE media_attachments
MODIFY COLUMN media_type ENUM('image','video','audio','pdf','file') NOT NULL DEFAULT 'file';
