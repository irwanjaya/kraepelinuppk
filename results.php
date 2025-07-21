<?php
session_start();
require_once 'includes/functions.php';

// Get all test results
$results = getTestResults();

// Handle delete action
if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['session_id'])) {
    $sessionId = intval($_POST['session_id']);
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("DELETE FROM test_sessions WHERE id = ?");
        $stmt->execute([$sessionId]);
        header('Location: results.php?deleted=1');
        exit;
    } catch (PDOException $e) {
        $error = "Gagal menghapus data: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Tes Kraepelin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto p-4">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-3xl font-bold text-gray-900">Hasil Tes Kraepelin</h1>
                <a href="index.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    Kembali ke Tes
                </a>
            </div>
            
            <?php if (isset($_GET['deleted'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    Data berhasil dihapus.
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Results Table -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <?php if ($results && count($results) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Peserta
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Unit Kerja
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Tanggal Tes
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Durasi
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Progress
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Jawaban
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($results as $result): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($result['participant_name']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?php echo htmlspecialchars($result['participant_unit_kerja']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?php echo date('d/m/Y H:i', strtotime($result['start_time'])); ?>
                                        </div>
                                        <?php if ($result['end_time']): ?>
                                            <div class="text-xs text-gray-500">
                                                Selesai: <?php echo date('H:i', strtotime($result['end_time'])); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?php 
                                            if ($result['duration_seconds']) {
                                                $minutes = floor($result['duration_seconds'] / 60);
                                                $seconds = $result['duration_seconds'] % 60;
                                                echo sprintf('%d:%02d', $minutes, $seconds);
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                                <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $result['completion_percentage']; ?>%"></div>
                                            </div>
                                            <span class="text-sm text-gray-900"><?php echo round($result['completion_percentage'], 1); ?>%</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?php echo $result['filled_answers']; ?>/<?php echo $result['total_answers']; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <a href="view_result.php?id=<?php echo $result['id']; ?>" 
                                               class="text-blue-600 hover:text-blue-900">
                                                Lihat
                                            </a>
                                            <a href="export.php?session_id=<?php echo $result['id']; ?>" 
                                               class="text-green-600 hover:text-green-900">
                                                Export
                                            </a>
                                            <form method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus data ini?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="session_id" value="<?php echo $result['id']; ?>">
                                                <button type="submit" class="text-red-600 hover:text-red-900">
                                                    Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Belum ada hasil tes</h3>
                    <p class="mt-1 text-sm text-gray-500">Mulai tes pertama untuk melihat hasil di sini.</p>
                    <div class="mt-6">
                        <a href="index.php" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            Mulai Tes Baru
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>