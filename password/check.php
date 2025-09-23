/**
 * Password Strength Evaluation API
 * 
 * This script evaluates password strength using multiple criteria:
 * - Length and character variety
 * - Entropy calculation
 * - Pattern detection (sequences, repetition, keyboard patterns)
 * - Dictionary/common password checking
 * - Crack time estimation
 * 
 * @author DevSecOps Team
 * @version 1.0
 */

<?php
require_once '../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$password = $_POST['password'] ?? '';

// Validate and sanitize input
if (empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Password is required']);
    exit;
}

// Additional input validation for security
$password = trim($password);
if (!is_string($password)) {
    echo json_encode(['success' => false, 'message' => 'Invalid password format']);
    exit;
}

if (strlen($password) > MAX_PASSWORD_LENGTH) {
    echo json_encode(['success' => false, 'message' => 'Password is too long']);
    exit;
}

try {
    $result = evaluatePasswordStrength($password);
    
    // Log the evaluation (optional, for analytics)
    if (isset($_SESSION['user_id'])) {
        logPasswordEvaluation($_SESSION['user_id'], $result);
    }
    
    echo json_encode(['success' => true, 'result' => $result]);
    
} catch (Exception $e) {
    logError('Password evaluation error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error evaluating password']);
}

/**
 * Evaluates password strength using comprehensive criteria
 * 
 * @param string $password The password to evaluate
 * @return array Detailed evaluation results including score, feedback, and suggestions
 */
function evaluatePasswordStrength($password) {
    $length = strlen($password);
    
    // Character type analysis
    $has_lowercase = preg_match('/[a-z]/', $password);
    $has_uppercase = preg_match('/[A-Z]/', $password);
    $has_digits = preg_match('/\d/', $password);
    $has_symbols = preg_match('/[^a-zA-Z0-9]/', $password);
    
    // Character set size for entropy calculation
    $charset_size = 0;
    if ($has_lowercase) $charset_size += 26;
    if ($has_uppercase) $charset_size += 26;
    if ($has_digits) $charset_size += 10;
    if ($has_symbols) $charset_size += 32; // Common symbols
    
    // Calculate entropy (bits of randomness)
    $entropy = $length * log($charset_size, 2);
    
    // Base score calculation
    $score = 0;
    
    // Length scoring (0-30 points)
    if ($length >= 8) $score += 10;
    if ($length >= 12) $score += 10;
    if ($length >= 16) $score += 5;
    if ($length >= 20) $score += 5;
    
    // Character variety (0-40 points)
    if ($has_lowercase) $score += 5;
    if ($has_uppercase) $score += 5;
    if ($has_digits) $score += 10;
    if ($has_symbols) $score += 20;
    
    // Pattern penalties
    $penalties = checkPatterns($password);
    $score -= $penalties;
    
    // Dictionary check
    $is_common = isDictionaryPassword($password);
    if ($is_common) {
        $score -= 30; // Heavy penalty for common passwords
    }
    
    // Ensure score is between 0 and 100
    $score = max(0, min(100, $score));
    
    // Determine strength level
    $strength_level = 'very-weak';
    if ($score >= 80) $strength_level = 'very-strong';
    elseif ($score >= 60) $strength_level = 'strong';
    elseif ($score >= 40) $strength_level = 'medium';
    elseif ($score >= 20) $strength_level = 'weak';
    
    // Generate feedback
    $feedback = generateFeedback($password, $has_lowercase, $has_uppercase, $has_digits, $has_symbols, $is_common, $length);
    $suggestions = generateSuggestions($password, $has_lowercase, $has_uppercase, $has_digits, $has_symbols, $is_common, $length);
    
    return [
        'score' => $score,
        'normalized_score' => $score / 100,
        'strength_level' => $strength_level,
        'entropy' => round($entropy, 2),
        'length' => $length,
        'character_types' => [
            'lowercase' => $has_lowercase,
            'uppercase' => $has_uppercase,
            'digits' => $has_digits,
            'symbols' => $has_symbols
        ],
        'is_common' => $is_common,
        'feedback' => $feedback,
        'suggestions' => $suggestions,
        'estimated_crack_time' => estimateCrackTime($entropy)
    ];
}

function checkPatterns($password) {
    $penalty = 0;
    
    // Sequential characters (123, abc, etc.)
    if (preg_match('/(?:012|123|234|345|456|567|678|789|890|abc|bcd|cde|def|efg|fgh|ghi|hij|ijk|jkl|klm|lmn|mno|nop|opq|pqr|qrs|rst|stu|tuv|uvw|vwx|wxy|xyz)/i', $password)) {
        $penalty += 10;
    }
    
    // Repeated characters (aaa, 111, etc.)
    if (preg_match('/(.)\1{2,}/', $password)) {
        $penalty += 15;
    }
    
    // Keyboard patterns (qwerty, asdf, etc.)
    $keyboard_patterns = ['qwerty', 'asdf', 'zxcv', '1234', 'qwertz', 'azerty'];
    foreach ($keyboard_patterns as $pattern) {
        if (stripos($password, $pattern) !== false) {
            $penalty += 20;
            break;
        }
    }
    
    // Simple patterns (password1, admin123, etc.)
    if (preg_match('/^(password|admin|user|test|guest|login)\d*$/i', $password)) {
        $penalty += 25;
    }
    
    return $penalty;
}

