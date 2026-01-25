document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const modal = document.getElementById('profileModal');
    const openModalBtn = document.getElementById('openProfileModal');
    const closeModalBtn = document.getElementById('closeProfileModal');
    const cancelBtn = document.getElementById('cancelProfileEdit');
    const form = document.getElementById('profileForm');
    
    // Open modal
    openModalBtn.addEventListener('click', function() {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden'; // Prevent scrolling while modal is open
        
        // Fetch current user data
        fetchUserData();
    });
    
    // Close modal functions
    function closeModal() {
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto'; // Re-enable scrolling
        form.reset();
    }
    
    closeModalBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);
    
    // Close modal when clicking outside of it
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeModal();
        }
    });
    
    // Fetch user data from server
    function fetchUserData() {
        fetch('get_user_data.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Populate form with existing user data
                    document.getElementById('username').value = data.username;
                    document.getElementById('email').value = data.email;
                    // Password fields intentionally left blank
                } else {
                    console.error('Failed to fetch user data:', data.error);
                }
            })
            .catch(error => {
                console.error('Error fetching user data:', error);
            });
    }
    
    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validate required fields
        const currentPassword = document.getElementById('current_password').value;
        if (!currentPassword) {
            showFormError('Current password is required to make changes');
            return;
        }
        
        // Create FormData object to handle file uploads
        const formData = new FormData(form);
        
        // Submit form data
        fetch('update_profile.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                alert('Profile updated successfully!');
                closeModal();
                
                // Reload page to reflect changes
                window.location.reload();
            } else {
                // Show error message
                showFormError(data.error || 'An error occurred while updating your profile');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showFormError('An error occurred while submitting the form');
        });
    });
    
    function showFormError(message) {
        // Create error element if it doesn't exist
        let errorElement = document.getElementById('profileFormError');
        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.id = 'profileFormError';
            errorElement.className = 'bg-red-600 bg-opacity-70 text-white p-3 rounded mb-4';
            form.prepend(errorElement);
        }
        
        errorElement.textContent = message;
        errorElement.style.display = 'block';
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            errorElement.style.display = 'none';
        }, 5000);
    }
});