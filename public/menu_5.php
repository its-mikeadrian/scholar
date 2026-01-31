<?php
require_once __DIR__ . '/../src/security.php';
secure_session_start();
require_once __DIR__ . '/../src/auth.php';
enforce_auth_for_page(basename(__FILE__));
if (!isset($_SESSION['auth_user_id'])) {
    header('Location: ' . route_url('admin'));
    exit;
}

$user_id = $_SESSION['auth_user_id'];
$role = auth_role();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iskolar Nang Luis - ANNOUNCEMENTS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-[#f0f7ff]">
    <?php require __DIR__ . '/header.php'; ?>
    <?php require __DIR__ . '/sidebar.php'; ?>
    <div class="pt-14 lg:pl-16" id="appMain">
        <main id="app-content" class="max-w-4xl mx-auto px-4 py-6">
            <!-- Create Announcement Section -->
            <div class="mb-8">
                <div class="rounded-2xl bg-white p-6 shadow-sm">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">📢 Create Announcement</h2>
                    
                    <form id="announcementForm" class="space-y-4">
                        <?= csrf_input(); ?>
                        
                        <!-- Title Input -->
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

                        <!-- Content Input -->
                        <div>
                            <label for="announcementContent" class="block text-sm font-semibold text-gray-700 mb-2">
                                Message
                            </label>
                            <textarea 
                                id="announcementContent" 
                                name="content" 
                                placeholder="Write your announcement message..." 
                                rows="5"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
                                required
                            ></textarea>
                        </div>

                        <!-- Image Upload -->
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

                        <!-- Error/Success Messages -->
                        <div id="messageContainer"></div>

                        <!-- Action Buttons -->
                        <div class="flex gap-3 pt-4">
                            <button 
                                type="submit" 
                                id="postBtn"
                                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold flex-1"
                            >
                                📤 Post Announcement
                            </button>
                            <button 
                                type="reset"
                                class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-semibold"
                            >
                                🔄 Clear
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Announcements Feed -->
            <div class="rounded-2xl bg-white p-6 shadow-sm">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">📰 Announcements Feed</h2>
                <div id="announcementsFeed" class="space-y-6">
                    <div class="text-center text-gray-500 py-8">Loading announcements...</div>
                </div>
                <div id="loadMoreContainer" class="text-center mt-6"></div>
            </div>
        </main>
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
            postBtn.textContent = '⏳ Posting...';
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
                    loadAnnouncements();
                } else {
                    showMessage('✗ ' + (data.message || 'Failed to post announcement'), 'error');
                }
            } catch (error) {
                showMessage('✗ An error occurred', 'error');
                console.error(error);
            } finally {
                postBtn.disabled = false;
                postBtn.textContent = '📤 Post Announcement';
            }
        });

        // Load announcements
        async function loadAnnouncements() {
            try {
                const response = await fetch(`announcement_actions.php?action=get&page=${currentPage}`);
                const data = await response.json();

                if (data.success) {
                    if (currentPage === 1) {
                        announcementsFeed.innerHTML = '';
                    }

                    if (data.announcements.length === 0 && currentPage === 1) {
                        announcementsFeed.innerHTML = '<div class="text-center text-gray-500 py-8">No announcements yet</div>';
                    } else {
                        data.announcements.forEach(announcement => {
                            const author = announcement.first_name && announcement.last_name 
                                ? `${announcement.first_name} ${announcement.last_name}` 
                                : announcement.username;
                            const date = new Date(announcement.created_at).toLocaleDateString();
                            
                            const card = document.createElement('div');
                            card.className = 'bg-gray-50 rounded-lg border border-gray-200 p-4';
                            card.innerHTML = `
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <h3 class="font-bold text-lg text-gray-800">${escapeHtml(announcement.title)}</h3>
                                        <p class="text-sm text-gray-500">by ${escapeHtml(author)} • ${date}</p>
                                    </div>
                                    ${announcement.user_id === <?= $user_id ?> || '<?= $role ?>' === 'superadmin' ? `
                                        <button onclick="deleteAnnouncement(${announcement.id})" class="text-red-500 hover:text-red-700 text-sm font-semibold">
                                            🗑️ Delete
                                        </button>
                                    ` : ''}
                                </div>
                                <p class="text-gray-700 whitespace-pre-wrap mb-4">${escapeHtml(announcement.content)}</p>
                                ${announcement.image_path ? `
                                    <img src="${announcement.image_path}" alt="Announcement image" class="w-full rounded-lg max-h-96 object-cover" onerror="this.style.display='none'">
                                ` : ''}
                            `;
                            announcementsFeed.appendChild(card);
                        });
                    }

                    // Load more button
                    loadMoreContainer.innerHTML = '';
                    if (currentPage < data.total_pages) {
                        const loadMoreBtn = document.createElement('button');
                        loadMoreBtn.className = 'px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition';
                        loadMoreBtn.textContent = '📥 Load More';
                        loadMoreBtn.onclick = () => {
                            currentPage++;
                            loadAnnouncements();
                        };
                        loadMoreContainer.appendChild(loadMoreBtn);
                    }
                }
            } catch (error) {
                console.error('Failed to load announcements:', error);
                announcementsFeed.innerHTML = '<div class="text-center text-red-500 py-8">Failed to load announcements</div>';
            }
        }

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
                    currentPage = 1;
                    loadAnnouncements();
                    showMessage('✓ Announcement deleted', 'success');
                } else {
                    showMessage('✗ Failed to delete announcement', 'error');
                }
            } catch (error) {
                console.error('Delete error:', error);
                showMessage('✗ Error deleting announcement', 'error');
            }
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

        // Load announcements on page load
        loadAnnouncements();
    </script>
</body>

</html>
