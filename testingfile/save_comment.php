<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log errors to a file
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// Include banned words list
require_once 'banned_words.php';

// Function to check for banned words
function containsBannedWords($text) {
    global $banned_words;
    $text = strtolower($text);
    foreach ($banned_words as $word) {
        if (strpos($text, strtolower($word)) !== false) {
            return true;
        }
    }
    return false;
}

// Function to verify reCAPTCHA v3
function verifyRecaptcha($recaptcha_response) {
    $secret_key = "6LeY0EArAAAAAMxGAJfm7WaGE9BlUw_Zg0PzvEcx";
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = array(
        'secret' => $secret_key,
        'response' => $recaptcha_response,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    );

    $options = array(
        'http' => array(
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        )
    );

    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    $result = json_decode($response);

    // For reCAPTCHA v3, we check the score (0.0 to 1.0)
    // You can adjust this threshold based on your needs
    return $result->success && $result->score >= 0.5;
}

// Get the article ID from the URL
$article_id = isset($_GET['article']) ? $_GET['article'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the comment data
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $recaptcha_response = isset($_POST['g-recaptcha-response']) ? $_POST['g-recaptcha-response'] : '';

    // Verify reCAPTCHA
    if (!verifyRecaptcha($recaptcha_response)) {
        echo json_encode(['success' => false, 'message' => 'reCAPTCHA verification failed']);
        exit;
    }

    // Check for banned words in username
    if (containsBannedWords($username) || empty($username)) {
        $username = "Anonymous";
    }

    // Check for banned words in comment
    if (containsBannedWords($comment)) {
        echo json_encode(['success' => false, 'message' => 'Your comment contains inappropriate language']);
        exit;
    }

    // Validate comment
    if (empty($comment)) {
        echo json_encode(['success' => false, 'message' => 'Comment cannot be empty']);
        exit;
    }

    // Create comments directory if it doesn't exist
    if (!file_exists('comments')) {
        mkdir('comments', 0777, true);
    }

    // Format the comment with username and timestamp
    $timestamp = date('Y-m-d H:i:s');
    $formatted_comment = "<div class='comment'><strong>$username</strong> - $timestamp<br>$comment</div>\n";

    // Save the comment
    $filename = "comments/$article_id.txt";
    if (file_put_contents($filename, $formatted_comment, FILE_APPEND)) {
        echo json_encode(['success' => true, 'message' => 'Comment saved successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error saving comment']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?> 