document.addEventListener('DOMContentLoaded', function() {
    const stage1 = document.getElementById('war-council-stage-1');
    const stage2 = document.getElementById('war-council-stage-2');
    const targetIdInput = document.getElementById('target_alliance_id_input');
    const targetCards = document.querySelectorAll('.target-card');
    const targetHeader = document.getElementById('target-header');
    const targetAvatar = document.getElementById('target-avatar');
    const randomizeCasusBelliBtn = document.getElementById('randomize-casus-belli');
    const casusBelliInput = document.getElementById('casus-belli-input');
    
    // Comparison Bars (for AJAX data)
    const yourFleetBar = document.getElementById('your-fleet-bar');
    const targetFleetBar = document.getElementById('target-fleet-bar');

    const slideToDeclare = document.getElementById('slide-to-declare');
    const sliderHandle = document.getElementById('slider-handle');
    const sliderPathCleared = slideToDeclare.querySelector('.slider-path-cleared');

    let isSliding = false;
    let startX = 0;
    let currentTargetAlliance = null;

    const casusBelliPhrases = [
        "Violation of Sector 9 Treaty",
        "Unsanctioned Mining Operations in Border Zone",
        "Aggression Against Protectorate Colony",
        "Unauthorized Passage Through Our Star Lanes",
        "Pre-emptive Strike Against Imminent Threat",
        "Retaliation for Past Atrocities",
        "Resource Expropriation in Disputed Territory",
        "Espionage and Infiltration Detected",
        "Breach of Non-Aggression Pact"
    ];

    // --- STAGE 1: TARGET SELECTION ---
    targetCards.forEach(card => {
        card.addEventListener('click', function() {
            targetCards.forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');
            
            const allianceId = this.dataset.allianceId;
            const allianceTag = this.dataset.allianceTag;
            const allianceName = this.dataset.allianceName;
            const allianceAvatar = this.dataset.allianceAvatar;

            targetIdInput.value = allianceId;
            currentTargetAlliance = { id: allianceId, tag: allianceTag, name: allianceName, avatar: allianceAvatar };

            // Transition to Stage 2
            stage1.style.display = 'none';
            stage2.style.display = 'block';
            updateVersusScreen();
        });
    });

    // --- STAGE 2: VERSUS CONFIRMATION ---
    function updateVersusScreen() {
        if (!currentTargetAlliance) return;

        // Update Target Info
        targetHeader.innerHTML = `TARGET: [${currentTargetAlliance.tag}]`;
        targetAvatar.src = currentTargetAlliance.avatar;

        // TODO: Replace with actual AJAX call to fetch dynamic comparison data
        // For now, use dummy data
        const yourFleet = 750000;
        const targetFleet = 600000;
        const maxFleet = Math.max(yourFleet, targetFleet);

        yourFleetBar.style.width = `${(yourFleet / maxFleet) * 100}%`;
        targetFleetBar.style.width = `${(targetFleet / maxFleet) * 100}%`;

        // Reset slide-to-confirm
        resetSlideToConfirm();
    }

    randomizeCasusBelliBtn.addEventListener('click', function() {
        const randomIndex = Math.floor(Math.random() * casusBelliPhrases.length);
        casusBelliInput.value = casusBelliPhrases[randomIndex];
    });

    // --- SLIDE-TO-CONFIRM LOGIC ---
    sliderHandle.addEventListener('mousedown', startSlide);
    sliderHandle.addEventListener('touchstart', startSlide, { passive: true });

    function startSlide(e) {
        isSliding = true;
        startX = (e.touches ? e.touches[0].clientX : e.clientX) - sliderHandle.getBoundingClientRect().left;
        slideToDeclare.classList.add('active'); // Indicate active sliding

        document.addEventListener('mousemove', slide);
        document.addEventListener('mouseup', endSlide);
        document.addEventListener('touchmove', slide, { passive: true });
        document.addEventListener('touchend', endSlide);
    }

    function slide(e) {
        if (!isSliding) return;
        e.preventDefault(); // Prevent text selection etc.

        const clientX = e.touches ? e.touches[0].clientX : e.clientX;
        const containerRect = slideToDeclare.getBoundingClientRect();
        let newLeft = clientX - containerRect.left - startX;

        // Clamp position within bounds
        newLeft = Math.max(0, Math.min(newLeft, containerRect.width - sliderHandle.offsetWidth));
        sliderHandle.style.left = `${newLeft}px`;
        sliderPathCleared.style.width = `${newLeft + sliderHandle.offsetWidth / 2}px`;

        // Check if slid to end
        if (newLeft >= (containerRect.width - sliderHandle.offsetWidth - 5)) { // 5px tolerance
            // Successfully slid to declare war
            document.removeEventListener('mousemove', slide);
            document.removeEventListener('mouseup', endSlide);
            document.removeEventListener('touchmove', slide);
            document.removeEventListener('touchend', endSlide);
            
            isSliding = false;
            // Submit the form
            document.getElementById('declare-war-form').submit();
        }
    }

    function endSlide() {
        if (!isSliding) return;
        isSliding = false;
        slideToDeclare.classList.remove('active');
        // If not fully slid, snap back
        if (parseFloat(sliderHandle.style.left) < slideToDeclare.offsetWidth - sliderHandle.offsetWidth - 5) {
            sliderHandle.style.left = '5px';
            sliderPathCleared.style.width = '0';
        }

        document.removeEventListener('mousemove', slide);
        document.removeEventListener('mouseup', endSlide);
        document.removeEventListener('touchmove', slide);
        document.removeEventListener('touchend', endSlide);
    }

    function resetSlideToConfirm() {
        sliderHandle.style.left = '5px';
        sliderPathCleared.style.width = '0';
        isSliding = false;
    }

    // Initial setup for existing active wars to show timer
    const countdowns = document.querySelectorAll('.timer-countdown');
    function updateTimer(element) {
        const endTime = new Date(element.dataset.endTime + ' UTC').getTime();
        const now = new Date().getTime();
        const distance = endTime - now;

        if (distance < 0) {
            element.innerHTML = "WAR CONCLUDED";
            return;
        }

        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));

        element.innerHTML = days + "d " + hours + "h " + minutes + "m ";
    }

    countdowns.forEach(countdown => {
        updateTimer(countdown);
        // Update every 30 seconds for minute-level precision
        setInterval(() => updateTimer(countdown), 30000);
    });
});
