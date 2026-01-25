document.addEventListener('DOMContentLoaded', function() {
      const form = document.getElementById('gameForm');
      const resultsDiv = document.getElementById('results');
      const mainGameCard = document.getElementById('mainGameCard');
      const similarGamesContainer = document.getElementById('similarGamesContainer');
      const loadingSpinner = document.getElementById('loadingSpinner');
      const errorMessage = document.getElementById('errorMessage');
      const submitButton = document.getElementById('submitButton');
      const franchiseModal = document.getElementById('franchiseModal');
      const franchiseName = document.getElementById('franchiseName');
      const timelineContainer = document.getElementById('timelineContainer');
      const closeModal = document.getElementById('closeModal');
      const csrfToken = document.querySelector('input[name="csrf_token"]').value;

      // Modal close button
      closeModal.addEventListener('click', () => {
        franchiseModal.classList.remove('active');
        // Enable page scrolling
        document.body.style.overflow = 'auto';
      });

      // Close modal when clicking outside the content
      franchiseModal.addEventListener('click', (e) => {
        if (e.target === franchiseModal) {
          franchiseModal.classList.remove('active');
          // Enable page scrolling
          document.body.style.overflow = 'auto';
        }
      });

      form.addEventListener('submit', async (e) => {
        e.preventDefault();

        // Get input value
        const promptInput = document.getElementById('prompt');
        const userPrompt = promptInput.value.trim();

        if (!userPrompt) {
          return;  // Don't submit empty prompts
        }

        // Show loading state
        loadingSpinner.classList.remove('hidden');
        submitButton.disabled = true;
        submitButton.innerText = 'Finding games...';
        resultsDiv.classList.add('hidden');
        errorMessage.classList.add('hidden');
        mainGameCard.innerHTML = '';
        similarGamesContainer.innerHTML = '';

        try {
          // Send request to backend
          const response = await fetch('api_handler.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({
              prompt: userPrompt
            })
          });

          const data = await response.json();

          if (data.success && data.main_game) {
            // Render the main game
            renderMainGame(data.main_game);

            // Render similar games if available
            if (data.similar_games && data.similar_games.length > 0) {
              renderSimilarGames(data.similar_games);
            }

            resultsDiv.classList.remove('hidden');

            // Set up favorite checkboxes after rendering
            setupFavoriteCheckboxes();
          } else {
            // Show error
            errorMessage.textContent = data.error || 'No games found. Try a different prompt.';
            errorMessage.classList.remove('hidden');
          }
        } catch (error) {
          console.error('Error:', error);
          errorMessage.textContent = 'Something went wrong. Please try again.';
          errorMessage.classList.remove('hidden');
        } finally {
          // Reset UI state
          loadingSpinner.classList.add('hidden');
          submitButton.disabled = false;
          submitButton.innerText = 'Get Recommendations';
        }
      });

      function renderMainGame(game) {
        // Format the rating with one decimal place or show N/A
        const rating = game.total_rating ? 
          game.total_rating.toFixed(1) : 
          (game.rating ? game.rating.toFixed(1) : 'N/A');

        // Determine rating color based on value
        let ratingColor = 'border-gray-500';
        if (rating !== 'N/A') {
          const ratingValue = parseFloat(rating);
          if (ratingValue >= 85) ratingColor = 'border-green-500';
          else if (ratingValue >= 70) ratingColor = 'border-blue-500';
          else if (ratingValue >= 50) ratingColor = 'border-yellow-500';
          else ratingColor = 'border-red-500';
        }

        // Build badge HTML elements
        const genreBadges = (game.genre_names || [])
          .map(genre => `<span class="badge genre-badge">${genre}</span>`)
          .join('');

        const platformBadges = (game.platform_names || [])
          .map(platform => `<span class="badge platform-badge">${platform}</span>`)
          .join('');

        const themeBadges = (game.theme_names || [])
          .map(theme => `<span class="badge theme-badge">${theme}</span>`)
          .join('');

        const modeBadges = (game.game_mode_names || [])
          .map(mode => `<span class="badge mode-badge">${mode}</span>`)
          .join('');

        // Build store buttons HTML
        const storeButtons = (game.stores || [])
          .map(storeUrl => {
            let storeName = "Buy";
            if (storeUrl.includes("store.steampowered.com")) storeName = "Steam";
            else if (storeUrl.includes("epicgames.com")) storeName = "Epic Games";
            else if (storeUrl.includes("gog.com")) storeName = "GOG";
            else if (storeUrl.includes("itch.io")) storeName = "Itch.io";

            return `<a href="${storeUrl}" target="_blank" rel="noopener" class="store-button bg-purple-700 hover:bg-purple-600">${storeName}</a>`;
          })
          .join('');

        // Franchise button (only show if franchise data is available)
        const franchiseButton = game.franchise_details ? 
          `<button id="franchiseButton" class="franchise-btn">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M2 12h20M12 2v20" />
            </svg>
            Check Franchise
          </button>` : '';

        // Screenshot gallery (if available)
        const screenshotGallery = game.screenshots ? 
          `<div class="mt-6">
            <h4 class="text-lg font-semibold mb-2">Screenshots</h4>
            <div class="screenshots-container">
              ${game.screenshots.map(screenshot => 
                `<img src="${screenshot.url}" alt="Screenshot" class="screenshot">`
              ).join('')}
            </div>
          </div>` : '';
          
        // DLC and Expansions section (if available)
        const addOnSection = game.add_on_details && game.add_on_details.length > 0 ? 
          `<div class="mt-6">
            <h4 class="text-lg font-semibold mb-2">Downloadable Content & Expansions</h4>
            <div class="add-on-container">
              ${game.add_on_details.map(addOn => {
                // Cover image with fallback
                const addOnCover = addOn.cover && addOn.cover.url ? 
                  `<img src="${addOn.cover.url}" alt="${addOn.name}" class="add-on-cover">` : 
                  `<img src="assets/images/placeholder.png" alt="${addOn.name}" class="add-on-cover">`;
                  
                // Get first store link if available
                const addOnStoreLink = addOn.stores && addOn.stores.length > 0 ? 
                  `<a href="${addOn.stores[0]}" target="_blank" rel="noopener" class="mt-2 text-center bg-purple-700 hover:bg-purple-600 transition py-1 px-3 rounded text-white text-xs block">
                    Get ${addOn.type}
                  </a>` : '';
                  
                // Release year if available
                const releaseInfo = addOn.release_year ? 
                  `<span class="text-xs text-purple-300">${addOn.release_year}</span>` : '';
                  
                // Type badge (DLC or Expansion)
                const typeBadge = addOn.type === 'Expansion' ?
                  `<span class="add-on-type expansion-type">${addOn.type}</span>` :
                  `<span class="add-on-type dlc-type">${addOn.type}</span>`;
                  
                return `
                  <div class="add-on-card">
                    <div class="relative">
                      ${addOnCover}
                      ${typeBadge}
                    </div>
                    <div class="mt-2">
                      <div class="flex justify-between items-start">
                        <h5 class="font-bold text-sm">${addOn.name}</h5>
                        ${releaseInfo}
                      </div>
                      <p class="text-gray-300 text-xs mt-1 line-clamp-2">${addOn.summary || 'No description available.'}</p>
                      ${addOnStoreLink}
                    </div>
                  </div>`;
              }).join('')}
            </div>
          </div>` : '';

        // Company information
        const developers = (game.developers || []).join(', ');
        const publishers = (game.publishers || []).join(', ');

        const companyInfo = (developers || publishers) ? 
          `<div class="mt-4">
            ${developers ? `<p class="text-gray-300"><span class="text-white font-semibold">Developers:</span> ${developers}</p>` : ''}
            ${publishers ? `<p class="text-gray-300"><span class="text-white font-semibold">Publishers:</span> ${publishers}</p>` : ''}
          </div>` : '';

        // Storyline section (if available) with truncation
        const storylineSection = game.storyline ? 
          `<div class="mt-4">
            <h4 class="text-lg font-semibold mb-2">Storyline</h4>
            <div class="storyline-container">
              <p class="text-gray-300 storyline-text storyline-truncated">${game.storyline}</p>
              <span class="read-more-btn storyline-read-more">Read More</span>
            </div>
          </div>` : '';

        // Official website link
        const officialWebsite = game.official_website ? 
          `<a href="${game.official_website}" target="_blank" rel="noopener" class="text-purple-400 hover:text-purple-300 mt-4 inline-block">Visit Official Website</a>` : '';

        // Cover image with fallback
        const coverImage = game.cover && game.cover.url ? 
          `<img src="${game.cover.url}" alt="${game.name}" class="rounded-lg object-cover w-full h-auto">` : 
          `<img src="assets/images/placeholder.png" alt="${game.name}" class="rounded-lg object-cover w-full h-auto">`;

        // Main HTML structure
        mainGameCard.innerHTML = `
          <div class="game-card p-6 bg-gray-800 rounded-lg shadow-lg">
            <div class="flex flex-col lg:flex-row gap-8">
              <!-- Cover Image Column -->
              <div class="flex-shrink-0 lg:w-1/4 min-w-[220px]">
                <div class="relative">
                  ${coverImage}
                  <div class="absolute top-2 right-2 rating-circle ${ratingColor} w-10 h-10">
                    <span class="text-sm font-bold">${rating}</span>
                  </div>
                  <div class="absolute top-2 left-2">
                    <div class="heart-container" title="Favorite">
                      <input type="checkbox" class="checkbox favorite-checkbox" id="favorite-main-${game.id}" 
                             data-game-id="${game.id}" 
                             data-name="${game.name}" 
                             data-cover-url="${game.cover && game.cover.url ? game.cover.url : ''}" 
                             data-summary="${game.summary || ''}" 
                             data-rating="${rating !== 'N/A' ? rating : ''}" 
                             data-release-date="${game.first_release_date || ''}">
                      <div class="svg-container">
                        <svg viewBox="0 0 24 24" class="svg-outline" xmlns="http://www.w3.org/2000/svg"><path d="M17.5,1.917a6.4,6.4,0,0,0-5.5,3.3,6.4,6.4,0,0,0-5.5-3.3A6.8,6.8,0,0,0,0,8.967c0,4.547,4.786,9.513,8.8,12.88a4.974,4.974,0,0,0,6.4,0C19.214,18.48,24,13.514,24,8.967A6.8,6.8,0,0,0,17.5,1.917Zm-3.585,18.4a2.973,2.973,0,0,1-3.83,0C4.947,16.006,2,11.87,2,8.967a4.8,4.8,0,0,1,4.5-5.05A4.8,4.8,0,0,1,11,8.967a1,1,0,0,0,2,0,4.8,4.8,0,0,1,4.5-5.05A4.8,4.8,0,0,1,22,8.967C22,11.87,19.053,16.006,13.915,20.313Z"></path></svg>
                        <svg viewBox="0 0 24 24" class="svg-filled" xmlns="http://www.w3.org/2000/svg"><path d="M17.5,1.917a6.4,6.4,0,0,0-5.5,3.3,6.4,6.4,0,0,0-5.5-3.3A6.8,6.8,0,0,0,0,8.967c0,4.547,4.786,9.513,8.8,12.88a4.974,4.974,0,0,0,6.4,0C19.214,18.48,24,13.514,24,8.967A6.8,6.8,0,0,0,17.5,1.917Z"></path></svg>
                        <svg class="svg-celebrate" width="100" height="100" xmlns="http://www.w3.org/2000/svg"><polygon points="10,10 20,20"></polygon><polygon points="10,50 20,50"></polygon><polygon points="20,80 30,70"></polygon><polygon points="90,10 80,20"></polygon><polygon points="90,50 80,50"></polygon><polygon points="80,80 70,70"></polygon></svg>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="mt-4 text-center">
                  ${franchiseButton}
                  ${storeButtons ? `<div class="mt-4">${storeButtons}</div>` : ''}
                  ${officialWebsite}
                  
                  <!-- Age Rating Covers -->
                  ${(game.esrb_rating_cover_url || game.pegi_rating_cover_url) ? `
                    <div class="mt-4 flex justify-center items-center gap-3">
                      ${game.esrb_rating_cover_url ? `
                        <div class="flex flex-col items-center">
                          <img src="${game.esrb_rating_cover_url}" alt="ESRB Rating" class="h-12 w-auto">
                          <span class="text-xs text-gray-400 mt-1">US</span>
                        </div>` : ''}
                      ${game.pegi_rating_cover_url ? `
                        <div class="flex flex-col items-center">
                          <img src="${game.pegi_rating_cover_url}" alt="PEGI Rating" class="h-12 w-auto">
                          <span class="text-xs text-gray-400 mt-1">EU</span>
                        </div>` : ''}
                    </div>` : ''}
                    
                  <!-- Language Support Section -->
                  ${game.language_support && Object.keys(game.language_support).length > 0 ? `
                    <div class="mt-4 p-2 bg-gray-900 rounded-lg">
                      <button id="toggleLanguageSupport" class="flex justify-between items-center w-full text-left">
                        <h4 class="text-sm font-semibold">Supported Languages</h4>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transform transition-transform language-dropdown-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                      </button>
                      <div id="languageSupportContent" class="mt-2 overflow-hidden transition-all duration-300 max-h-0">
                        <table class="w-full text-xs">
                          <thead>
                            <tr>
                              <th class="text-center pb-1 pr-2">Language</th>
                              ${Object.keys(game.language_support).map(supportType => {
                                // Use shorter header names
                                let shortName = supportType;
                                if (supportType === 'Interface') shortName = 'UI';
                                if (supportType === 'Subtitles') shortName = 'Sub';
                                return `<th class="text-center pb-1 px-1">${shortName}</th>`;
                              }).join('')}
                            </tr>
                          </thead>
                          <tbody>
                            ${(() => {
                              // Get all unique languages across all support types
                              const allLanguages = new Set();
                              Object.values(game.language_support).forEach(languages => {
                                languages.forEach(lang => allLanguages.add(lang.name));
                              });
                              
                              // Create rows for each language
                              return Array.from(allLanguages).map(langName => {
                                const row = `<tr class="border-t border-gray-800">
                                  <td class="py-1">${langName}</td>
                                  ${Object.values(game.language_support).map(langList => {
                                    const hasSupport = langList.some(lang => lang.name === langName);
                                    return `<td class="text-center py-1">${hasSupport ? '✓' : ''}</td>`;
                                  }).join('')}
                                </tr>`;
                                return row;
                              }).join('');
                            })()}
                          </tbody>
                        </table>
                      </div>
                    </div>
                  ` : ''}
                  
                  <!-- Time To Beat Section -->
                  ${game.time_to_beat ? `
                    <div class="mt-4 p-2 bg-gray-900 rounded-lg">
                      <h4 class="text-sm font-semibold mb-2 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Time To Beat
                      </h4>
                      <div class="flex justify-between mt-2 space-x-1">
                        ${game.time_to_beat.hastily_formatted ? `
                          <div class="flex-1 bg-purple-900 bg-opacity-40 rounded-lg p-2 text-center border border-purple-700">
                            <div class="text-xs text-gray-300">Main Story</div>
                            <div class="text-lg font-bold text-purple-300">${game.time_to_beat.hastily_formatted}</div>
                          </div>
                        ` : ''}
                        ${game.time_to_beat.normally_formatted ? `
                          <div class="flex-1 bg-blue-900 bg-opacity-40 rounded-lg p-2 text-center border border-blue-700">
                            <div class="text-xs text-gray-300">Main + Extras</div>
                            <div class="text-lg font-bold text-blue-300">${game.time_to_beat.normally_formatted}</div>
                          </div>
                        ` : ''}
                        ${game.time_to_beat.completely_formatted ? `
                          <div class="flex-1 bg-green-900 bg-opacity-40 rounded-lg p-2 text-center border border-green-700">
                            <div class="text-xs text-gray-300">Completionist</div>
                            <div class="text-lg font-bold text-green-300">${game.time_to_beat.completely_formatted}</div>
                          </div>
                        ` : ''}
                      </div>
                      ${game.time_to_beat.count ? `
                        <div class="text-xs text-gray-400 text-center mt-1">
                          Based on ${game.time_to_beat.count} player reports
                        </div>
                      ` : ''}
                    </div>
                  ` : ''}
                </div>
              </div>
              
              <!-- Game Details Column -->
              <div class="lg:flex-1 min-w-0 overflow-x-auto">
                <div class="flex justify-between items-start">
                  <h3 class="text-3xl font-bold mb-2">${game.name}</h3>
                  <span class="text-gray-400 text-sm">${game.formatted_release_date || (game.release_year ? game.release_year : 'Release date unknown')}</span>
                </div>
                
                ${game.alt_names && game.alt_names.length > 0 ? 
                  `<div class="mb-3">
                    <span class="text-sm text-gray-300">Also known as: </span>
                    <span class="badge alt-name-badge">${game.alt_names[0]}</span>
                  </div>` : ''}
                
                <div class="summary-container">
                  <p class="text-gray-300 my-4 summary-text summary-truncated">${game.summary || 'No description available.'}</p>
                  <span class="read-more-btn summary-read-more">Read More</span>
                </div>
                
                ${companyInfo}
                
                <div class="mt-4">
                  ${genreBadges ? `
                    <div class="mb-3">
                      <span class="text-sm font-semibold block mb-1">Genres:</span>
                      ${genreBadges}
                    </div>` : ''}
                  
                  ${platformBadges ? `
                    <div class="mb-3">
                      <span class="text-sm font-semibold block mb-1">Platforms:</span>
                      ${platformBadges}
                    </div>` : ''}
                  
                  ${themeBadges || modeBadges ? `
                    <div class="mb-3">
                      <span class="text-sm font-semibold block mb-1">Features:</span>
                      ${themeBadges}
                      ${modeBadges}
                    </div>` : ''}
                </div>
                
                ${storylineSection}
                ${screenshotGallery}
                ${addOnSection}
              </div>
            </div>
          </div>
        `;

        // Add event listener for the "Read More" button
        const readMoreBtn = mainGameCard.querySelector('.storyline-read-more');
        if (readMoreBtn) {
          readMoreBtn.addEventListener('click', function() {
            const storylineText = mainGameCard.querySelector('.storyline-text');
            if (storylineText.classList.contains('storyline-truncated')) {
              storylineText.classList.remove('storyline-truncated');
              storylineText.classList.add('storyline-full');
              readMoreBtn.textContent = 'Show Less';
            } else {
              storylineText.classList.remove('storyline-full');
              storylineText.classList.add('storyline-truncated');
              readMoreBtn.textContent = 'Read More';
            }
          });
        }

        // Add event listener for summary "Read More" button
        const summaryReadMoreBtn = mainGameCard.querySelector('.summary-read-more');
        if (summaryReadMoreBtn) {
          summaryReadMoreBtn.addEventListener('click', function() {
            const summaryText = mainGameCard.querySelector('.summary-text');
            if (summaryText.classList.contains('summary-truncated')) {
              summaryText.classList.remove('summary-truncated');
              summaryText.classList.add('summary-full');
              summaryReadMoreBtn.textContent = 'Show Less';
            } else {
              summaryText.classList.remove('summary-full');
              summaryText.classList.add('summary-truncated');
              summaryReadMoreBtn.textContent = 'Read More';
            }
          });
        }

        // Add event listener for franchise button
        const franchiseBtn = document.getElementById('franchiseButton');
        if (franchiseBtn && game.franchise_details) {
          franchiseBtn.addEventListener('click', function() {
            renderFranchiseTimeline(game.franchise_details);

            // Show the modal
            franchiseModal.classList.add('active');
            // Disable page scrolling when modal is open
            document.body.style.overflow = 'hidden';
          });
        }

        // Add event listener for language support toggle
        const toggleLanguageSupport = document.getElementById('toggleLanguageSupport');
        const languageSupportContent = document.getElementById('languageSupportContent');
        const languageDropdownIcon = document.querySelector('.language-dropdown-icon');
        if (toggleLanguageSupport && languageSupportContent) {
          toggleLanguageSupport.addEventListener('click', function() {
            const isExpanded = languageSupportContent.classList.contains('expanded');

            if (!isExpanded) {
              // Open the section
              languageSupportContent.classList.add('expanded');
              languageSupportContent.style.maxHeight = languageSupportContent.scrollHeight + 'px';
              languageDropdownIcon.style.transform = 'rotate(180deg)';
            } else {
              // Close the section
              languageSupportContent.classList.remove('expanded');
              languageSupportContent.style.maxHeight = '0';
              languageDropdownIcon.style.transform = 'rotate(0)';
            }
          });
        }
      }

      function renderSimilarGames(similarGames) {
        similarGamesContainer.innerHTML = '';

        similarGames.forEach(game => {
          // Simple rating display
          const rating = game.total_rating ? 
            game.total_rating.toFixed(1) : 
            (game.rating ? game.rating.toFixed(1) : 'N/A');

          // Genre badges (limited)
          const genreBadges = (game.genre_names || []).slice(0, 3)
            .map(genre => `<span class="badge genre-badge">${genre}</span>`)
            .join('');

          // Cover image with fallback
          const coverImage = game.cover && game.cover.url ? 
            `<img src="${game.cover.url}" alt="${game.name}" class="rounded-lg object-cover w-full h-56">` : 
            `<img src="assets/images/placeholder.png" alt="${game.name}" class="rounded-lg object-cover w-full h-56">`;

          // Create the card
          const gameCard = document.createElement('div');
          gameCard.className = "game-card p-4 bg-gray-800 rounded-lg shadow-lg";
          gameCard.innerHTML = `
            <div class="relative">
              ${coverImage}
              <div class="absolute top-2 right-2 rating-circle border-purple-500 w-10 h-10 text-sm">
                ${rating}
              </div>
              <div class="absolute top-2 left-2">
                <div class="heart-container" title="Favorite">
                  <input type="checkbox" class="checkbox favorite-checkbox" id="favorite-similar-${game.id}" 
                         data-game-id="${game.id}" 
                         data-name="${game.name}" 
                         data-cover-url="${game.cover && game.cover.url ? game.cover.url : ''}" 
                         data-summary="${game.summary || ''}" 
                         data-rating="${rating !== 'N/A' ? rating : ''}" 
                         data-release-date="${game.first_release_date || ''}">
                  <div class="svg-container">
                    <svg viewBox="0 0 24 24" class="svg-outline" xmlns="http://www.w3.org/2000/svg"><path d="M17.5,1.917a6.4,6.4,0,0,0-5.5,3.3,6.4,6.4,0,0,0-5.5-3.3A6.8,6.8,0,0,0,0,8.967c0,4.547,4.786,9.513,8.8,12.88a4.974,4.974,0,0,0,6.4,0C19.214,18.48,24,13.514,24,8.967A6.8,6.8,0,0,0,17.5,1.917Zm-3.585,18.4a2.973,2.973,0,0,1-3.83,0C4.947,16.006,2,11.87,2,8.967a4.8,4.8,0,0,1,4.5-5.05A4.8,4.8,0,0,1,11,8.967a1,1,0,0,0,2,0,4.8,4.8,0,0,1,4.5-5.05A4.8,4.8,0,0,1,22,8.967C22,11.87,19.053,16.006,13.915,20.313Z"></path></svg>
                    <svg viewBox="0 0 24 24" class="svg-filled" xmlns="http://www.w3.org/2000/svg"><path d="M17.5,1.917a6.4,6.4,0,0,0-5.5,3.3,6.4,6.4,0,0,0-5.5-3.3A6.8,6.8,0,0,0,0,8.967c0,4.547,4.786,9.513,8.8,12.88a4.974,4.974,0,0,0,6.4,0C19.214,18.48,24,13.514,24,8.967A6.8,6.8,0,0,0,17.5,1.917Z"></path></svg>
                    <svg class="svg-celebrate" width="100" height="100" xmlns="http://www.w3.org/2000/svg"><polygon points="10,10 20,20"></polygon><polygon points="10,50 20,50"></polygon><polygon points="20,80 30,70"></polygon><polygon points="90,10 80,20"></polygon><polygon points="90,50 80,50"></polygon><polygon points="80,80 70,70"></polygon></svg>
                  </div>
                </div>
              </div>
            </div>
            <h3 class="text-xl font-bold mt-3 mb-2">${game.name}</h3>
            <p class="text-gray-300 text-sm mb-3 line-clamp-3">${game.summary || 'No description available.'}</p>
            <div class="mt-auto">
              ${genreBadges}
            </div>
            ${game.stores && game.stores.length > 0 ? 
              `<a href="${game.stores[0]}" target="_blank" rel="noopener" class="block text-center mt-3 py-2 bg-purple-700 hover:bg-purple-600 rounded text-white text-sm transition">
                Get Game
              </a>` : ''}
          `;

          similarGamesContainer.appendChild(gameCard);
        });
      }

      function setupFavoriteCheckboxes() {
        document.querySelectorAll('.favorite-checkbox').forEach(checkbox => {
          const gameId = checkbox.dataset.gameId;

          // Check initial favorite status using the new PHP endpoint
          fetch(`api/get-favorites.php?game_id=${gameId}`)
            .then(response => response.json())
            .then(data => {
              checkbox.checked = data.is_favorite;
            })
            .catch(error => console.error('Error fetching favorite status:', error));

          // Add event listener for checkbox change
          checkbox.addEventListener('change', function() {
            const gameId = this.dataset.gameId;
            const gameName = this.dataset.name;
            const coverUrl = this.dataset.coverUrl;
            const summary = this.dataset.summary;
            const rating = this.dataset.rating;
            const releaseDate = this.dataset.releaseDate;

            // Toggle favorite status using the PHP endpoint
            fetch('api/toggle-favorite.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
              },
              body: JSON.stringify({
                game_id: gameId,
                name: gameName,
                cover_url: coverUrl,
                summary: summary,
                rating: rating,
                first_release_date: releaseDate
              })
            })
            .then(response => response.json())
            .then(data => {
              if (data.success) {
                console.log(data.added ? 'Added to favorites' : 'Removed from favorites');
              } else {
                console.error('Failed to toggle favorite:', data.error || 'Unknown error');
                this.checked = !this.checked; 
              }
            })
            .catch(error => {
              console.error('Error toggling favorite:', error);
              this.checked = !this.checked;
            });
          });
        });
      }

      function renderFranchiseTimeline(franchiseDetails) {
        // Update franchise name
        franchiseName.textContent = franchiseDetails.name;

        // Clear existing timeline content
        timelineContainer.innerHTML = '';

        // Get games sorted by release date
        const franchiseGames = franchiseDetails.games_details.filter(game => game.first_release_date);

        // Create timeline HTML
        franchiseGames.forEach((game, index) => {
          // Alternate left/right positioning
          const position = index % 2 === 0 ? 'timeline-left' : 'timeline-right';

          // Get release year
          const releaseYear = game.release_year || 'Unknown';

          // Game rating
          const rating = game.total_rating ? 
            game.total_rating.toFixed(1) : 
            (game.rating ? game.rating.toFixed(1) : '-');

          // Game cover image
          const coverImage = game.cover && game.cover.url ? 
            `<img src="${game.cover.url}" alt="${game.name}" class="franchise-cover">` : 
            `<img src="assets/images/placeholder.png" alt="${game.name}" class="franchise-cover">`;

          // Game type badge (Main Game, DLC, etc.)
          let categoryClass = 'category-main-game';
          if (game.type.includes('DLC') || game.type.includes('Add-on')) {
            categoryClass = 'category-dlc';
          } else if (game.type.includes('Expansion')) {
            categoryClass = 'category-expansion';
          }

          // Create timeline item
          const timelineItem = document.createElement('div');
          timelineItem.className = `timeline-item ${position}`;
          timelineItem.innerHTML = `
            <div class="timeline-content">
              <div class="timeline-year">${releaseYear}</div>
              ${coverImage}
              <div class="flex flex-col ${position === 'timeline-left' ? 'items-end' : 'items-start'}">
                <span class="category-pill ${categoryClass}">${game.type}</span>
                <h4 class="text-lg font-bold">${game.name}</h4>
                ${rating !== '-' ? `<div class="text-purple-300 mt-1">Rating: ${rating}</div>` : ''}
              </div>
            </div>
          `;

          // Add to timeline container
          timelineContainer.appendChild(timelineItem);
        });

        // Show the timeline section
        franchiseModal.classList.add('active');
      }
    });
