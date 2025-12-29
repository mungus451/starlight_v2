document.addEventListener('DOMContentLoaded', () => {
    // Initialize Tabs (Armory Pattern)
    initTabs();

    // State
    let charts = {};

    // --- Player Logic ---
    const playerSelect = document.getElementById('player-select');
    
    if (playerSelect) {
        playerSelect.addEventListener('change', (e) => {
            const playerId = e.target.value;
            if (playerId) {
                loadPlayerDossier(playerId);
            }
        });
    }

    function loadPlayerDossier(playerId) {
        fetch(`/almanac/get_player_dossier?player_id=${playerId}`)
            .then(res => {
                if (!res.ok) throw new Error('Failed to load dossier');
                return res.json();
            })
            .then(data => renderPlayerDossier(data))
            .catch(err => alert(err.message));
    }

    function renderPlayerDossier(data) {
        document.getElementById('player-dossier').classList.remove('d-none');
        
        const p = data.player;
        const s = data.stats;

        // Header
        document.getElementById('player-name').textContent = p.characterName;
        document.getElementById('player-bio').textContent = p.bio || "No biography available.";
        document.getElementById('player-joined').textContent = `Joined: ${new Date(p.createdAt).toLocaleDateString()}`;
        
        // Avatar Logic
        const avatarUrl = p.profile_picture_url 
            ? `/serve/avatar/${p.profile_picture_url}` 
            : 'https://via.placeholder.com/120?text=Pilot';
        document.getElementById('player-avatar').src = avatarUrl;

        // Records
        document.getElementById('record-plunder').textContent = parseInt(s.largest_plunder).toLocaleString();
        document.getElementById('record-deadliest').textContent = parseInt(s.deadliest_attack).toLocaleString();

        // Stats List
        document.getElementById('stat-total-battles').textContent = s.total_battles;
        document.getElementById('stat-wins').textContent = s.battles_won;
        document.getElementById('stat-losses').textContent = s.battles_lost;
        document.getElementById('stat-killed').textContent = parseInt(s.units_killed).toLocaleString();
        document.getElementById('stat-lost').textContent = parseInt(s.units_lost).toLocaleString();

        // Charts
        renderChart('player-wl-chart', 'doughnut', data.charts.win_loss.labels, data.charts.win_loss.datasets[0].data, data.charts.win_loss.datasets[0].backgroundColor);
        renderChart('player-kd-chart', 'pie', data.charts.units.labels, data.charts.units.datasets[0].data, data.charts.units.datasets[0].backgroundColor);
    }


    // --- Alliance Logic ---
    const allianceSelect = document.getElementById('alliance-select');

    if (allianceSelect) {
        allianceSelect.addEventListener('change', (e) => {
            const allianceId = e.target.value;
            if (allianceId) {
                loadAllianceDossier(allianceId);
            }
        });
    }

    function loadAllianceDossier(allianceId) {
        fetch(`/almanac/get_alliance_dossier?alliance_id=${allianceId}`)
            .then(res => res.json())
            .then(data => renderAllianceDossier(data))
            .catch(err => alert('Error loading alliance dossier'));
    }

    function renderAllianceDossier(data) {
        document.getElementById('alliance-dossier').classList.remove('d-none');
        
        const a = data.alliance;
        const s = data.stats;
        
        // Header
        document.getElementById('alliance-name').textContent = `${a.name} [${a.tag}]`;
        document.getElementById('alliance-desc').textContent = a.description || "No description.";
        document.getElementById('alliance-member-count').textContent = s.member_count;
        document.getElementById('alliance-wars').textContent = s.wars_participated;
        
        // Alliance Avatar Logic
        const allyAvatarUrl = a.profile_picture_url 
            ? `/serve/alliance_avatar/${a.profile_picture_url}` 
            : 'https://via.placeholder.com/150?text=Alliance';
        document.getElementById('alliance-avatar').src = allyAvatarUrl;

        // Stats
        document.getElementById('alliance-plunder').textContent = parseInt(s.total_plundered).toLocaleString();
        document.getElementById('alliance-wins').textContent = s.total_wins;
        document.getElementById('alliance-losses').textContent = s.total_losses;
        
        const total = s.total_wins + s.total_losses;
        // const ratio = total > 0 ? Math.round((s.total_wins / total) * 100) : 0; // Not used in view currently

        // Chart (Note: View ID is 'alliance-wl-chart', checking if data matches)
        // Check AlmanacService getAllianceDossier charts key.
        // It returns ['win_loss'].
        
        // Wait, View has `canvas id="alliance-wl-chart"`.
        // Service returns `charts => ['win_loss' => ...]`.
        if (data.charts.win_loss) {
            // Fix: Check if datasets[0] exists
             renderChart('alliance-wl-chart', 'doughnut', data.charts.win_loss.labels, data.charts.win_loss.datasets[0].data, data.charts.win_loss.datasets[0].backgroundColor);
        }

        // Roster
        const tbody = document.getElementById('alliance-roster');
        tbody.innerHTML = '';
        if (data.members.length === 0) {
            tbody.innerHTML = '<tr><td colspan="2" class="text-center text-muted">No members found</td></tr>';
        } else {
            data.members.forEach(m => {
                const tr = document.createElement('tr');
                const role = m.alliance_role_name || 'Member';
                
                // Member Avatar Logic
                const memberAvatarUrl = m.profile_picture_url 
                    ? `/serve/avatar/${m.profile_picture_url}` 
                    : 'https://via.placeholder.com/30';
                
                tr.innerHTML = `
                    <td>
                        <img src="${memberAvatarUrl}" class="rounded-circle me-2" width="30" height="30">
                        <a href="/profile/${m.id}" class="text-light text-decoration-none">${m.character_name}</a>
                    </td>
                    <td class="text-info">${role}</td>
                `;
                tbody.appendChild(tr);
            });
        }
    }

    // --- Tab Switching Logic (Matches Armory) ---
    function initTabs() {
        const links = document.querySelectorAll('.tab-link');
        const contents = document.querySelectorAll('.tab-content');

        links.forEach(link => {
            link.addEventListener('click', (e) => {
                // Deactivate all
                links.forEach(l => l.classList.remove('active'));
                contents.forEach(c => c.classList.remove('active'));
                
                // Activate clicked
                link.classList.add('active');
                
                // Activate content
                const targetId = link.dataset.tab;
                const targetContent = document.getElementById(targetId);
                if (targetContent) {
                    targetContent.classList.add('active');
                }
            });
        });
    }


    // --- Helpers ---

    function renderChart(canvasId, type, labels, dataPoints, colors) {
        const ctx = document.getElementById(canvasId).getContext('2d');
        
        if (charts[canvasId]) {
            charts[canvasId].destroy();
        }

        charts[canvasId] = new Chart(ctx, {
            type: type,
            data: {
                labels: labels,
                datasets: [{
                    data: dataPoints,
                    backgroundColor: colors,
                    borderColor: '#222',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: '#fff' }
                    }
                }
            }
        });
    }
});