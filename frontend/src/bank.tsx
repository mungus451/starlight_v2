import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { BankIsland, type BankInitialState } from './components/BankIsland';
import './styles/bank-spa.css';

type BankWindow = Window & {
    __bankSpaMounted?: boolean;
};

const mountNode = document.getElementById('bank-spa-root');

function toInt(value: string | null, fallback = 0): number {
    const parsed = Number(value ?? '');
    return Number.isFinite(parsed) ? Math.trunc(parsed) : fallback;
}

function toFloat(value: string | null, fallback = 0): number {
    const parsed = Number(value ?? '');
    return Number.isFinite(parsed) ? parsed : fallback;
}

if (mountNode) {
    const bankWindow = window as BankWindow;
    const legacyRoot = document.getElementById('bank-legacy-root');

    const revealLegacyRoot = (): void => {
        if (legacyRoot) {
            legacyRoot.style.display = '';
        }
    };

    const fallbackTimeout = window.setTimeout(() => {
        if (!bankWindow.__bankSpaMounted) {
            revealLegacyRoot();
        }
    }, 1600);

    window.addEventListener(
        'bank-spa-mounted',
        () => {
            bankWindow.__bankSpaMounted = true;
            window.clearTimeout(fallbackTimeout);
        },
        { once: true },
    );

    const initialState: BankInitialState = {
        resources: {
            credits: toInt(mountNode.getAttribute('data-credits'), 0),
            banked_credits: toInt(mountNode.getAttribute('data-banked-credits'), 0),
        },
        stats: {
            deposit_charges: toInt(mountNode.getAttribute('data-deposit-charges'), 0),
            last_deposit_at: mountNode.getAttribute('data-last-deposit-at') || null,
        },
        bankConfig: {
            deposit_max_charges: toInt(mountNode.getAttribute('data-max-charges'), 4),
            deposit_charge_regen_hours: toFloat(mountNode.getAttribute('data-regen-hours'), 6),
            deposit_percent_limit: toFloat(mountNode.getAttribute('data-deposit-percent-limit'), 0.8),
        },
    };

    try {
        createRoot(mountNode).render(
            <StrictMode>
                <BankIsland initialState={initialState} />
            </StrictMode>,
        );
    } catch (error) {
        window.clearTimeout(fallbackTimeout);
        revealLegacyRoot();
        throw error;
    }
}