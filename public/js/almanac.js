document.addEventListener('DOMContentLoaded', () => {
    // Initialize Tabs (Advisor V2)
    initTabs();

    // State
    let charts = {};

    // --- Live Search Initialization ---
    
    // Player Search
    initAutocomplete({
        inputId: 'player-search-input',
        resultsId: 'player-search-results',
        endpoint: '/almanac/search_players',
        renderItem: (item) => {
            const avatarUrl = item.profile_picture_url 
                ? `/serve/avatar/${item.profile_picture_url}` 
                : '/img/default_avatar.png';
            return `
                <div class="result-item" data-id="${item.id}">
                    <img src="${avatarUrl}" class="result-avatar">
                    <div class="result-info">
                        <span class="result-name">${item.character_name}</span>
                        <span class="result-meta">Level ${item.level}</span>
                    </div>
                </div>
            `;
        },
        onSelect: (item) => {
            loadPlayerDossier(item.id);
        }
    });

    // Alliance Search
    initAutocomplete({
        inputId: 'alliance-search-input',
        resultsId: 'alliance-search-results',
        endpoint: '/almanac/search_alliances',
        renderItem: (item) => {
            const avatarUrl = item.profile_picture_url 
                ? `/serve/alliance_avatar/${item.profile_picture_url}` 
                : '/img/default_alliance.png';
            return `
                <div class="result-item" data-id="${item.id}">
                    <img src="${avatarUrl}" class="result-avatar">
                    <div class="result-info">
                        <span class="result-name">[${item.tag}] ${item.name}</span>
                        <span class="result-meta">${item.member_count} Members</span>
                    </div>
                </div>
            `;
        },
        onSelect: (item) => {
            loadAllianceDossier(item.id);
        }
    });


    // --- Core Logic ---

    function loadPlayerDossier(playerId) {
        // Hide previous results if any
        document.getElementById('player-search-results').classList.remove('active');
        
        fetch(`/almanac/get_player_dossier?player_id=${playerId}`)
            .then(res => {
                if (!res.ok) throw new Error('Failed to load dossier');
                return res.json();
            })
            .then(data => renderPlayerDossier(data))
            .catch(err => console.error(err));
    }

    function renderPlayerDossier(data) {
        document.getElementById('player-dossier').classList.remove('d-none');
        
        const p = data.player;
        const s = data.stats;

        // Header
        document.getElementById('player-name').textContent = p.characterName;
        document.getElementById('player-bio').textContent = p.bio || "No biography available.";
        document.getElementById('player-joined').textContent = `Joined: ${new Date(p.createdAt).toLocaleDateString()}`;
        
        const avatarUrl = p.profile_picture_url 
            ? `/serve/avatar/${p.profile_picture_url}` 
            : '/img/default_avatar.png';
        document.getElementById('player-avatar').src = avatarUrl;

        // Stats
        document.getElementById('record-plunder').textContent = parseInt(s.largest_plunder).toLocaleString();
        document.getElementById('record-deadliest').textContent = parseInt(s.deadliest_attack).toLocaleString();

        document.getElementById('stat-total-battles').textContent = s.total_battles;
        document.getElementById('stat-wins').textContent = s.battles_won;
        document.getElementById('stat-losses').textContent = s.battles_lost;
        document.getElementById('stat-killed').textContent = parseInt(s.units_killed).toLocaleString();
        document.getElementById('stat-lost').textContent = parseInt(s.units_lost).toLocaleString();
        document.getElementById('stat-lost-defensive').textContent = parseInt(s.units_lost_defending).toLocaleString();

        // Espionage
        document.getElementById('spy-missions-total').textContent = parseInt(s.spy_missions_total).toLocaleString();
        document.getElementById('spy-missions-success').textContent = parseInt(s.spy_missions_success).toLocaleString();
        document.getElementById('spy-lost').textContent = parseInt(s.spies_lost).toLocaleString();
        document.getElementById('sentry-killed').textContent = parseInt(s.enemy_sentries_killed).toLocaleString();
        document.getElementById('spy-intercepted').textContent = parseInt(s.spy_defenses_intercepted).toLocaleString();
        document.getElementById('enemy-spy-caught').textContent = parseInt(s.enemy_spies_caught).toLocaleString();
        document.getElementById('sentry-lost').textContent = parseInt(s.sentries_lost).toLocaleString();

        // Charts
        if (data.charts.win_loss) renderChart('player-wl-chart', 'doughnut', data.charts.win_loss);
        if (data.charts.units) renderChart('player-kd-chart', 'pie', data.charts.units);
        if (data.charts.casualty_breakdown) renderChart('player-casualty-chart', 'bar', data.charts.casualty_breakdown);
        if (data.charts.spy_success) renderChart('player-spy-chart', 'doughnut', data.charts.spy_success);
        if (data.charts.spy_kd) renderChart('player-spy-kd-chart', 'pie', data.charts.spy_kd);
    }

    function loadAllianceDossier(allianceId) {
        document.getElementById('alliance-search-results').classList.remove('active');

        fetch(`/almanac/get_alliance_dossier?alliance_id=${allianceId}`)
            .then(res => res.json())
            .then(data => renderAllianceDossier(data))
            .catch(err => console.error(err));
    }

    function renderAllianceDossier(data) {
        document.getElementById('alliance-dossier').classList.remove('d-none');
        
        const a = data.alliance;
        const s = data.stats;
        
        document.getElementById('alliance-name').textContent = `${a.name} [${a.tag}]`;
        document.getElementById('alliance-desc').textContent = a.description || "No description.";
        document.getElementById('alliance-member-count').textContent = s.member_count;
        document.getElementById('alliance-wars').textContent = s.wars_participated;
        
        const allyAvatarUrl = a.profile_picture_url 
            ? `/serve/alliance_avatar/${a.profile_picture_url}` 
            : '/img/default_alliance.png';
        document.getElementById('alliance-avatar').src = allyAvatarUrl;

        document.getElementById('alliance-plunder').textContent = parseInt(s.total_plundered).toLocaleString();
        document.getElementById('alliance-wins').textContent = s.total_wins;
        document.getElementById('alliance-losses').textContent = s.total_losses;
        
        if (data.charts.win_loss) renderChart('alliance-wl-chart', 'doughnut', data.charts.win_loss);

        // Roster
        const tbody = document.getElementById('alliance-roster');
        tbody.innerHTML = '';
        if (data.members.length === 0) {
            tbody.innerHTML = '<tr><td colspan="2" class="text-center text-muted">No members found</td></tr>';
        } else {
            data.members.forEach(m => {
                const tr = document.createElement('tr');
                const role = m.alliance_role_name || 'Member';
                const memberAvatarUrl = m.profile_picture_url 
                    ? `/serve/avatar/${m.profile_picture_url}` 
                    : '/img/default_avatar.png';
                
                tr.innerHTML = `
                    <td class="ps-4">
                        <a href="/profile/${m.id}" class="d-flex align-items-center text-decoration-none py-2 group-hover-glow">
                            <div class="mini-avatar me-3" style="width: 32px; height: 32px; border-radius: 4px; border: 1px solid var(--border); overflow: hidden; background: #000; display: flex; align-items: center; justify-content: center;">
                                <img src="${memberAvatarUrl}" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                            <span class="text-light fw-bold">${m.character_name}</span>
                        </a>
                    </td>
                    <td class="text-info font-08">${role}</td>
                `;
                tbody.appendChild(tr);
            });
        }
    }

    // --- Autocomplete Engine ---
    function initAutocomplete({ inputId, resultsId, endpoint, renderItem, onSelect }) {
        const input = document.getElementById(inputId);
        const results = document.getElementById(resultsId);
        let timeout = null;

        if (!input || !results) return;

        input.addEventListener('input', () => {
            const query = input.value.trim();
            clearTimeout(timeout);

            if (query.length < 2) {
                results.classList.remove('active');
                results.innerHTML = '';
                return;
            }

            timeout = setTimeout(() => {
                // Show loading state
                results.innerHTML = '<div class="p-3 text-center"><div class="spinner-sm mx-auto"></div></div>';
                results.classList.add('active');

                fetch(`${endpoint}?q=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.length === 0) {
                            results.innerHTML = '<div class="p-3 text-center text-muted">No matches found.</div>';
                            return;
                        }

                        results.innerHTML = '';
                        data.forEach(item => {
                            // Create HTML string from render function
                            const itemHtml = renderItem(item);
                            // Convert string to DOM element (wrapper)
                            const tempDiv = document.createElement('div');
                            tempDiv.innerHTML = itemHtml.trim();
                            const el = tempDiv.firstChild;

                            el.addEventListener('click', () => {
                                onSelect(item);
                                input.value = ''; // Clear input on selection
                                results.classList.remove('active');
                            });

                            results.appendChild(el);
                        });
                    })
                    .catch(() => {
                        results.innerHTML = '<div class="p-3 text-center text-danger">Error fetching results.</div>';
                    });
            }, 300); // 300ms debounce
        });

        // Close on outside click
        document.addEventListener('click', (e) => {
            if (!input.contains(e.target) && !results.contains(e.target)) {
                results.classList.remove('active');
            }
        });
    }

    // --- Tab Logic ---
    function initTabs() {
        const links = document.querySelectorAll('.tab-link, .structure-nav-btn');
        links.forEach(link => {
            link.addEventListener('click', () => {
                const targetId = link.dataset.tabTarget || link.dataset.tab;
                if (!targetId) return;

                links.forEach(l => l.classList.remove('active'));
                document.querySelectorAll('.tab-content, .structure-category-container').forEach(c => c.classList.remove('active'));
                
                link.classList.add('active');
                const target = document.getElementById(targetId);
                if (target) target.classList.add('active');
            });
        });
    }

    // --- Chart Helper ---
    function renderChart(canvasId, type, chartData) {
        const ctx = document.getElementById(canvasId);
        if(!ctx) return;
        
        if (charts[canvasId]) {
            charts[canvasId].destroy();
        }

        charts[canvasId] = new Chart(ctx.getContext('2d'), {
            type: type,
            data: {
                labels: chartData.labels,
                datasets: [{
                    data: chartData.datasets[0].data,
                    backgroundColor: chartData.datasets[0].backgroundColor,
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
                        labels: { color: '#fff', font: { size: 10 } }
                    }
                }
            }
        });
    }
});
