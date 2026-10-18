<?php

return [
    // Operational defaults, adjustable to the actual PHP / cPanel limits.
    'max_file_kb' => (int) env('ARCHIVE_MAX_FILE_KB', 20480),
    'max_attachments' => (int) env('ARCHIVE_MAX_ATTACHMENTS', 20),
    'max_package_kb' => (int) env('ARCHIVE_MAX_PACKAGE_KB', 102400),
    'extensions' => ['pdf', 'docx', 'xlsx', 'pptx', 'jpg', 'jpeg', 'png'],
];
