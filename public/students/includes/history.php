<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="img/sanluislogo.png">
    <link rel="icon" type="image/png" href="img/sanluislogo.png">
    <title>Iskolar Nang Luis - EDUCATIONAL ASSISTANCE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="includes/footer.css">
    <link rel="stylesheet" href="includes/header.css">

    <style>
        .history-item { 
            background: #f9f9f9; 
            padding: 15px; 
            border-radius: 8px; 
            margin-bottom: 12px; 
            border-left: 4px solid #667eea; 
            display: flex; 
            justify-content: space-between; 
            align-items: flex-start; 
        }
        .history-item-date { 
            color: #667eea; 
            font-weight: 600; 
            font-size: 0.9rem; 
            margin-bottom: 8px; 
        }
        .history-item-name { 
            font-weight: 600; 
            color: #333; 
            margin-bottom: 6px; 
        }
        .history-item-status { 
            background: #e8f5e9; 
            color: #2e7d32; 
            padding: 4px 8px; 
            border-radius: 4px; 
            font-size: 0.85rem; 
            display: inline-block; 
            white-space: nowrap; 
            margin-left: 12px; 
        }
        .history-item-details { 
            color: #666; 
            font-size: 0.9rem; 
            margin-top: 8px; 
            line-height: 1.5; 
        }
        .no-history { 
            text-align: center; 
            color: #999; 
            padding: 40px 20px; 
        }
    </style>

    <!-- History Modal -->
    <div id="historyModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Application History</h2>
                <button class="close-btn" onclick="closeHistoryModal()">&times;</button>
            </div>
            <div id="historyList">
                <!-- History items will be populated here -->
            </div>
        </div>
    </div>

    <!-- Main content: Application Tracker + Submitted Form -->
    <main style="padding: 90px 0 40px;">
        <div class="container">

    <script>

        // History Modal Functions
        function openHistoryModal() {
            const modal = document.getElementById('historyModal');
            modal.classList.add('active');
            populateHistory();
        }

        function closeHistoryModal() {
            const modal = document.getElementById('historyModal');
            modal.classList.remove('active');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('historyModal');
            if (event.target == modal) {
                modal.classList.remove('active');
            }
        }

        // Populate history with sample data
        function populateHistory() {
            const historyList = document.getElementById('historyList');
            
            // Sample history data - in a real app, this would come from the server
            const historyData = [
                {
                    date: 'June 3, 2025',
                    name: 'Dela Cruz, Juan Cruz',
                    status: 'Completed',
                    level: '1st Year - 1st Semester',
                    gwa: '1.25'
                },
                {
                    date: 'October 15, 2025',
                    name: 'Dela Cruz, Juan Cruz',
                    status: 'Completed',
                    level: '1st Year - 2nd Semester',
                    gwa: '1.50'
                },
                {
                    date: 'June 20, 2026',
                    name: 'Dela Cruz, Juan Cruz',
                    status: 'Completed',
                    level: '2nd Year - 1st Semester',
                    gwa: '1.75'
                }
            ];

            if (historyData.length === 0) {
                historyList.innerHTML = '<div class="no-history"><i class="fas fa-inbox" style="font-size: 3rem; color: #ddd; margin-bottom: 10px; display: block;"></i>No submitted forms yet.</div>';
            } else {
                historyList.innerHTML = historyData.map(item => `
                    <div class="history-item">
                        <div>
                            <div class="history-item-date"><i class="fas fa-calendar-alt"></i> ${item.date}</div>
                            <div class="history-item-name">${item.name}</div>
                            <div class="history-item-details">
                                <strong>Academic Level:</strong> ${item.level}
                            </div>
                        </div>
                        <div class="history-item-status"><i class="fas fa-check-circle"></i> ${item.status}</div>
                    </div>
                `).join('');
            }
        }
    </script>
</body>
</html>