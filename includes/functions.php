<?php
require_once 'config/database.php';

/**
 * Generate random test data
 */
function generateTestData() {
    $numbers = [];
    $answers = [];
    
    // Generate 25 rows of 50 numbers each
    for ($row = 0; $row < 25; $row++) {
        $rowNumbers = [];
        for ($col = 0; $col < 50; $col++) {
            $rowNumbers[] = rand(0, 9);
        }
        $numbers[] = $rowNumbers;
        
        // Initialize empty answers
        $answers[] = array_fill(0, 50, '');
    }
    
    return [
        'numbers' => $numbers,
        'answers' => $answers
    ];
}

/**
 * Register new participant
 */
function registerParticipant($name, $unitKerja, $username, $password) {
    try {
        $pdo = getConnection();
        
        // Check if username already exists
        $stmt = $pdo->prepare("SELECT id FROM test_sessions WHERE participant_username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Username sudah digunakan. Silakan pilih username lain.'];
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Create initial session record
        $stmt = $pdo->prepare("
            INSERT INTO test_sessions 
            (participant_name, participant_unit_kerja, participant_username, participant_password, start_time, total_answers) 
            VALUES (?, ?, ?, ?, NOW(), 1250)
        ");
        
        $stmt->execute([$name, $unitKerja, $username, $hashedPassword]);
        $sessionId = $pdo->lastInsertId();
        
        return ['success' => true, 'session_id' => $sessionId];
    } catch (PDOException $e) {
        error_log("Error registering participant: " . $e->getMessage());
        return ['success' => false, 'message' => 'Terjadi kesalahan saat mendaftar. Silakan coba lagi.'];
    }
}

/**
 * Login participant
 */
function loginParticipant($username, $password) {
    try {
        $pdo = getConnection();
        
        $stmt = $pdo->prepare("SELECT * FROM test_sessions WHERE participant_username = ?");
        $stmt->execute([$username]);
        $participant = $stmt->fetch();
        
        if (!$participant) {
            return ['success' => false, 'message' => 'Username tidak ditemukan.'];
        }
        
        if (!password_verify($password, $participant['participant_password'])) {
            return ['success' => false, 'message' => 'Password salah.'];
        }
        
        return ['success' => true, 'participant' => $participant];
    } catch (PDOException $e) {
        error_log("Error logging in participant: " . $e->getMessage());
        return ['success' => false, 'message' => 'Terjadi kesalahan saat login. Silakan coba lagi.'];
    }
}

/**
 * Save test results to database
 */
function saveTestResults($participantInfo, $testData, $startTime, $endTime) {
    try {
        $pdo = getConnection();
        
        // Get session ID from participant info
        $sessionId = $participantInfo['session_id'];
        
        // Calculate statistics
        $duration = $endTime - $startTime;
        $totalAnswers = 25 * 50;
        $filledAnswers = 0;
        
        foreach ($testData['answers'] as $row) {
            foreach ($row as $answer) {
                if (trim($answer) !== '') {
                    $filledAnswers++;
                }
            }
        }
        
        $completionPercentage = ($filledAnswers / $totalAnswers) * 100;
        
        // Update existing test session
        $stmt = $pdo->prepare("
            UPDATE test_sessions 
            SET end_time = FROM_UNIXTIME(?), duration_seconds = ?, filled_answers = ?, completion_percentage = ?
            WHERE id = ?
        ");
        
        $stmt->execute([$endTime, $duration, $filledAnswers, $completionPercentage, $sessionId]);
        
        // Insert questions and answers
        $questionStmt = $pdo->prepare("
            INSERT INTO test_questions (session_id, row_index, col_index, question_number) 
            VALUES (?, ?, ?, ?)
        ");
        
        $answerStmt = $pdo->prepare("
            INSERT INTO test_answers (session_id, row_index, col_index, answer_value, is_correct) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        for ($row = 0; $row < 25; $row++) {
            for ($col = 0; $col < 50; $col++) {
                $questionNumber = $testData['numbers'][$row][$col];
                $answer = $testData['answers'][$row][$col];
                
                // Insert question
                $questionStmt->execute([$sessionId, $row, $col, $questionNumber]);
                
                // Insert answer if provided
                if (trim($answer) !== '') {
                    // Calculate if answer is correct (sum of current and next number)
                    $isCorrect = false;
                    if ($row < 24) { // Not the last row
                        $expectedAnswer = $testData['numbers'][$row][$col] + $testData['numbers'][$row + 1][$col];
                        $isCorrect = (intval($answer) === $expectedAnswer);
                    }
                    
                    $answerStmt->execute([$sessionId, $row, $col, $answer, $isCorrect]);
                }
            }
        }
        
        return $sessionId;
    } catch (PDOException $e) {
        error_log("Error saving test results: " . $e->getMessage());
        return false;
    }
}

/**
 * Get test results from database
 */
function getTestResults($sessionId = null) {
    try {
        $pdo = getConnection();
        
        if ($sessionId) {
            $stmt = $pdo->prepare("SELECT * FROM test_sessions WHERE id = ?");
            $stmt->execute([$sessionId]);
            return $stmt->fetch();
        } else {
            $stmt = $pdo->query("SELECT * FROM test_sessions ORDER BY created_at DESC");
            return $stmt->fetchAll();
        }
    } catch (PDOException $e) {
        error_log("Error getting test results: " . $e->getMessage());
        return false;
    }
}

/**
 * Export test data to Excel format
 */
function exportToExcel($sessionId = null) {
    try {
        $pdo = getConnection();
        
        // Get session data
        if ($sessionId) {
            $session = getTestResults($sessionId);
            if (!$session) {
                return false;
            }
        } else {
            // Use current session data
            if (!isset($_SESSION['test_data']) || !isset($_SESSION['participant_info'])) {
                return false;
            }
            $session = [
                'participant_name' => $_SESSION['participant_info']['name'],
               'participant_unit_kerja' => $_SESSION['participant_info']['unit_kerja']
            ];
        }
        
        // Create CSV content
        $csvContent = "Tes Kraepelin - Hasil\n";
        $csvContent .= "Nama: " . $session['participant_name'] . "\n";
       $csvContent .= "Unit Kerja: " . $session['participant_unit_kerja'] . "\n";
        $csvContent .= "Tanggal: " . date('Y-m-d H:i:s') . "\n\n";
        
        // Get test data
        $testData = $sessionId ? getSessionTestData($sessionId) : $_SESSION['test_data'];
        
        // Calculate filled rows per column
        for ($col = 0; $col < 50; $col++) {
            $filledRows = 0;
            
            for ($row = 0; $row < 25; $row++) {
                $answer = trim($testData['answers'][$row][$col]);
                if ($answer !== '') {
                    $filledRows++;
                }
            }
            
            $csvContent .= "Banyaknya baris yang diisi pada kolom " . ($col + 1) . ": " . $filledRows . "\n";
        }
        
        // Calculate total wrong answers
        $totalWrongAnswers = 0;
        
        for ($col = 0; $col < 50; $col++) {
            for ($row = 0; $row < 24; $row++) { // Only check rows 0-23 since row 24 has no next row to compare
                $answer = trim($testData['answers'][$row][$col]);
                if ($answer !== '') {
                    // Check if answer is correct
                    $expectedAnswer = $testData['numbers'][$row][$col] + $testData['numbers'][$row + 1][$col];
                    if (intval($answer) !== $expectedAnswer) {
                        $totalWrongAnswers++;
                    }
                }
            }
        }
        
        // Add total wrong answers with the requested format
        $csvContent .= "Banyaknya kolom yang salah dari jawaban yang diisi: " . $totalWrongAnswers . "\n";
        
        return $csvContent;
    } catch (Exception $e) {
        error_log("Error exporting to Excel: " . $e->getMessage());
        return false;
    }
}

/**
 * Get session test data from database
 */
function getSessionTestData($sessionId) {
    try {
        $pdo = getConnection();
        
        // Get questions
        $stmt = $pdo->prepare("SELECT * FROM test_questions WHERE session_id = ? ORDER BY row_index, col_index");
        $stmt->execute([$sessionId]);
        $questions = $stmt->fetchAll();
        
        // Get answers
        $stmt = $pdo->prepare("SELECT * FROM test_answers WHERE session_id = ? ORDER BY row_index, col_index");
        $stmt->execute([$sessionId]);
        $answers = $stmt->fetchAll();
        
        // Reconstruct data arrays
        $numbers = array_fill(0, 25, array_fill(0, 50, 0));
        $answerArray = array_fill(0, 25, array_fill(0, 50, ''));
        
        foreach ($questions as $question) {
            $numbers[$question['row_index']][$question['col_index']] = $question['question_number'];
        }
        
        foreach ($answers as $answer) {
            $answerArray[$answer['row_index']][$answer['col_index']] = $answer['answer_value'];
        }
        
        return [
            'numbers' => $numbers,
            'answers' => $answerArray
        ];
    } catch (PDOException $e) {
        error_log("Error getting session test data: " . $e->getMessage());
        return false;
    }
}
?>