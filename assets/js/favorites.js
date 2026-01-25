document.addEventListener('DOMContentLoaded', function() {
    // Get CSRF token
    const csrfToken = document.querySelector('[name=csrf_token]').value;
    
    // Add event listeners to favorite checkboxes
    document.querySelectorAll('.favorite-checkbox').forEach(checkbox => {
      checkbox.addEventListener('change', function(e) {
        const gameId = this.dataset.gameId;
        const gameName = this.dataset.name;
        const cardElement = this.closest('.game-card');
        
        // Toggle favorite status
        fetch('api/toggle-favorite.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
          },
          body: JSON.stringify({
            game_id: gameId,
            name: gameName
          })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // If removed from favorites, remove the card from the page
            if (!data.added) {
              // Add a fade-out effect
              cardElement.style.opacity = '0';
              cardElement.style.transform = 'scale(0.8)';
              cardElement.style.transition = 'opacity 0.5s, transform 0.5s';
              
              // Remove the element after animation
              setTimeout(() => {
                cardElement.remove();
                
                // Check if there are any cards left
                const remainingCards = document.querySelectorAll('.game-card');
                if (remainingCards.length === 0) {
                  // Reload the page to show the "No Favorites" message
                  location.reload();
                }
              }, 500);
            }
          }
        });
      });
    });
  });