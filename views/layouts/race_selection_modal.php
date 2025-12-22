<!-- Race Selection Modal -->
<div id="race-selection-modal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 900px;">
        <div class="modal-header">
            <h2><i class="fas fa-meteor"></i> Select Your Race</h2>
            <p style="margin-top: 10px; opacity: 0.8;">Choose wisely - this decision is permanent and cannot be changed!</p>
        </div>
        
        <div class="modal-body">
            <div id="races-container" style="display: grid; gap: 15px;">
                <!-- Race cards will be loaded here via JavaScript -->
            </div>
            
            <div id="race-error" class="alert alert-error" style="display: none; margin-top: 15px;"></div>
        </div>
    </div>
</div>

<style>
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.modal-content {
    background: linear-gradient(145deg, #1a1a2e 0%, #16213e 100%);
    border-radius: 16px;
    box-shadow: 0 10px 50px rgba(0, 0, 0, 0.5);
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    border: 2px solid rgba(255, 255, 255, 0.1);
}

.modal-header {
    padding: 30px;
    border-bottom: 2px solid rgba(255, 255, 255, 0.1);
    text-align: center;
}

.modal-header h2 {
    font-size: 2em;
    margin: 0;
    color: #00d4ff;
    text-shadow: 0 0 20px rgba(0, 212, 255, 0.5);
}

.modal-body {
    padding: 30px;
}

.race-card {
    background: linear-gradient(145deg, #0f3460 0%, #16213e 100%);
    border: 2px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    padding: 20px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.race-card:hover {
    border-color: #00d4ff;
    box-shadow: 0 0 20px rgba(0, 212, 255, 0.3);
    transform: translateY(-3px);
}

.race-card.selected {
    border-color: #00ff88;
    box-shadow: 0 0 30px rgba(0, 255, 136, 0.5);
}

.race-card h3 {
    font-size: 1.5em;
    margin: 0 0 10px 0;
    color: #00d4ff;
}

.race-card .race-resource {
    font-weight: bold;
    color: #00ff88;
    margin-bottom: 10px;
    font-size: 1.1em;
}

.race-card .race-lore {
    font-style: italic;
    opacity: 0.8;
    margin-bottom: 10px;
    line-height: 1.5;
}

.race-card .race-uses {
    opacity: 0.9;
    line-height: 1.5;
}

.race-card .race-uses strong {
    color: #00d4ff;
}

.modal-footer {
    padding: 20px 30px;
    border-top: 2px solid rgba(255, 255, 255, 0.1);
    text-align: center;
}

.btn-confirm-race {
    background: linear-gradient(135deg, #00d4ff, #00ff88);
    color: #000;
    border: none;
    padding: 15px 40px;
    border-radius: 8px;
    font-size: 1.1em;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-confirm-race:hover:not(:disabled) {
    transform: scale(1.05);
    box-shadow: 0 0 30px rgba(0, 212, 255, 0.5);
}

.btn-confirm-race:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.alert {
    padding: 15px;
    border-radius: 8px;
    margin-top: 10px;
}

.alert-error {
    background: rgba(255, 0, 0, 0.2);
    border: 2px solid rgba(255, 0, 0, 0.5);
    color: #ff6b6b;
}

.alert-success {
    background: rgba(0, 255, 136, 0.2);
    border: 2px solid rgba(0, 255, 136, 0.5);
    color: #00ff88;
}
</style>

<script>
(function() {
    let selectedRaceId = null;
    let races = [];

    // Check if user needs to select a race
    async function checkRaceStatus() {
        try {
            const response = await fetch('/api/race/check');
            const data = await response.json();
            
            if (data.success && data.data.needs_selection) {
                loadRaces();
            }
        } catch (error) {
            console.error('Error checking race status:', error);
        }
    }

    // Load all races from the API
    async function loadRaces() {
        try {
            const response = await fetch('/api/races');
            const data = await response.json();
            
            if (data.success) {
                races = data.data;
                displayRaces();
                showModal();
            }
        } catch (error) {
            console.error('Error loading races:', error);
        }
    }

    // Display races in the modal
    function displayRaces() {
        const container = document.getElementById('races-container');
        container.innerHTML = '';
        
        races.forEach(race => {
            const card = document.createElement('div');
            card.className = 'race-card';
            card.dataset.raceId = race.id;
            
            card.innerHTML = `
                <h3><i class="fas fa-star"></i> ${escapeHtml(race.name)}</h3>
                <div class="race-resource">
                    <i class="fas fa-gem"></i> Exclusive Resource: ${escapeHtml(race.exclusive_resource)}
                </div>
                <div class="race-lore">
                    ${escapeHtml(race.lore)}
                </div>
                <div class="race-uses">
                    <strong>Uses:</strong> ${escapeHtml(race.uses)}
                </div>
            `;
            
            card.addEventListener('click', () => selectRace(race.id));
            container.appendChild(card);
        });
        
        // Add confirm button
        const footer = document.createElement('div');
        footer.className = 'modal-footer';
        footer.innerHTML = `
            <button id="confirm-race-btn" class="btn-confirm-race" disabled>
                <i class="fas fa-check"></i> Confirm Selection
            </button>
        `;
        container.parentElement.appendChild(footer);
        
        document.getElementById('confirm-race-btn').addEventListener('click', confirmRaceSelection);
    }

    // Select a race
    function selectRace(raceId) {
        selectedRaceId = raceId;
        
        // Update UI
        document.querySelectorAll('.race-card').forEach(card => {
            card.classList.remove('selected');
        });
        
        const selectedCard = document.querySelector(`.race-card[data-race-id="${raceId}"]`);
        if (selectedCard) {
            selectedCard.classList.add('selected');
        }
        
        // Enable confirm button
        document.getElementById('confirm-race-btn').disabled = false;
    }

    // Confirm race selection
    async function confirmRaceSelection() {
        if (!selectedRaceId) return;
        
        const btn = document.getElementById('confirm-race-btn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Confirming...';
        
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            
            const response = await fetch('/api/race/select', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    race_id: selectedRaceId,
                    csrf_token: csrfToken
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Show success message and reload page
                btn.innerHTML = '<i class="fas fa-check"></i> Success!';
                btn.style.background = 'linear-gradient(135deg, #00ff88, #00d4ff)';
                
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showError(data.message || 'Failed to select race');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check"></i> Confirm Selection';
            }
        } catch (error) {
            showError('An error occurred. Please try again.');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check"></i> Confirm Selection';
        }
    }

    // Show error message
    function showError(message) {
        const errorDiv = document.getElementById('race-error');
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
        
        setTimeout(() => {
            errorDiv.style.display = 'none';
        }, 5000);
    }

    // Show modal
    function showModal() {
        document.getElementById('race-selection-modal').style.display = 'flex';
    }

    // Helper function to escape HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Initialize on page load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', checkRaceStatus);
    } else {
        checkRaceStatus();
    }
})();
</script>
