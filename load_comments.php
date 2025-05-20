<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log errors to a file
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

if (!function_exists('loadComments')) {
    function loadComments($article_id) {
        error_log("Loading comments for article: $article_id");
        
        $filename = "comments/$article_id.txt";
        error_log("Looking for file: $filename");
        
        if (file_exists($filename)) {
            error_log("File exists, attempting to read");
            $comments = file_get_contents($filename);
            if ($comments === false) {
                error_log("Error reading file: $filename");
                return "Error loading comments";
            }
            error_log("Successfully loaded comments");
            return $comments; // Comments are already formatted with HTML
        }
        
        error_log("File does not exist: $filename");
        return "No comments yet";
    }
}
?> 