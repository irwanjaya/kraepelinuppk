<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Initialize test data if not exists
if (!isset($_SESSION['test_data'])) {
    $_SESSION['test_data'] = generateTestData();
    $_SESSION['test_running'] = false;
    $_SESSION['participant_info'] = ['name' => '', 'nip' => ''];
}

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'start_test':
                $_SESSION['test_running'] = true;
                $_SESSION['participant_info'] = [
                    'name' => $_POST['participant_name'],
                    'nip' => $_POST['participant_nip']
                ];
                $_SESSION['test_start_time'] = time();
                break;
                
            case 'stop_test':
                $_SESSION['test_running'] = false;
                $_SESSION['test_end_time'] = time();
                // Save to database
                saveTestResults($_SESSION['participant_info'], $_SESSION['test_data'], $_SESSION['test_start_time'], $_SESSION['test_end_time']);
                break;
                
            case 'reset_test':
                $_SESSION['test_data'] = generateTestData();
                $_SESSION['test_running'] = false;
                $_SESSION['participant_info'] = ['name' => '', 'nip' => ''];
                unset($_SESSION['test_start_time']);
                unset($_SESSION['test_end_time']);
                break;
                
            case 'update_answer':
                $row = intval($_POST['row']);
                $col = intval($_POST['col']);
                $value = $_POST['value'];
                $_SESSION['test_data']['answers'][$row][$col] = $value;
                echo json_encode(['success' => true]);
                exit;
        }
    }
}

$testData = $_SESSION['test_data'];
$isRunning = $_SESSION['test_running'];
$participantInfo = $_SESSION['participant_info'];

