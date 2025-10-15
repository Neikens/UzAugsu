// Toggle pullups form
function showPullupsForm() {
    const form = document.getElementById('pullupsForm');
    if (form) {
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    }
}

// Toggle verse form
function showVerseForm() {
    const form = document.getElementById('verseForm');
    if (form) {
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    }
}

// Add pullups with real-time updates
document.getElementById('addPullups')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const messageDiv = document.getElementById('pullupMessage');
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const countInput = e.target.querySelector('input[name="count"]');
    
    submitBtn.disabled = true;
    submitBtn.textContent = 'Pievieno...';
    
    try {
        const response = await fetch('/api/add_pullups.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        console.log('Add pullups response:', data);
        
        if (data.success) {
            messageDiv.className = 'message success';
            messageDiv.textContent = data.message;
            
            // Reset form
            countInput.value = '';
            
            // Update stats immediately
            updatePullupStats(data);
            
            setTimeout(() => {
                messageDiv.textContent = '';
                messageDiv.className = '';
            }, 3000);
        } else {
            messageDiv.className = 'message error';
            messageDiv.textContent = data.message;
        }
    } catch (error) {
        console.error('Error adding pullups:', error);
        messageDiv.className = 'message error';
        messageDiv.textContent = 'Kļūda pievienojot pievilkšanās';
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Pievienot';
    }
});

// Update pullup stats in real-time
function updatePullupStats(data) {
    console.log('Updating pullup stats:', data);
    
    // Update "Kopā km" (all-time total) - FIRST stat card
    const totalValue = document.querySelector('.stat-card:first-child .stat-value');
    if (totalValue && data.all_time_total !== undefined) {
        console.log('Updating Kopā to:', data.all_time_total);
        totalValue.textContent = new Intl.NumberFormat('lv-LV').format(data.all_time_total);
    }
    
    // Update "Šodien" (today's total) - FIRST stat card sub-stat
    const todaySubStat = document.querySelector('.stat-card:first-child .stat-sub');
    if (todaySubStat && data.today_total !== undefined) {
        console.log('Updating Šodien to:', data.today_total);
        todaySubStat.textContent = `Šodien: ${new Intl.NumberFormat('lv-LV').format(data.today_total)} pievilkšanās`;
    }
}

// Add verse with real-time updates
document.getElementById('addVerse')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const messageDiv = document.getElementById('verseMessage');
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const referenceInput = document.getElementById('verseSearch');
    const textInput = document.getElementById('verseText');
    
    submitBtn.disabled = true;
    submitBtn.textContent = 'Pievieno...';
    
    try {
        const response = await fetch('/api/add_verse.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        console.log('Add verse response:', data);
        
        if (data.success) {
            messageDiv.className = 'message success';
            messageDiv.textContent = data.message;
            
            // Reset form
            referenceInput.value = '';
            textInput.value = '';
            
            // Clear suggestions
            const suggestionsList = document.getElementById('suggestions');
            if (suggestionsList) {
                suggestionsList.innerHTML = '';
                suggestionsList.style.display = 'none';
            }
            
            // Update verse stats
            updateVerseStats(data);
            
            setTimeout(() => {
                messageDiv.textContent = '';
                messageDiv.className = '';
            }, 3000);
        } else {
            messageDiv.className = 'message error';
            messageDiv.textContent = data.message || 'Kļūda pievienojot pantu';
        }
    } catch (error) {
        console.error('Error adding verse:', error);
        messageDiv.className = 'message error';
        messageDiv.textContent = 'Kļūda pievienojot pantu';
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Pievienot pantu';
    }
});

// Update verse stats in real-time
function updateVerseStats(data) {
    console.log('Updating verse stats:', data);
    
    // Update total days with verses (main number) - SECOND stat card
    const verseValue = document.querySelector('.stat-card:nth-child(2) .stat-value');
    if (verseValue && data.total_days !== undefined) {
        console.log('Updating total days to:', data.total_days);
        verseValue.textContent = data.total_days;
    }
    
    // Update today's count - SECOND stat card sub-stat
    const verseSubStat = document.querySelector('.stat-card:nth-child(2) .stat-sub');
    if (verseSubStat) {
        // Use today_count from API response
        const todayCount = data.today_count !== undefined ? data.today_count : (data.is_first ? 1 : 2);
        console.log('Updating today verse count to:', todayCount);
        verseSubStat.textContent = `Šodien: ${todayCount} panti`;
    }
}

// Random verse button
async function getRandomVerse() {
    const referenceInput = document.getElementById('verseSearch');
    const textInput = document.getElementById('verseText');
    const randomBtn = document.querySelector('button[onclick="getRandomVerse()"]');
    
    if (randomBtn) {
        randomBtn.disabled = true;
        randomBtn.textContent = '⏳ Ielādē...';
    }
    
    try {
        const response = await fetch('/api/random_verse.php');
        const data = await response.json();
        
        console.log('Random verse response:', data);
        
        if (data.reference) {
            referenceInput.value = data.reference;
            if (data.text) {
                textInput.value = data.text;
            }
        }
    } catch (error) {
        console.error('Error getting random verse:', error);
        alert('Kļūda ielādējot pantu');
    } finally {
        if (randomBtn) {
            randomBtn.disabled = false;
            randomBtn.textContent = '🎲 Nejauši izvēlēties';
        }
    }
}

// Confirm before logout
document.querySelectorAll('a[href="/api/logout.php"]').forEach(link => {
    link.addEventListener('click', (e) => {
        if (!confirm('Vai tiešām vēlies iziet?')) {
            e.preventDefault();
        }
    });
});