// Kraepelin Test Application JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize application
    initializeApp();
});

function initializeApp() {
    // Setup form validation
    setupFormValidation();
    
    // Setup answer inputs
    setupAnswerInputs();
    
    // Setup keyboard navigation
    setupKeyboardNavigation();
    
    // Setup auto-save
    setupAutoSave();
    
}

function setupFormValidation() {
    // Form validation is no longer needed since participant info is pre-filled from login
    console.log('Participant is logged in, form validation skipped');
}

function setupAnswerInputs() {
    const answerInputs = document.querySelectorAll('.answer-input');
    
    answerInputs.forEach(input => {
        // Only allow numeric input
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 2) {
                value = value.slice(0, 2);
            }
            e.target.value = value;
            
            // Update visual state
            updateInputState(e.target);
            
            // Auto-save answer
            saveAnswer(e.target);
        });
        
        // Handle paste events
        input.addEventListener('paste', function(e) {
            e.preventDefault();
            const paste = (e.clipboardData || window.clipboardData).getData('text');
            const numericValue = paste.replace(/\D/g, '').slice(0, 2);
            e.target.value = numericValue;
            updateInputState(e.target);
            saveAnswer(e.target);
        });
        
        // Focus effects
        input.addEventListener('focus', function(e) {
            e.target.select();
        });
    });
}

function setupKeyboardNavigation() {
    const answerInputs = document.querySelectorAll('.answer-input');
    
    answerInputs.forEach(input => {
        input.addEventListener('keydown', function(e) {
            const row = parseInt(e.target.dataset.row);
            const col = parseInt(e.target.dataset.col);
            
            switch(e.key) {
                case 'Enter':
                case 'Tab':
                    e.preventDefault();
                    moveToNextInput(row, col);
                    break;
                case 'ArrowLeft':
                    e.preventDefault();
                    if (col > 0) {
                        focusInput(row, col - 1);
                    }
                    break;
                case 'ArrowRight':
                    e.preventDefault();
                    if (col < 49) {
                        focusInput(row, col + 1);
                    }
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    if (row > 0) {
                        focusInput(row - 1, col);
                    }
                    break;
                case 'ArrowDown':
                    e.preventDefault();
                    if (row < 24) {
                        focusInput(row + 1, col);
                    }
                    break;
                case 'Escape':
                    e.target.blur();
                    break;
            }
        });
    });
}

function moveToNextInput(currentRow, currentCol) {
    // Move to next available input (bottom to top, left to right)
    if (currentRow < 24) {
        // Move down in the same column
        focusInput(currentRow + 1, currentCol);
    } else if (currentCol < 49) {
        // Move to top of next column
        focusInput(0, currentCol + 1);
    }
}

function focusInput(row, col) {
    const input = document.querySelector(`input[data-row="${row}"][data-col="${col}"]`);
    if (input && !input.disabled) {
        setTimeout(() => {
            input.focus();
            input.select();
        }, 50);
    }
}

function updateInputState(input) {
    const value = input.value.trim();
    
    // Remove all state classes
    input.classList.remove('border-green-300', 'bg-green-50', 'border-gray-300', 'bg-white');
    
    if (value !== '') {
        input.classList.add('border-green-300', 'bg-green-50');
    } else {
        input.classList.add('border-gray-300', 'bg-white');
    }
}

function saveAnswer(input) {
    const row = input.dataset.row;
    const col = input.dataset.col;
    const value = input.value;
    
    // Send AJAX request to save answer
    fetch('index.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=update_answer&row=${row}&col=${col}&value=${encodeURIComponent(value)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update progress if needed
            updateProgress();
        }
    })
    .catch(error => {
        console.error('Error saving answer:', error);
    });
}

function setupAutoSave() {
    // Auto-save every 30 seconds
    setInterval(() => {
        const answerInputs = document.querySelectorAll('.answer-input');
        let hasChanges = false;
        
        answerInputs.forEach(input => {
            if (input.value.trim() !== '' && !input.dataset.saved) {
                hasChanges = true;
                saveAnswer(input);
                input.dataset.saved = 'true';
            }
        });
        
        if (hasChanges) {
            console.log('Auto-saved answers');
        }
    }, 30000);
}


function updateProgress() {
    // This function can be enhanced to update progress in real-time
    // For now, it's a placeholder for future enhancements
    const filledInputs = document.querySelectorAll('.answer-input').length;
    const totalInputs = 1250; // 25 rows × 50 columns
    
    // Calculate and update progress bar if needed
    // This would require additional AJAX calls to get current state
}

// Utility functions
function showMessage(message, type = 'info') {
    const messageDiv = document.createElement('div');
    messageDiv.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${getMessageClass(type)}`;
    messageDiv.textContent = message;
    
    document.body.appendChild(messageDiv);
    
    setTimeout(() => {
        messageDiv.remove();
    }, 5000);
}

function getMessageClass(type) {
    switch(type) {
        case 'success':
            return 'bg-green-100 border border-green-400 text-green-700';
        case 'error':
            return 'bg-red-100 border border-red-400 text-red-700';
        case 'warning':
            return 'bg-yellow-100 border border-yellow-400 text-yellow-700';
        default:
            return 'bg-blue-100 border border-blue-400 text-blue-700';
    }
}

function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// Export functions for global access
window.KraepelinApp = {
    focusInput,
    moveToNextInput,
    saveAnswer,
    showMessage,
    confirmAction
};