// Calculate progress
$totalAnswers = 25 * 50;
$filledAnswers = 0;
foreach ($testData['answers'] as $row) {
    foreach ($row as $answer) {
        if (trim($answer) !== '') {
            $filledAnswers++;
        }
    }
}
$progressPercentage = ($filledAnswers / $totalAnswers) * 100;
?>
// Calculate detailed statistics
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


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tes Kraepelin - Aplikasi PHP</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto p-4">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-3xl font-bold text-gray-900">Tes Kraepelin</h1>
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-sm text-gray-600">
                            <?php echo $filledAnswers; ?>/<?php echo $totalAnswers; ?> (<?php echo round($progressPercentage); ?>%)
                        </span>
                    </div>
                </div>
            </div>

            <!-- Participant Information -->
            <form method="POST" id="participantForm">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label for="participant_name" class="block text-sm font-medium text-gray-700 mb-2">
                            Nama Peserta
                        </label>
                        <input
                            id="participant_name"
                            name="participant_name"
                            type="text"
                            value="<?php echo htmlspecialchars($participantInfo['name']); ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors"
                            placeholder="Masukkan nama lengkap"
                            <?php echo $isRunning ? 'disabled' : ''; ?>
                        />
                    </div>
                    <div>
                        <label for="participant_nip" class="block text-sm font-medium text-gray-700 mb-2">
                            NIP Peserta (18 digit)
                        </label>
                        <input
                            id="participant_nip"
                            name="participant_nip"
                            type="text"
                            value="<?php echo htmlspecialchars($participantInfo['nip']); ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors font-mono"
                            placeholder="123456789012345678"
                            maxlength="18"
                            pattern="[0-9]{18}"
                            <?php echo $isRunning ? 'disabled' : ''; ?>
                        />
                        <div class="text-xs text-gray-500 mt-1">
                            <span id="nip-length"><?php echo strlen($participantInfo['nip']); ?></span>/18 digit
                        </div>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                    <div 
                        class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                        style="width: <?php echo $progressPercentage; ?>%"
                    ></div>
                </div>

                <!-- Controls -->
                <div class="flex gap-3">
                    <?php if (!$isRunning): ?>
                        <button
                            type="submit"
                            name="action"
                            value="start_test"
                            id="startBtn"
                            class="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h1m4 0h1m6-10V4a2 2 0 00-2-2H5a2 2 0 00-2 2v16l3-2 3 2 3-2 3 2V4z"></path>
                            </svg>
                            Mulai Tes
                        </button>
                    <?php else: ?>
                        <div class="text-green-600 font-medium px-4 py-2 bg-green-50 rounded-lg border border-green-200">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Tes sedang berjalan
                            </div>
                        </div>
                        <button
                            type="submit"
                            name="action"
                            value="stop_test"
                            class="flex items-center gap-2 bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors"
                            onclick="return confirm('Apakah Anda yakin ingin menghentikan tes? Data akan disimpan secara otomatis.')"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10h6v4H9z"></path>
                            </svg>
                            Hentikan Tes
                        </button>
                    <?php endif; ?>
                    
                    <button
                        type="submit"
                        name="action"
                        value="reset_test"
                        class="flex items-center gap-2 bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors"
                        onclick="return confirm('Apakah Anda yakin ingin mereset tes?')"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Reset
                    </button>
                    
                    <a
                        href="export.php"
                        class="flex items-center gap-2 bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Export Excel
                    </a>
                </div>
            </form>
            
            <!-- Validation Message -->
            <?php if (!$isRunning && (empty(trim($participantInfo['name'])) || strlen($participantInfo['nip']) !== 18)): ?>
                <div class="mt-3 text-sm text-amber-600 bg-amber-50 border border-amber-200 rounded-lg p-3">
                    <strong>Perhatian:</strong> Lengkapi nama peserta dan NIP (18 digit) sebelum memulai tes.
                </div>
            <?php endif; ?>
        </div>

        <!-- Instructions -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <h3 class="font-semibold text-blue-900 mb-2">Petunjuk:</h3>
            <ul class="text-sm text-blue-800 space-y-1">
                <li>• Jumlahkan dua angka yang berurutan dalam setiap kolom</li>
                <li>• Masukkan hasil penjumlahan (maksimal 2 digit) di kolom jawaban</li>
                <li>• Pengisian dimulai dari bawah ke atas dalam setiap kolom</li>
                <li>• Anda bisa menjawab soal yang mana saja secara bebas</li>
                <li>• Gunakan Tab/Enter untuk pindah ke input berikutnya</li>
                <li>• Gunakan panah untuk navigasi manual (atas/bawah/kiri/kanan)</li>
                <li>• Kerjakan secepat dan seakurat mungkin</li>
                <li>• Klik "Hentikan Tes" untuk mengakhiri dan menyimpan hasil</li>
            </ul>
        </div>

        <!-- Test Grid -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <!-- Participant Statistics -->
            <?php if ($isRunning || (!empty($participantInfo['name']) && !empty($participantInfo['nip']))): ?>
                <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Statistik Peserta</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <span class="font-medium text-gray-700">Nama:</span>
                            <span class="ml-2 text-gray-900"><?php echo htmlspecialchars($participantInfo['name']) ?: '???'; ?></span>
                        </div>
                        <div>
                            <span class="font-medium text-gray-700">NIP:</span>
                            <span class="ml-2 text-gray-900 font-mono"><?php echo htmlspecialchars($participantInfo['nip']) ?: '???'; ?></span>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <span class="font-medium text-red-600">Jumlah kolom jawaban yang salah dari soal yang dijawab:</span>
                        <span class="ml-2 text-red-800 font-bold"><?php echo $totalWrongAnswers; ?></span>
                    </div>
                    
                    <!-- Column Statistics -->
                    <div class="border-t pt-4">
                        <h4 class="font-medium text-gray-700 mb-3">Statistik Per Kolom:</h4>
                        <div class="grid grid-cols-2 md:grid-cols-5 lg:grid-cols-10 gap-2 text-xs">
                            <?php for ($col = 0; $col < 50; $col++): ?>
                                <div class="bg-white p-2 rounded border">
                                    <div class="font-medium text-blue-600 mb-1">Kolom <?php echo $col + 1; ?></div>
                                    <div class="text-gray-600">
                                        Dijawab: <span class="font-medium"><?php echo $columnStats[$col]['answered']; ?></span>
                                    </div>
                                    <?php if ($columnStats[$col]['wrong'] > 0): ?>
                                        <div class="text-red-600">
                                            Salah: <span class="font-medium"><?php echo $columnStats[$col]['wrong']; ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
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
                                    <input
                                        type="text"
                                        maxlength="2"
                                        value="<?php echo htmlspecialchars($testData['answers'][$rowIndex][$colIndex]); ?>"
                                        class="answer-input w-8 h-8 text-center text-sm font-mono border-2 rounded transition-all <?php echo !empty($testData['answers'][$rowIndex][$colIndex]) ? 'border-green-300 bg-green-50' : 'border-gray-300 bg-white hover:border-blue-300'; ?> focus:border-blue-500 focus:bg-blue-50 focus:ring-2 focus:ring-blue-200 focus:outline-none"
                                        data-row="<?php echo $rowIndex; ?>"
                                        data-col="<?php echo $colIndex; ?>"
                                        <?php echo !$isRunning ? 'disabled' : ''; ?>
                                    />
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
        </div>

        <!-- Status Bar -->
        <div class="mt-6 bg-white rounded-lg shadow-sm p-4">
            <div class="flex items-center justify-between text-sm text-gray-600">
                <div>
                    Total kolom jawaban: <span class="font-mono font-medium">50 kolom × 25 baris = 1,250 jawaban</span>
                </div>
                <div>
                    Status: <span class="font-medium <?php echo !$isRunning ? 'text-gray-500' : 'text-green-600'; ?>">
                        <?php echo !$isRunning ? 'Belum dimulai' : 'Berjalan'; ?>
                    </span>
                </div>
                <div>
                    Jawaban terisi: <span class="font-medium text-blue-600"><?php echo $filledAnswers; ?></span>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
</body>
</html>