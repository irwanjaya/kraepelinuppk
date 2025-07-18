import React, { useState, useEffect, useRef } from 'react';
import { Play, RotateCcw, CheckCircle2 } from 'lucide-react';

interface TestData {
  numbers: number[][];
  answers: string[][];
}

interface ParticipantInfo {
  name: string;
  nip: string;
}

function App() {
  const [testData, setTestData] = useState<TestData>({ numbers: [], answers: [] });
  const [isRunning, setIsRunning] = useState(false);
  const [participantInfo, setParticipantInfo] = useState<ParticipantInfo>({ name: '', nip: '' });
  const inputRefs = useRef<(HTMLInputElement | null)[][]>([]);

  // Generate random test data
  const generateTestData = () => {
    const numbers: number[][] = [];
    for (let row = 0; row < 25; row++) {
      const rowNumbers: number[] = [];
      for (let col = 0; col < 50; col++) {
        rowNumbers.push(Math.floor(Math.random() * 10));
      }
      numbers.push(rowNumbers);
    }
    
    // Initialize answers array - 24 rows (excluding last row) x 50 columns
    const answers: string[][] = [];
    for (let row = 0; row < 25; row++) {
      answers.push(new Array(50).fill(''));
    }
    
    setTestData({
      numbers,
      answers
    });
    setIsRunning(false);
  };

  // Initialize test data on component mount
  useEffect(() => {
    generateTestData();
  }, []);

  // Handle answer input change
  const handleAnswerChange = (rowIndex: number, columnIndex: number, value: string) => {
    // Only allow numeric input up to 2 digits
    const numericValue = value.replace(/\D/g, '').slice(0, 2);
    
    const newAnswers = testData.answers.map(row => [...row]);
    newAnswers[rowIndex][columnIndex] = numericValue;
    setTestData(prev => ({ ...prev, answers: newAnswers }));
  };

  // Handle participant info change
  const handleParticipantInfoChange = (field: keyof ParticipantInfo, value: string) => {
    if (field === 'nip') {
      // Only allow numeric input up to 18 digits for NIP
      const numericValue = value.replace(/\D/g, '').slice(0, 18);
      setParticipantInfo(prev => ({ ...prev, [field]: numericValue }));
    } else {
      setParticipantInfo(prev => ({ ...prev, [field]: value }));
    }
  };

  // Handle key navigation
  const handleKeyDown = (e: React.KeyboardEvent, rowIndex: number, columnIndex: number) => {
    if (e.key === 'Enter' || e.key === 'Tab') {
      e.preventDefault();
      moveToNextInput(rowIndex, columnIndex);
    } else if (e.key === 'ArrowLeft' && columnIndex > 0) {
      e.preventDefault();
      focusInput(rowIndex, columnIndex - 1);
    } else if (e.key === 'ArrowRight' && columnIndex < 49) {
      e.preventDefault();
      focusInput(rowIndex, columnIndex + 1);
    } else if (e.key === 'ArrowUp' && rowIndex > 0) {
      e.preventDefault();
      focusInput(rowIndex - 1, columnIndex);
    } else if (e.key === 'ArrowDown' && rowIndex < 24) {
      e.preventDefault();
      focusInput(rowIndex + 1, columnIndex);
    }
  };

  // Navigation functions
  const moveToNextInput = (currentRow: number, currentCol: number) => {
    // Move to next available input (bottom to top, left to right)
    if (currentRow < 24) {
      // Move down in the same column
      focusInput(currentRow + 1, currentCol);
    } else if (currentCol < 49) {
      // Move to top of next column
      focusInput(0, currentCol + 1);
    }
  };

  const focusInput = (rowIndex: number, columnIndex: number) => {
    setTimeout(() => {
      if (inputRefs.current[rowIndex] && inputRefs.current[rowIndex][columnIndex]) {
        inputRefs.current[rowIndex][columnIndex]?.focus();
      }
    }, 50);
  };

  // Initialize input refs
  useEffect(() => {
    inputRefs.current = Array(25).fill(null).map(() => Array(50).fill(null));
  }, []);

  const moveToNextColumn = () => {
    // Legacy function - now focuses on first input
    focusInput(0, 0);
  };

  const moveToPreviousColumn = () => {
    // Legacy function - now focuses on first input
    focusInput(0, 0);
  };

  // Start test
  const startTest = () => {
    setIsRunning(true);
    setTimeout(() => {
      // Focus on bottom-left input (row 24, column 0)
      focusInput(24, 0);
    }, 100);
  };


  // Reset test
  const resetTest = () => {
    generateTestData();
  };

  // Calculate progress
  const totalAnswers = 25 * 50; // 25 rows of answers
  const filledAnswers = testData.answers.flat().filter(answer => answer.trim() !== '').length;
  const progressPercentage = (filledAnswers / totalAnswers) * 100;

  // Export to Excel
  const exportToExcel = () => {
    const XLSX = require('xlsx');
    
    // Create workbook
    const workbook = XLSX.utils.book_new();
    
    // Create questions worksheet
    const questionsData = testData.numbers.map((row, rowIndex) => {
      const rowData: any = {};
      row.forEach((num, colIndex) => {
        rowData[`Kolom_${colIndex + 1}`] = num;
      });
      return rowData;
    });
    
    const questionsWorksheet = XLSX.utils.json_to_sheet(questionsData);
    XLSX.utils.book_append_sheet(workbook, questionsWorksheet, 'Soal');
    
    // Create answers worksheet
    const answersData = testData.answers.map((row, rowIndex) => {
      const rowData: any = { Baris: rowIndex + 1 };
      row.forEach((answer, colIndex) => {
        rowData[`Kolom_${colIndex + 1}`] = answer || '';
      });
      return rowData;
    });
    
    const answersWorksheet = XLSX.utils.json_to_sheet(answersData);
    XLSX.utils.book_append_sheet(workbook, answersWorksheet, 'Jawaban');
    
    // Generate filename with current date and time
    const now = new Date();
    const timestamp = now.toISOString().slice(0, 19).replace(/[:.]/g, '-');
    const filename = `kraepelin-test-${timestamp}.xlsx`;
    
    // Save file
    XLSX.writeFile(workbook, filename);
  };

  return (
    <div className="min-h-screen bg-gray-50 p-4">
      <div className="max-w-7xl mx-auto">
        {/* Header */}
        <div className="bg-white rounded-lg shadow-sm p-6 mb-6">
          <div className="flex items-center justify-between mb-4">
            <h1 className="text-3xl font-bold text-gray-900">Tes Kraepelin</h1>
            <div className="flex items-center gap-4">
              <div className="flex items-center gap-2">
                <CheckCircle2 className="w-5 h-5 text-green-600" />
                <span className="text-sm text-gray-600">
                  {filledAnswers}/{totalAnswers} ({Math.round(progressPercentage)}%)
                </span>
              </div>
            </div>
          </div>

          {/* Participant Information */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
              <label htmlFor="participant-name" className="block text-sm font-medium text-gray-700 mb-2">
                Nama Peserta
              </label>
              <input
                id="participant-name"
                type="text"
                value={participantInfo.name}
                onChange={(e) => handleParticipantInfoChange('name', e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors"
                placeholder="Masukkan nama lengkap"
                disabled={isRunning}
              />
            </div>
            <div>
              <label htmlFor="participant-nip" className="block text-sm font-medium text-gray-700 mb-2">
                NIP Peserta (18 digit)
              </label>
              <input
                id="participant-nip"
                type="text"
                value={participantInfo.nip}
                onChange={(e) => handleParticipantInfoChange('nip', e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors font-mono"
                placeholder="123456789012345678"
                maxLength={18}
                disabled={isRunning}
              />
              <div className="text-xs text-gray-500 mt-1">
                {participantInfo.nip.length}/18 digit
              </div>
            </div>
          </div>

          {/* Progress Bar */}
          <div className="w-full bg-gray-200 rounded-full h-2 mb-4">
            <div 
              className="bg-blue-600 h-2 rounded-full transition-all duration-300"
              style={{ width: `${progressPercentage}%` }}
            ></div>
          </div>

          {/* Controls */}
          <div className="flex gap-3">
            {!isRunning ? (
              <button
                onClick={startTest}
                disabled={!participantInfo.name.trim() || participantInfo.nip.length !== 18}
                className="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors"
              >
                <Play className="w-4 h-4" />
                Mulai Tes
              </button>
            ) : (
              <div className="text-green-600 font-medium px-4 py-2">
                Tes sedang berjalan
              </div>
            )}
            <button
              onClick={resetTest}
              className="flex items-center gap-2 bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors"
            >
              <RotateCcw className="w-4 h-4" />
              Reset
            </button>
            <button
              onClick={exportToExcel}
              className="flex items-center gap-2 bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors"
            >
              <CheckCircle2 className="w-4 h-4" />
              Export Excel
            </button>
          </div>
          
          {/* Validation Message */}
          {(!participantInfo.name.trim() || participantInfo.nip.length !== 18) && (
            <div className="mt-3 text-sm text-amber-600 bg-amber-50 border border-amber-200 rounded-lg p-3">
              <strong>Perhatian:</strong> Lengkapi nama peserta dan NIP (18 digit) sebelum memulai tes.
            </div>
          )}
        </div>

        {/* Instructions */}
        <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
          <h3 className="font-semibold text-blue-900 mb-2">Petunjuk:</h3>
          <ul className="text-sm text-blue-800 space-y-1">
            <li>• Jumlahkan dua angka yang berurutan dalam setiap kolom</li>
            <li>• Masukkan hasil penjumlahan (maksimal 2 digit) di kolom jawaban</li>
            <li>• Pengisian dimulai dari bawah ke atas dalam setiap kolom</li>
            <li>• Anda bisa menjawab soal yang mana saja secara bebas</li>
            <li>• Gunakan Tab/Enter untuk pindah ke input berikutnya</li>
            <li>• Gunakan panah untuk navigasi manual (atas/bawah/kiri/kanan)</li>
            <li>• Kerjakan secepat dan seakurat mungkin</li>
          </ul>
        </div>

        {/* Test Grid */}
        <div className="bg-white rounded-lg shadow-sm p-6">
          <div className="overflow-x-auto">
            <div className="min-w-max">
              {/* Number Grid - 25 rows */}
              {testData.numbers.map((row, rowIndex) => (
                <div key={`row-${rowIndex}`} className="flex gap-1 mb-1">
                  {row.map((number, colIndex) => (
                    <React.Fragment key={`row-group-${rowIndex}-${colIndex}`}>
                      <div className="w-8 h-8 flex items-center justify-center text-lg font-mono border border-gray-200 bg-gray-50">
                        {number}
                      </div>
                      <div className="w-8 h-8 flex items-center justify-center">
                        <input
                          ref={el => {
                            if (!inputRefs.current[rowIndex]) {
                              inputRefs.current[rowIndex] = [];
                            }
                            inputRefs.current[rowIndex][colIndex] = el;
                          }}
                          type="text"
                          maxLength={2}
                          value={testData.answers[rowIndex]?.[colIndex] || ''}
                          onChange={(e) => handleAnswerChange(rowIndex, colIndex, e.target.value)}
                          onKeyDown={(e) => handleKeyDown(e, rowIndex, colIndex)}
                          className={`w-8 h-8 text-center text-sm font-mono border-2 rounded transition-all ${
                            testData.answers[rowIndex]?.[colIndex]
                              ? 'border-green-300 bg-green-50'
                              : 'border-gray-300 bg-white hover:border-blue-300'
                          } focus:border-blue-500 focus:bg-blue-50 focus:ring-2 focus:ring-blue-200 focus:outline-none`}
                          disabled={!isRunning}
                          placeholder=""
                        />
                      </div>
                    </React.Fragment>
                  ))}
                </div>
              ))}

              {/* Column Footers */}
              <div className="flex gap-1 mt-2">
                {Array.from({ length: 50 }, (_, colIndex) => (
                  <React.Fragment key={`footer-group-${colIndex}`}>
                    <div className="w-8 text-center">
                      <div className="text-xs font-medium text-gray-500">
                        {colIndex + 1}
                      </div>
                    </div>
                    <div className="w-8 text-center">
                      <div className="text-xs font-medium text-blue-600">
                        J{colIndex + 1}
                      </div>
                    </div>
                  </React.Fragment>
                ))}
              </div>
            </div>
          </div>
        </div>

        {/* Status Bar */}
        <div className="mt-6 bg-white rounded-lg shadow-sm p-4">
          <div className="flex items-center justify-between text-sm text-gray-600">
            <div>
              Total kolom jawaban: <span className="font-mono font-medium">50 kolom × 25 baris = 1,250 jawaban</span>
            </div>
            <div>
              Status: <span className={`font-medium ${
                !isRunning ? 'text-gray-500' : 'text-green-600'
              }`}>
                {!isRunning ? 'Belum dimulai' : 'Berjalan'}
              </span>
            </div>
            <div>
              Jawaban terisi: <span className="font-medium text-blue-600">{filledAnswers}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

export default App;