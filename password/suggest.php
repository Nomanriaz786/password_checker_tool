<?php
require_once '../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$current_password = $_POST['current_password'] ?? '';
$requirements = $_POST['requirements'] ?? [];

// Validate and sanitize input
if (empty($current_password)) {
    echo json_encode(['success' => false, 'message' => 'Current password is required']);
    exit;
}

// Additional input validation for security
$current_password = trim($current_password);
if (!is_string($current_password)) {
    echo json_encode(['success' => false, 'message' => 'Invalid password format']);
    exit;
}

// Validate requirements array
if (!is_array($requirements)) {
    $requirements = [];
}

try {
    $suggestions = generatePasswordSuggestions($current_password, $requirements);
    echo json_encode(['success' => true, 'suggestions' => $suggestions]);
    
} catch (Exception $e) {
    logError('Password suggestion error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error generating suggestions']);
}

function generatePasswordSuggestions($password, $requirements = []) {
    $suggestions = [];
    
    // Analyze current password
    $length = strlen($password);
    $has_lower = preg_match('/[a-z]/', $password);
    $has_upper = preg_match('/[A-Z]/', $password);
    $has_digits = preg_match('/\d/', $password);
    $has_symbols = preg_match('/[^a-zA-Z0-9]/', $password);
    
    // Improvement suggestions based on current password
    if ($length >= 6) {
        // Improve existing password
        $improved = improvePassword($password, $has_lower, $has_upper, $has_digits, $has_symbols);
        if ($improved !== $password) {
            $suggestions[] = [
                'type' => 'improved',
                'password' => $improved,
                'description' => 'Enhanced version of your current password',
                'strength' => 'medium'
            ];
        }
    }
    
    // Generate completely new strong passwords
    $suggestions = array_merge($suggestions, generateNewPasswords());
    
    // Generate passphrases
    $suggestions = array_merge($suggestions, generatePassphrases());
    
    // Generate pattern-based passwords
    $suggestions = array_merge($suggestions, generatePatternPasswords());
    
    // Limit to 8 suggestions maximum
    return array_slice($suggestions, 0, 8);
}

function improvePassword($password, $has_lower, $has_upper, $has_digits, $has_symbols) {
    $improved = $password;
    
    // Add missing character types
    if (!$has_upper && $has_lower) {
        // Capitalize first letter or random position
        $pos = rand(0, strlen($improved) - 1);
        $improved[$pos] = strtoupper($improved[$pos]);
    }
    
    if (!$has_digits) {
        $improved .= rand(10, 99);
    }
    
    if (!$has_symbols) {
        $symbols = ['!', '@', '#', '$', '%', '&', '*'];
        $improved .= $symbols[array_rand($symbols)];
    }
    
    // Ensure minimum length
    while (strlen($improved) < 12) {
        $improved .= chr(rand(97, 122)); // Add lowercase letters
    }
    
    return $improved;
}

function generateNewPasswords() {
    $suggestions = [];
    
    // Word combinations with modifications
    $adjectives = ['Swift', 'Bright', 'Bold', 'Quick', 'Smart', 'Fresh', 'Cool', 'Warm', 'Sharp', 'Clear'];
    $nouns = ['Tiger', 'Eagle', 'River', 'Mountain', 'Ocean', 'Storm', 'Fire', 'Wind', 'Star', 'Moon'];
    $symbols = ['!', '@', '#', '$', '%', '^', '&', '*'];
    
    for ($i = 0; $i < 3; $i++) {
        $adj = $adjectives[array_rand($adjectives)];
        $noun = $nouns[array_rand($nouns)];
        $num = rand(100, 999);
        $symbol = $symbols[array_rand($symbols)];
        
        $password = $adj . $noun . $num . $symbol;
        
        $suggestions[] = [
            'type' => 'generated',
            'password' => $password,
            'description' => 'Strong combination of words, numbers, and symbols',
            'strength' => 'strong'
        ];
    }
    
    return $suggestions;
}

function generatePassphrases() {
    $suggestions = [];
    
    $words = [
        'coffee', 'sunset', 'guitar', 'travel', 'pizza', 'beach', 'music', 'dance', 'smile', 'dream',
        'forest', 'castle', 'rainbow', 'thunder', 'whisper', 'journey', 'mystery', 'treasure', 'magic', 'wonder'
    ];
    
    for ($i = 0; $i < 2; $i++) {
        $selected_words = array_rand($words, 4);
        $passphrase_words = [];
        
        foreach ($selected_words as $index) {
            $word = $words[$index];
            // Randomly capitalize
            if (rand(0, 1)) {
                $word = ucfirst($word);
            }
            $passphrase_words[] = $word;
        }
        
        $separator = ['-', '_', '.', '!'][array_rand(['-', '_', '.', '!'])];
        $number = rand(10, 99);
        
        $passphrase = implode($separator, $passphrase_words) . $separator . $number;
        
        $suggestions[] = [
            'type' => 'passphrase',
            'password' => $passphrase,
            'description' => 'Easy to remember passphrase with separators',
            'strength' => 'very-strong'
        ];
    }
    
    return $suggestions;
}

function generatePatternPasswords() {
    $suggestions = [];
    
    // Date-based patterns (but not obvious ones)
    $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    $years = range(1980, 2010);
    
    $month = $months[array_rand($months)];
    $year = $years[array_rand($years)];
    $day = rand(10, 28);
    
    $password = $month . $day . $year . '@Home!';
    
    $suggestions[] = [
        'type' => 'pattern',
        'password' => $password,
        'description' => 'Date-based pattern with location and symbols',
        'strength' => 'medium'
    ];
    
    // Acronym-based patterns
    $phrases = [
        'I Love To Code Every Day' => 'ILtCeD',
        'My Favorite Color Is Blue' => 'MFcIb',
        'Coffee Makes Me Happy Always' => 'CmMhA',
        'Reading Books Is My Passion' => 'RbImP'
    ];
    
    $phrase = array_rand($phrases);
    $acronym = $phrases[$phrase];
    $number = rand(100, 999);
    $symbol = ['!', '@', '#', '$'][array_rand(['!', '@', '#', '$'])];
    
    $password = $acronym . $number . $symbol;
    
    $suggestions[] = [
        'type' => 'acronym',
        'password' => $password,
        'description' => "Based on: \"$phrase\"",
        'strength' => 'strong'
    ];
    
    return $suggestions;
}
?>