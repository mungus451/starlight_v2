document.addEventListener('DOMContentLoaded', function() {
    const scoreboards = document.querySelectorAll('.war-score-dashboard');

    scoreboards.forEach(board => {
        const warId = board.dataset.warId;
        if (!warId) {
            console.warn('War ID not found for a scoreboard. Skipping its dynamic updates.');
            return;
        }

        async function updateWarScore() {
            try {
                const response = await fetch(`/alliance/war/${warId}/score`);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const data = await response.json();

                const { allianceA, allianceB, pointsA, pointsB, totalPointsA, totalPointsB } = data;
                
                // Update names and avatars
                const allianceANameEl = board.querySelector(`#alliance-a-name-${warId}`);
                if (allianceANameEl) {
                    const allianceAName = `[${allianceA.tag}] ${allianceA.name}`;
                    allianceANameEl.textContent = allianceAName;
                    allianceANameEl.dataset.text = allianceAName;
                }
                const allianceBNameEl = board.querySelector(`#alliance-b-name-${warId}`);
                if(allianceBNameEl) {
                    const allianceBName = `[${allianceB.tag}] ${allianceB.name}`;
                    allianceBNameEl.textContent = allianceBName;
                    allianceBNameEl.dataset.text = allianceBName;
                }
                
                const allianceAAvatarEl = board.querySelector(`#alliance-a-avatar-${warId}`);
                if(allianceAAvatarEl) allianceAAvatarEl.src = `/serve/alliance_avatar/${allianceA.profile_picture_url || 'default.png'}`;
                
                const allianceBAvatarEl = board.querySelector(`#alliance-b-avatar-${warId}`);
                if(allianceBAvatarEl) allianceBAvatarEl.src = `/serve/alliance_avatar/${allianceB.profile_picture_url || 'default.png'}`;

                // Update individual category scores and progress bars
                updateCategory('economy', pointsA.economy, pointsB.economy, warId, board);
                updateCategory('attack-offense', pointsA.attack_offense, pointsB.attack_offense, warId, board);
                updateCategory('spy-offense', pointsA.spy_offense, pointsB.spy_offense, warId, board);
                updateCategory('attack-defense', pointsA.attack_defense, pointsB.attack_defense, warId, board);
                updateCategory('spy-defense', pointsA.spy_defense, pointsB.spy_defense, warId, board);

                // Update total scores
                const totalAEl = board.querySelector(`#alliance-a-total-score-${warId}`);
                if(totalAEl) {
                    totalAEl.textContent = totalPointsA.toFixed(2);
                    totalAEl.dataset.text = totalPointsA.toFixed(2);
                }
                
                const totalBEl = board.querySelector(`#alliance-b-total-score-${warId}`);
                if(totalBEl) {
                    totalBEl.textContent = totalPointsB.toFixed(2);
                    totalBEl.dataset.text = totalPointsB.toFixed(2);
                }

            } catch (error) {
                console.error(`Failed to fetch war score for war ${warId}:`, error);
            }
        }

        // Initial update
        updateWarScore();

        // Update every 30 seconds
        setInterval(updateWarScore, 30000);
    });

    function updateCategory(categoryName, scoreA, scoreB, warId, board) {
        const totalCategoryPoints = (scoreA || 0) + (scoreB || 0);
        const percentA = totalCategoryPoints > 0 ? (scoreA / totalCategoryPoints) * 100 : 50;
        const percentB = 100 - percentA;

        const scoreAEl = board.querySelector(`#alliance-a-${categoryName}-score-${warId}`);
        if(scoreAEl) scoreAEl.textContent = (scoreA || 0).toFixed(2);
        
        const scoreBEl = board.querySelector(`#alliance-b-${categoryName}-score-${warId}`);
        if(scoreBEl) scoreBEl.textContent = (scoreB || 0).toFixed(2);

        const progressBarA = board.querySelector(`#${categoryName}-progress-a-${warId}`);
        const progressBarB = board.querySelector(`#${categoryName}-progress-b-${warId}`);

        if (progressBarA && progressBarB) {
            progressBarA.style.width = `${percentA}%`;
            progressBarA.setAttribute('aria-valuenow', percentA);
            progressBarB.style.width = `${percentB}%`;
            progressBarB.setAttribute('aria-valuenow', percentB);
        }
    }

    // War countdown timer logic (one for each board)
    document.querySelectorAll('.timer-countdown').forEach(countdownElement => {
        const endTime = new Date(countdownElement.dataset.endTime + ' UTC');

        function updateCountdown() {
            const now = new Date();
            const distance = endTime - now;

            if (distance < 0) {
                countdownElement.textContent = 'War Concluded!';
                // No need to clear interval here if it's outside the scope
                return;
            }

            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));

            countdownElement.textContent = `${days}d ${hours}h ${minutes}m`;
        }

        updateCountdown();
        setInterval(updateCountdown, 60000); // Update every minute
    });
});
