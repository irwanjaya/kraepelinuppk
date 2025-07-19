<?php
session_start();
require_once 'includes/functions.php';

// Get session ID from URL
$sessionId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$sessionId) {
    header('Location: results.php');
    exit;
}

// Get session data
$session = getTestResults($sessionId);
if (!$session) {
    header('Location: results.php');
    exit;
}

// Get test data
$testData = getSessionTestData($sessionId);
if (!$testData) {
    $error = "Data tes tidak ditemukan.";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Hasil Tes - <?php echo htmlspecialchars($session['participant_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto p-4">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-3xl font-bold text-gray-900">Detail Hasil Tes</h1>
                <div class="flex gap-3">
                    <a href="export.php?session_id=<?php echo $sessionId; ?>" 
                       class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                        Export Excel
                    </a>
                    <a href="results.php" 
                       class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                        Kembali
                    </a>
                </div>
            </div>
            
            <!-- Session Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-blue-50 p-4 rounded-lg">
                    <h3 class="font-semibold text-blue-900 mb-2">Peserta</h3>
                    <p class="text-blue-800"><?php echo htmlspecialchars($session['participant_name']); ?></p>
                    <p class="text-sm text-blue-600 font-mono"><?php echo htmlspecialchars($session['participant_nip']); ?></p>
                </div>
                
                <div class="bg-green-50 p-4 rounded-lg">
                    <h3 class="font-semibold text-green-900 mb-2">Waktu Tes</h3>
                    <p class="text-green-800"><?php echo date('d/m/Y H:i', strtotime($session['start_time'])); ?></p>
                    <?php if ($session['end_time']): ?>
                        <p class="text-sm text-green-600">Selesai: <?php echo date('H:i', strtotime($session['end_time'])); ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="bg-yellow-50 p-4 rounded-lg">
                    <h3 class="font-semibold text-yellow-900 mb-2">Durasi</h3>
                    <p class="text-yellow-800">
                        <?php 
                        if ($session['duration_seconds']) {
                            $minutes = floor($session['duration_seconds'] / 60);
                            $seconds = $session['duration_seconds'] % 60;
                            echo sprintf('%d menit %d detik', $minutes, $seconds);
                        } else {
                            echo 'Tidak selesai';
                        }
                        ?>
                    </p>
                </div>
                
                <div class="bg-purple-50 p-4 rounded-lg">
                    <h3 class="font-semibold text-purple-900 mb-2">Progress</h3>
                    <p class="text-purple-800"><?php echo round($session['completion_percentage'], 1); ?>%</p>
                    <p class="text-sm text-purple-600"><?php echo $session['filled_answers']; ?>/<?php echo $session['total_answers']; ?> jawaban</p>
                </div>
            </div>
            
            <!-- Progress Bar -->
            <div class="w-full bg-gray-200 rounded-full h-3">
                <div class="bg-blue-600 h-3 rounded-full" style="width: <?php echo $session['completion_percentage']; ?>%"></div>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php elseif ($testData): ?>
            <!-- Test Grid -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Data Tes</h2>
                
                <div class="overflow-x-auto">
                    <div class="min-w-max">
                        <!-- Number Grid - 25 rows -->
                        <?php for ($rowIndex = 0; $rowIndex < 25; $rowIndex++): ?>
                            <div class="flex gap-1 mb-1">
                                <?php for ($colIndex = 0; $colIndex < 50; $colIndex++): ?>
                                    <div class="w-8 h-8 flex items-center justify-center text-lg font-mono border border-gray-200 bg-gray-50">
                                        <?php echo $testData['numbers'][$rowIndex][$colIndex]; ?>
                                    </div>
                                    <div class="w-8 h-8 flex items-center justify-center">
                                        <div class="w-8 h-8 text-center text-sm font-mono border-2 rounded flex items-center justify-center <?php 
                                            $answer = $testData['answers'][$rowIndex][$colIndex];
                                            if (!empty($answer)) {
                                                // Check if answer is correct
                                                $isCorrect = false;
                                                if ($rowIndex < 24) {
                                                    $expectedAnswer = $testData['numbers'][$rowIndex][$colIndex] + $testData['numbers'][$rowIndex + 1][$colIndex];
                                                    $isCorrect = (intval($answer) === $expectedAnswer);
                                                }
                                                echo $isCorrect ? 'border-green-500 bg-green-100 text-green-800' : 'border-red-500 bg-red-100 text-red-800';
                                            } else {
                                                echo 'border-gray-300 bg-gray-50';
                                            }
                                        ?>">
                                            <?php echo htmlspecialchars($answer); ?>
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        <?php endfor; ?>

                        <!-- Column Footers -->
                        <div class="flex gap-1 mt-2">
                            <?php for ($colIndex = 0; $colIndex < 50; $colIndex++): ?>
                                <div class="w-8 text-center">
                                    <div class="text-xs font-medium text-gray-500">
                                        <?php echo $colIndex + 1; ?>
                                    </div>
                                </div>
                                <div class="w-8 text-center">
                                    <div class="text-xs font-medium text-blue-600">
                                        J<?php echo $colIndex + 1; ?>
                                    </div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Legend -->
                <div class="mt-6 flex items-center gap-6 text-sm">
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 border-2 border-green-500 bg-green-100 rounded"></div>
                        <span>Jawaban Benar</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 border-2 border-red-500 bg-red-100 rounded"></div>
                        <span>Jawaban Salah</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 border-2 border-gray-300 bg-gray-50 rounded"></div>
                        <span>Tidak Dijawab</span>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>