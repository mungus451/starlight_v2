document.addEventListener('DOMContentLoaded', function() {
    const detailsPane = document.getElementById('structure-details-pane');
    const detailsName = document.getElementById('details-name');
    const detailsDescription = document.getElementById('details-description');
    const detailsBody = document.getElementById('details-body');
    let currentActiveCard = null;

    document.querySelectorAll('.structure-card.interactive').forEach(card => {
        card.addEventListener('click', () => {
            // Remove active state from previous card
            if (currentActiveCard) {
                currentActiveCard.classList.remove('active');
            }
            // Add active state to current card
            card.classList.add('active');
            currentActiveCard = card;

            const dataset = card.dataset;
            populateDetails(dataset);
        });
    });

    function populateDetails(data) {
        detailsPane.style.display = 'block';
        detailsName.textContent = data.name;
        detailsDescription.textContent = data.description;

        let bodyHtml = `
            <div class="mb-3">
                <strong>Current Level:</strong> ${data.level}
            </div>
        `;

        if (data.isMaxLevel === '1') {
            bodyHtml += `
                <div class="alert alert-success text-center">
                    <i class="fas fa-check-circle"></i> Maximum Level Reached
                </div>
            `;
        } else {
            bodyHtml += `
                <div class="mb-3">
                    <strong>Next Upgrade Benefit:</strong><br>
                    <span class="text-neon-blue">${data.benefitText}</span>
                </div>
                <div class="mb-3">
                    <strong>Upgrade Cost:</strong><br>
                    <span class="${data.canAfford === '1' ? 'text-white' : 'text-danger'} font-weight-bold">
                        ${data.costFormatted}
                    </span>
                </div>
                <form action="/structures/upgrade" method="POST">
                    <input type="hidden" name="csrf_token" value="${document.querySelector('meta[name=csrf-token]').content}">
                    <input type="hidden" name="structure_key" value="${data.key}">
                    <button type="submit" class="btn btn-primary w-100" ${data.canAfford === '1' ? '' : 'disabled'}>
                        <i class="fas fa-hammer"></i> Upgrade Now
                    </button>
                </form>
            `;
        }

        detailsBody.innerHTML = bodyHtml;
    }
});
