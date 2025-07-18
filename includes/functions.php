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
 * Save test results to database
 */
function saveTestResults($participantInfo, $testData, $startTime, $endTime) {
    try {
        $pdo = getConnection();
        
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
        
        // Insert test session
        $stmt = $pdo->prepare("
            INSERT INTO test_sessions 
            (participant_name, participant_nip, start_time, end_time, duration_seconds, total_answers, filled_answers, completion_percentage) 
            VALUES (?, ?, FROM_UNIXTIME(?), FROM_UNIXTIME(?), ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $participantInfo['name'],
            $participantInfo['nip'],
            $startTime,
            $endTime,
            $duration,
            $totalAnswers,
            $filledAnswers,
            $completionPercentage
        ]);
        
        $sessionId = $pdo->lastInsertId();
        
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
                'participant_nip' => $_SESSION['participant_info']['nip']
            ];
        }
        
        // Create CSV content
        $csvContent = "Tes Kraepelin - Hasil\n";
        $csvContent .= "Nama: " . $session['participant_name'] . "\n";
        $csvContent .= "NIP: " . $session['participant_nip'] . "\n";
        $csvContent .= "Tanggal: " . date('Y-m-d H:i:s') . "\n\n";
        
        // Calculate detailed statistics for export
        $columnStats = [];
        $totalWrongAnswers = 0;
        
        for ($col = 0; $col < 50; $col++) {
            $answeredInColumn = 0;
            $wrongInColumn = 0;
            
            for ($row = 0; $row < 24; $row++) { // Only check rows 0-23 (24 rows) since row 24 has no next row to compare
                $answer = trim($testData['answers'][$row][$col]);
                if ($answer !== '') {
                    $answeredInColumn++;
                    
                    // Check if answer is correct
                    $expectedAnswer = $testData['numbers'][$row][$col] + $testData['numbers'][$row + 1][$col];
                    if (intval($answer) !== $expectedAnswer) {
                        $wrongInColumn++;
                        $totalWrongAnswers++;
                    }
                }
            }
            
            $columnStats[$col] = [
                'answered' => $answeredInColumn,
                'wrong' => $wrongInColumn
            ];
        }
        
        // Add statistics to CSV
        $csvContent .= "STATISTIK PESERTA\n";
        $csvContent .= "Jumlah kolom jawaban yang salah dari soal yang dijawab: " . $totalWrongAnswers . "\n\n";
        
        // Add column statistics
        $csvContent .= "STATISTIK PER KOLOM\n";
        $csvContent .= "Kolom,Jumlah Baris Dijawab,Jumlah Jawaban Salah\n";
        for ($col = 0; $col < 50; $col++) {
            $csvContent .= "Kolom " . ($col + 1) . "," . $columnStats[$col]['answered'] . "," . $columnStats[$col]['wrong'] . "\n";
        }
        $csvContent .= "\n";
        
        // Add questions header
        $csvContent .= "SOAL\n";
        $header = "";
        for ($col = 0; $col < 50; $col++) {
            $header .= "Kolom " . ($col + 1) . ",";
        }
        $csvContent .= rtrim($header, ',') . "\n";
        
        // Add questions data
        $testData = $sessionId ? getSessionTestData($sessionId) : $_SESSION['test_data'];
        for ($row = 0; $row < 25; $row++) {
            $rowData = "";
            for ($col = 0; $col < 50; $col++) {
                $rowData .= $testData['numbers'][$row][$col] . ",";
            }
            $csvContent .= rtrim($rowData, ',') . "\n";
        }
        
        // Add answers header
        $csvContent .= "\nJAWABAN\n";
        $csvContent .= "Baris," . rtrim($header, ',') . "\n";
        
        // Add answers data
        for ($row = 0; $row < 25; $row++) {
            $rowData = ($row + 1) . ",";
            for ($col = 0; $col < 50; $col++) {
                $answer = isset($testData['answers'][$row][$col]) ? $testData['answers'][$row][$col] : '';
                $rowData .= $answer . ",";
            }
            $csvContent .= rtrim($rowData, ',') . "\n";
        }
        
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