function isDictionaryPassword($password) {
    $db = Database::getInstance();
    
    // Check exact match first
    $result = $db->fetch("SELECT id FROM common_passwords WHERE password = ?", [strtolower($password)]);
    if ($result) {
        return true;
    }
    
    // Check common variations
    $variations = [
        strtolower($password),
        strtoupper($password),
        ucfirst(strtolower($password)),
        preg_replace('/[0-9]+$/', '', strtolower($password)), // Remove trailing numbers
        preg_replace('/[!@#$%^&*()]+$/', '', strtolower($password)) // Remove trailing symbols
    ];
    
    foreach ($variations as $variation) {
        if (strlen($variation) >= 4) {
            $result = $db->fetch("SELECT id FROM common_passwords WHERE password = ?", [$variation]);
            if ($result) {
                return true;
            }
        }
    }
    
    return false;
}

function generateFeedback($password, $has_lower, $has_upper, $has_digits, $has_symbols, $is_common, $length) {
    $feedback = [];
    
    if ($is_common) {
        $feedback[] = "WARNING: This password is commonly used and easily guessable";
    }
    
    if ($length < 8) {
        $feedback[] = "LENGTH: Password is too short (minimum 8 characters)";
    } elseif ($length < 12) {
        $feedback[] = "LENGTH: Consider using at least 12 characters for better security";
    }
    
    if (!$has_lower) $feedback[] = "CHARACTERS: Add lowercase letters (a-z)";
    if (!$has_upper) $feedback[] = "CHARACTERS: Add uppercase letters (A-Z)";
    if (!$has_digits) $feedback[] = "NUMBERS: Add numbers (0-9)";
    if (!$has_symbols) $feedback[] = "SYMBOLS: Add special characters (!@#$%^&*)";
    
    // Pattern-specific feedback
    if (preg_match('/(.)\1{2,}/', $password)) {
        $feedback[] = "PATTERN: Avoid repeating the same character multiple times";
    }
    
    if (preg_match('/(?:123|abc|qwerty)/i', $password)) {
        $feedback[] = "PATTERN: Avoid common sequences and keyboard patterns";
    }
    
    return $feedback;
}

function generateSuggestions($password, $has_lower, $has_upper, $has_digits, $has_symbols, $is_common, $length) {
    $suggestions = [];
    
    if ($is_common || $length < 12 || !$has_symbols) {
        // Generate a strong password suggestion
        $words = ['Ocean', 'Mountain', 'River', 'Forest', 'Desert', 'Valley', 'Sunset', 'Thunder', 'Lightning', 'Rainbow'];
        $symbols = ['!', '@', '#', '$', '%', '^', '&', '*'];
        $numbers = range(10, 99);
        
        $suggested = $words[array_rand($words)] . $numbers[array_rand($numbers)] . $symbols[array_rand($symbols)] . $words[array_rand($words)];
        $suggestions[] = "Try something like: " . $suggested;
    }
    
    $suggestions[] = "Use a passphrase: combine 4-6 random words with numbers and symbols";
    $suggestions[] = "Consider using a password manager to generate and store unique passwords";
    
    if ($length >= 8 && !$is_common) {
        $suggestions[] = "Your password has good basic security. Consider adding more characters for extra protection";
    }
    
    return $suggestions;
}

function estimateCrackTime($entropy) {
    // Estimates based on entropy bits
    // Assumes 1 billion guesses per second
    $guesses_per_second = 1e9;
    $possible_combinations = pow(2, $entropy);
    $seconds_to_crack = $possible_combinations / (2 * $guesses_per_second); // Average case
    
    if ($seconds_to_crack < 1) return 'Instantly';
    if ($seconds_to_crack < 60) return number_format($seconds_to_crack, 1) . ' seconds';
    if ($seconds_to_crack < 3600) return number_format($seconds_to_crack / 60, 1) . ' minutes';
    if ($seconds_to_crack < 86400) return number_format($seconds_to_crack / 3600, 1) . ' hours';
    if ($seconds_to_crack < 31536000) return number_format($seconds_to_crack / 86400, 1) . ' days';
    if ($seconds_to_crack < 31536000000) return number_format($seconds_to_crack / 31536000, 1) . ' years';
    
    return 'Centuries';
}

function logPasswordEvaluation($user_id, $result) {
    try {
        $db = Database::getInstance();
        
        $sql = "INSERT INTO password_evaluations 
                (user_id, password_length, strength_score, has_uppercase, has_lowercase, has_digits, has_symbols, strength_category, ip_address) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        // Determine strength category based on score
        $strength_category = 'very_weak';
        if ($result['normalized_score'] >= 0.8) {
            $strength_category = 'very_strong';
        } elseif ($result['normalized_score'] >= 0.6) {
            $strength_category = 'strong';
        } elseif ($result['normalized_score'] >= 0.4) {
            $strength_category = 'medium';
        } elseif ($result['normalized_score'] >= 0.2) {
            $strength_category = 'weak';
        }
        
        $db->query($sql, [
            $user_id,
            $result['length'],
            $result['normalized_score'],
            $result['character_types']['uppercase'] ? 1 : 0,
            $result['character_types']['lowercase'] ? 1 : 0,
            $result['character_types']['digits'] ? 1 : 0,
            $result['character_types']['symbols'] ? 1 : 0,
            $strength_category,
            getClientIP()
        ]);
    } catch (Exception $e) {
        logError('Failed to log password evaluation: ' . $e->getMessage());
    }
}
?>