<?php
require_once __DIR__ . '/../src/security.php';
secure_session_start();
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/db.php';
enforce_auth_for_page(basename(__FILE__));
if (!isset($_SESSION['auth_user_id'])) {
    header('Location: ' . route_url(''));
    exit;
}

$user_id = $_SESSION['auth_user_id'];
$role = auth_role();

// Fetch announcements server-side
$announcements = [];
$stats = ['total' => 0, 'this_month' => 0, 'this_week' => 0, 'today' => 0];

try {
    $pdo = get_db_connection();
    $stmt = $pdo->prepare("
        SELECT 
            a.id,
            a.user_id,
            a.title,
            a.content,
            a.image_path,
            a.created_at,
            u.username,
            COALESCE(sp.first_name, '') as first_name,
            COALESCE(sp.last_name, '') as last_name
        FROM announcements a
        JOIN users u ON a.user_id = u.id
        LEFT JOIN student_profiles sp ON u.id = sp.user_id
        ORDER BY a.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate statistics
    $stats['total'] = $pdo->query("SELECT COUNT(*) as count FROM announcements")->fetch(PDO::FETCH_ASSOC)['count'];
    
    $stats['this_month'] = $pdo->query("
        SELECT COUNT(*) as count FROM announcements 
        WHERE YEAR(created_at) = YEAR(NOW()) AND MONTH(created_at) = MONTH(NOW())
    ")->fetch(PDO::FETCH_ASSOC)['count'];
    
    $stats['this_week'] = $pdo->query("
        SELECT COUNT(*) as count FROM announcements 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ")->fetch(PDO::FETCH_ASSOC)['count'];
    
    $stats['today'] = $pdo->query("
        SELECT COUNT(*) as count FROM announcements 
        WHERE DATE(created_at) = DATE(NOW())
    ")->fetch(PDO::FETCH_ASSOC)['count'];
} catch (Exception $e) {
    error_log('Error fetching announcements: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iskolar Nang Luis - ANNOUNCEMENTS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</head>

<body class="min-h-screen bg-[#f0f7ff]">
    <?php require __DIR__ . '/header.php'; ?>
    <?php require __DIR__ . '/sidebar.php'; ?>
    <div class="pt-14 lg:pl-16" id="appMain">
        <main id="app-content" class="max-w-4xl mx-auto px-4 py-6">
            <!-- Date Filter Calendar & Statistics -->
            <div class="mb-8 bg-white rounded-2xl p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Filter by Date</h3>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Calendar -->
                    <div class="lg:col-span-1">
                        <div id="calendarContainer" class="mb-4"></div>
                    </div>
                    
                    <!-- Statistics -->
                    <div class="lg:col-span-1">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">Announcement Statistics</h4>
                        <div class="grid grid-cols-2 gap-3 mb-4">
                            <!-- Total Announcements -->
                            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-3 border border-blue-200">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-xs text-gray-600 font-medium">Total Announcements</p>
                                        <p class="text-2xl font-bold text-blue-600 mt-0"><?php echo $stats['total']; ?></p>
                                    </div>
                                    <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                            </div>
                            
                            <!-- This Month -->
                            <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-3 border border-green-200">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-xs text-gray-600 font-medium">This Month</p>
                                        <p class="text-2xl font-bold text-green-600 mt-0"><?php echo $stats['this_month']; ?></p>
                                    </div>
                                    <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                            </div>
                            
                            <!-- This Week -->
                            <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-3 border border-purple-200">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-xs text-gray-600 font-medium">This Week</p>
                                        <p class="text-2xl font-bold text-purple-600 mt-0"><?php echo $stats['this_week']; ?></p>
                                    </div>
                                    <svg class="w-8 h-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 012 12V7a2 2 0 012-2z"></path>
                                    </svg>
                                </div>
                            </div>
                            
                            <!-- Today -->
                            <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-lg p-3 border border-orange-200">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-xs text-gray-600 font-medium">Today</p>
                                        <p class="text-2xl font-bold text-orange-600 mt-0"><?php echo $stats['today']; ?></p>
                                    </div>
                                    <svg class="w-8 h-8 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <button onclick="openCreateModal()" class="w-full px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold">
                            + Create Announcement
                        </button>
                    </div>
                </div>
                
                <input 
                    type="text" 
                    id="dateFilter" 
                    placeholder="Select a date..." 
                    class="hidden"
                >
            </div>

            <!-- Announcements Feed -->
            <div class="rounded-2xl bg-white p-6 shadow-sm">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Announcements Feed</h2>
                <div id="announcementsFeed" class="space-y-6">
                    <?php if (empty($announcements)): ?>
                        <div class="text-center text-gray-500 py-8">No announcements yet</div>
                    <?php else: ?>
                        <?php foreach ($announcements as $announcement): ?>
                            <?php $announcementDate = date('Y-m-d', strtotime($announcement['created_at'])); ?>
                            <div class="bg-gray-50 rounded-lg border border-gray-200 p-4 announcement-card" data-date="<?php echo $announcementDate; ?>">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <h3 class="font-bold text-lg text-gray-800"><?php echo htmlspecialchars($announcement['title']); ?></h3>
                                        <p class="text-sm text-gray-500">
                                            by <?php echo htmlspecialchars(($announcement['first_name'] && $announcement['last_name']) ? $announcement['first_name'] . ' ' . $announcement['last_name'] : $announcement['username']); ?> • 
                                            <?php echo date('M d, Y', strtotime($announcement['created_at'])); ?>
                                        </p>
                                    </div>
                                    <?php if ($announcement['user_id'] == $user_id || $role === 'superadmin'): ?>
                                        <div class="flex gap-2">
                                            <button onclick="editAnnouncement(<?php echo $announcement['id']; ?>, '<?php echo addslashes($announcement['title']); ?>', '<?php echo addslashes($announcement['content']); ?>')" class="text-blue-500 hover:text-blue-700 text-sm font-semibold">
                                                Edit
                                            </button>
                                            <button onclick="deleteAnnouncement(<?php echo $announcement['id']; ?>)" class="text-red-500 hover:text-red-700 text-sm font-semibold">
                                                Delete
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <p class="text-gray-700 whitespace-pre-wrap mb-4"><?php echo htmlspecialchars($announcement['content']); ?></p>
                                <?php if ($announcement['image_path']): ?>
                                    <img src="<?php echo htmlspecialchars($announcement['image_path']); ?>" alt="Announcement image" class="w-full rounded-lg max-h-96 object-cover" onerror="this.style.display='none'">
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div id="loadMoreContainer" class="text-center mt-6"></div>
            </div>
        </main>
    </div>

    <!-- Create Announcement Modal -->
    <div id="createModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <h3 class="text-2xl font-bold text-gray-800 mb-6">Create Announcement</h3>
            <form id="announcementForm" class="space-y-4">
                <?= csrf_input(); ?>
                
                <div>
                    <label for="announcementTitle" class="block text-sm font-semibold text-gray-700 mb-2">
                        Title
                    </label>
                    <input 
                        type="text" 
                        id="announcementTitle" 
                        name="title" 
                        placeholder="Enter announcement title..." 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required
                    >
                </div>

                <div>
                    <label for="announcementContent" class="block text-sm font-semibold text-gray-700 mb-2">
                        Message
                    </label>
                    <textarea 
                        id="announcementContent" 
                        name="content" 
                        placeholder="Write your announcement message..." 
                        rows="4"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
                        required
                    ></textarea>
                </div>

                <div>
                    <label for="announcementImage" class="block text-sm font-semibold text-gray-700 mb-2">
                        Image (Optional)
                    </label>
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer hover:border-blue-500 transition"
                         id="imageDropZone">
                        <input 
                            type="file" 
                            id="announcementImage" 
                            name="image" 
                            accept="image/*"
                            class="hidden"
                        >
                        <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <p class="text-gray-600">Click or drag image here (Max 5MB)</p>
                        <p class="text-sm text-gray-400 mt-1">PNG, JPG, GIF, WebP supported</p>
                    </div>
                </div>

                <div id="messageContainer"></div>

                <div class="flex gap-3 pt-4">
                    <button 
                        type="submit" 
                        id="postBtn"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold flex-1"
                    >
                        Post Announcement
                    </button>
                    <button 
                        type="button"
                        onclick="closeCreateModal()"
                        class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-semibold flex-1"
                    >
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Announcement Modal -->
    <div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Edit Announcement</h3>
            <form id="editForm" class="space-y-4">
                <input type="hidden" id="editAnnouncementId" name="id">
                
                <div>
                    <label for="editTitle" class="block text-sm font-semibold text-gray-700 mb-2">
                        Title
                    </label>
                    <input 
                        type="text" 
                        id="editTitle" 
                        name="title" 
                        placeholder="Announcement title..." 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required
                    >
                </div>

                <div>
                    <label for="editContent" class="block text-sm font-semibold text-gray-700 mb-2">
                        Message
                    </label>
                    <textarea 
                        id="editContent" 
                        name="content" 
                        placeholder="Announcement message..." 
                        rows="4"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
                        required
                    ></textarea>
                </div>

                <div class="flex gap-3 pt-4">
                    <button 
                        type="submit" 
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold flex-1"
                    >
                        Save Changes
                    </button>
                    <button 
                        type="button"
                        onclick="closeEditModal()"
                        class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-semibold flex-1"
                    >
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('input[name="csrf_token"]')?.value || '';
        const announcementForm = document.getElementById('announcementForm');
        const titleInput = document.getElementById('announcementTitle');
        const contentInput = document.getElementById('announcementContent');
        const imageInput = document.getElementById('announcementImage');
        const imageDropZone = document.getElementById('imageDropZone');
        const postBtn = document.getElementById('postBtn');
        const messageContainer = document.getElementById('messageContainer');
        const announcementsFeed = document.getElementById('announcementsFeed');
        const loadMoreContainer = document.getElementById('loadMoreContainer');

        let selectedImage = null;
        let currentPage = 1;

        // Store all announcements data
        const allAnnouncements = <?php echo json_encode($announcements); ?>;

        // Initialize Flatpickr calendar
        flatpickr('#dateFilter', {
            mode: 'single',
            dateFormat: 'Y-m-d',
            inline: true,
            appendTo: document.getElementById('calendarContainer'),
            onChange: function(selectedDates) {
                if (selectedDates.length > 0) {
                    const selectedDate = selectedDates[0];
                    const dateStr = selectedDate.toISOString().split('T')[0];
                    filterAnnouncementsByDate(dateStr);
                }
            }
        });

        // Image upload handling
        imageDropZone.addEventListener('click', () => imageInput.click());

        imageDropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            imageDropZone.classList.add('border-blue-500', 'bg-blue-50');
        });

        imageDropZone.addEventListener('dragleave', () => {
            imageDropZone.classList.remove('border-blue-500', 'bg-blue-50');
        });

        imageDropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            imageDropZone.classList.remove('border-blue-500', 'bg-blue-50');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                imageInput.files = files;
                handleImageSelection();
            }
        });

        imageInput.addEventListener('change', handleImageSelection);

        function handleImageSelection() {
            if (imageInput.files.length > 0) {
                selectedImage = imageInput.files[0];
                const reader = new FileReader();
                reader.onload = function(e) {
                    const previewHTML = `
                        <div class="space-y-3">
                            <img src="${e.target.result}" alt="Preview" class="w-full max-h-80 rounded-lg border border-gray-200" style="display:block;">
                            <div class="text-left bg-green-50 p-3 rounded-lg">
                                <p class="font-semibold text-green-600">✓ Image selected</p>
                                <p class="text-sm text-gray-600">${selectedImage.name}</p>
                                <p class="text-xs text-gray-500">${(selectedImage.size / 1024 / 1024).toFixed(2)} MB</p>
                                <button type="button" onclick="removeImage()" class="mt-2 text-red-500 hover:text-red-700 font-bold text-sm">✕ Remove Image</button>
                            </div>
                        </div>
                    `;
                    imageDropZone.innerHTML = previewHTML;
                };
                reader.readAsDataURL(selectedImage);
            }
        }

        function removeImage() {
            selectedImage = null;
            imageInput.value = '';
            imageDropZone.innerHTML = `
                <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                <p class="text-gray-600">Click or drag image here (Max 5MB)</p>
                <p class="text-sm text-gray-400 mt-1">PNG, JPG, GIF, WebP supported</p>
            `;
        }

        // Form submission
        announcementForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const title = titleInput.value.trim();
            const content = contentInput.value.trim();

            if (!title || !content) {
                showMessage('Please fill in all required fields', 'error');
                return;
            }

            postBtn.disabled = true;
            postBtn.textContent = 'Posting...';
            messageContainer.innerHTML = '';

            const formData = new FormData();
            formData.append('csrf_token', csrfToken);
            formData.append('title', title);
            formData.append('content', content);
            if (selectedImage) {
                formData.append('image', selectedImage);
            }

            try {
                const response = await fetch('announcement_actions.php?action=post', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showMessage('✓ Announcement posted successfully!', 'success');
                    announcementForm.reset();
                    selectedImage = null;
                    imageDropZone.innerHTML = `
                        <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <p class="text-gray-600">Click or drag image here (Max 5MB)</p>
                        <p class="text-sm text-gray-400 mt-1">PNG, JPG, GIF, WebP supported</p>
                    `;
                    currentPage = 1;
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showMessage('✗ ' + (data.message || 'Failed to post announcement'), 'error');
                }
            } catch (error) {
                showMessage('✗ An error occurred', 'error');
                console.error(error);
            } finally {
                postBtn.disabled = false;
                postBtn.textContent = 'Post Announcement';
            }
        });

        // Delete announcement
        async function deleteAnnouncement(id) {
            if (!confirm('Are you sure you want to delete this announcement?')) return;

            try {
                const response = await fetch('announcement_actions.php?action=delete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `csrf_token=${csrfToken}&id=${id}`
                });

                const data = await response.json();
                if (data.success) {
                    showMessage('✓ Announcement deleted', 'success');
                    // Reload the page to refresh announcements
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showMessage('✗ Failed to delete announcement', 'error');
                }
            } catch (error) {
                console.error('Delete error:', error);
                showMessage('✗ Error deleting announcement', 'error');
            }
        }

        // Edit announcement functions
        function editAnnouncement(id, title, content) {
            document.getElementById('editAnnouncementId').value = id;
            document.getElementById('editTitle').value = title;
            document.getElementById('editContent').value = content;
            document.getElementById('editModal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        // Create announcement functions
        function openCreateModal() {
            document.getElementById('announcementForm').reset();
            selectedImage = null;
            imageDropZone.innerHTML = `
                <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                <p class="text-gray-600">Click or drag image here (Max 5MB)</p>
                <p class="text-sm text-gray-400 mt-1">PNG, JPG, GIF, WebP supported</p>
            `;
            messageContainer.innerHTML = '';
            document.getElementById('createModal').classList.remove('hidden');
        }

        function closeCreateModal() {
            document.getElementById('createModal').classList.add('hidden');
        }

        // Edit form submission
        document.getElementById('editForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const id = document.getElementById('editAnnouncementId').value;
            const title = document.getElementById('editTitle').value.trim();
            const content = document.getElementById('editContent').value.trim();

            if (!title || !content) {
                showMessage('Please fill in all required fields', 'error');
                return;
            }

            try {
                const response = await fetch('announcement_actions.php?action=update', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `csrf_token=${csrfToken}&id=${id}&title=${encodeURIComponent(title)}&content=${encodeURIComponent(content)}`
                });

                const data = await response.json();
                if (data.success) {
                    showMessage('✓ Announcement updated', 'success');
                    closeEditModal();
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showMessage('✗ ' + (data.message || 'Failed to update announcement'), 'error');
                }
            } catch (error) {
                console.error('Edit error:', error);
                showMessage('✗ Error updating announcement', 'error');
            }
        });

        // Close modal on outside click
        document.getElementById('editModal').addEventListener('click', (e) => {
            if (e.target.id === 'editModal') {
                closeEditModal();
            }
        });

        document.getElementById('createModal').addEventListener('click', (e) => {
            if (e.target.id === 'createModal') {
                closeCreateModal();
            }
        });

        // Date filter functionality
        function filterAnnouncementsByDate(dateString) {
            announcementsFeed.innerHTML = '';
            
            // Filter announcements from stored data
            const filtered = allAnnouncements.filter(announcement => {
                const announcementDate = announcement.created_at.split(' ')[0]; // Get YYYY-MM-DD
                return announcementDate === dateString;
            });
            
            if (filtered.length === 0) {
                announcementsFeed.innerHTML = '<div class="text-center text-gray-500 py-8">No announcements found for this date</div>';
                return;
            }
            
            // Build HTML from filtered announcements
            filtered.forEach(announcement => {
                const author = (announcement.first_name && announcement.last_name) 
                    ? `${announcement.first_name} ${announcement.last_name}` 
                    : announcement.username;
                const date = new Date(announcement.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
                
                const canEdit = announcement.user_id == <?php echo $user_id; ?> || '<?php echo $role; ?>' === 'superadmin';
                
                const cardHTML = `
                    <div class="bg-gray-50 rounded-lg border border-gray-200 p-4">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <h3 class="font-bold text-lg text-gray-800">${escapeHtml(announcement.title)}</h3>
                                <p class="text-sm text-gray-500">
                                    by ${escapeHtml(author)} • ${date}
                                </p>
                            </div>
                            ${canEdit ? `
                                <div class="flex gap-2">
                                    <button onclick="editAnnouncement(${announcement.id}, '${escapeForJS(announcement.title)}', '${escapeForJS(announcement.content)}')" class="text-blue-500 hover:text-blue-700 text-sm font-semibold">
                                        Edit
                                    </button>
                                    <button onclick="deleteAnnouncement(${announcement.id})" class="text-red-500 hover:text-red-700 text-sm font-semibold">
                                        Delete
                                    </button>
                                </div>
                            ` : ''}
                        </div>
                        <p class="text-gray-700 whitespace-pre-wrap mb-4">${escapeHtml(announcement.content)}</p>
                        ${announcement.image_path ? `
                            <img src="${announcement.image_path}" alt="Announcement image" class="w-full rounded-lg max-h-96 object-cover" onerror="this.style.display='none'">
                        ` : ''}
                    </div>
                `;
                
                const card = document.createElement('div');
                card.innerHTML = cardHTML;
                announcementsFeed.appendChild(card.firstElementChild);
            });
        }

        function resetDateFilter() {
            document.getElementById('dateFilter').value = '';
            flatpickr('#dateFilter').clear();
            location.reload();
        }

        // Utility functions
        function showMessage(message, type) {
            const className = type === 'success' ? 'bg-green-100 text-green-700 border-green-300' : 'bg-red-100 text-red-700 border-red-300';
            messageContainer.innerHTML = `
                <div class="p-4 rounded-lg border ${className}">
                    ${message}
                </div>
            `;
            setTimeout(() => {
                messageContainer.innerHTML = '';
            }, 5000);
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function escapeForJS(text) {
            return text.replace(/'/g, "\\'").replace(/"/g, '\\"').replace(/\n/g, '\\n');
        }
    </script>
</body>

</html>