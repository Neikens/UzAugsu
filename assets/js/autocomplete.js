// Autocomplete for Bible verses
const verseInput = document.getElementById('verseSearch');
const verseTextArea = document.getElementById('verseText');
const suggestionsList = document.getElementById('suggestions');

if (verseInput && suggestionsList) {
    let searchTimeout;
    let currentFocus = -1;

    // Handle input
    verseInput.addEventListener('input', function(e) {
        const query = e.target.value.trim();
        
        clearTimeout(searchTimeout);
        currentFocus = -1;
        
        if (query.length < 2) {
            suggestionsList.innerHTML = '';
            suggestionsList.style.display = 'none';
            return;
        }
        
        // Debounce search
        searchTimeout = setTimeout(async () => {
            try {
                const response = await fetch(`/api/search_verses.php?q=${encodeURIComponent(query)}`);
                const results = await response.json();
                
                displaySuggestions(results);
            } catch (error) {
                console.error('Autocomplete error:', error);
                suggestionsList.innerHTML = '';
                suggestionsList.style.display = 'none';
            }
        }, 300);
    });

    // Display suggestions
    function displaySuggestions(results) {
        suggestionsList.innerHTML = '';
        
        if (results.length === 0) {
            suggestionsList.style.display = 'none';
            return;
        }
        
        results.forEach((verse, index) => {
            const li = document.createElement('li');
            li.textContent = verse;
            li.dataset.index = index;
            li.dataset.reference = verse;
            
            li.addEventListener('click', async () => {
                verseInput.value = verse;
                suggestionsList.innerHTML = '';
                suggestionsList.style.display = 'none';
                
                // Fetch and fill verse text
                await fetchVerseText(verse);
                
                verseInput.focus();
            });
            
            suggestionsList.appendChild(li);
        });
        
        suggestionsList.style.display = 'block';
    }

    // Fetch verse text from API
    async function fetchVerseText(reference) {
        if (!verseTextArea) return;
        
        try {
            verseTextArea.value = 'Ielādē tekstu...';
            
            const response = await fetch(`/api/get_verse_text.php?reference=${encodeURIComponent(reference)}`);
            const data = await response.json();
            
            if (data.text) {
                verseTextArea.value = data.text;
            } else {
                verseTextArea.value = ''; // Clear if no text found
            }
        } catch (error) {
            console.error('Error fetching verse text:', error);
            verseTextArea.value = ''; // Clear on error
        }
    }

    // Keyboard navigation
    verseInput.addEventListener('keydown', function(e) {
        const items = suggestionsList.getElementsByTagName('li');
        
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            currentFocus++;
            addActive(items);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            currentFocus--;
            addActive(items);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (currentFocus > -1 && items[currentFocus]) {
                items[currentFocus].click();
            }
        } else if (e.key === 'Escape') {
            suggestionsList.innerHTML = '';
            suggestionsList.style.display = 'none';
        }
    });

    function addActive(items) {
        if (!items) return false;
        removeActive(items);
        
        if (currentFocus >= items.length) currentFocus = 0;
        if (currentFocus < 0) currentFocus = items.length - 1;
        
        items[currentFocus].classList.add('active');
    }

    function removeActive(items) {
        for (let i = 0; i < items.length; i++) {
            items[i].classList.remove('active');
        }
    }

    // Close suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (e.target !== verseInput) {
            suggestionsList.innerHTML = '';
            suggestionsList.style.display = 'none';
        }
    });
}