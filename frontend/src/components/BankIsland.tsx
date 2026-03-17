import { useCallback, useEffect, useMemo, useState } from 'react';
import {
    fetchBankData,
    postBankDeposit,
    postBankTransfer,
    postBankWithdraw,
    type BankApiResponse,
} from '../lib/api';

export type BankInitialState = BankApiResponse;

type Message = {
    kind: 'success' | 'error';
    text: string;
} | null;

function formatNumber(value: number): string {
    return value.toLocaleString();
}

function parseUtcDate(value: string | null): Date | null {
    if (!value) {
        return null;
    }

    const normalized = value.includes('T') ? value : value.replace(' ', 'T');
    const withZone = /Z$|[+-]\d\d:\d\d$/.test(normalized) ? normalized : `${normalized}Z`;
    const parsed = new Date(withZone);

    if (Number.isNaN(parsed.getTime())) {
        return null;
    }

    return parsed;
}

function formatDuration(ms: number): string {
    const totalSeconds = Math.max(0, Math.floor(ms / 1000));
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;

    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
}

export function BankIsland({ initialState }: { initialState: BankInitialState }) {
    const [bankData, setBankData] = useState<BankApiResponse>(initialState);
    const [loading, setLoading] = useState(false);
    const [submitting, setSubmitting] = useState<'deposit' | 'withdraw' | 'transfer' | null>(null);
    const [message, setMessage] = useState<Message>(null);

    const [depositAmount, setDepositAmount] = useState('');
    const [withdrawAmount, setWithdrawAmount] = useState('');
    const [transferAmount, setTransferAmount] = useState('');
    const [recipientName, setRecipientName] = useState('');

    const [countdownText, setCountdownText] = useState('--:--:--');

    const maxDeposit = useMemo(
        () => Math.floor(bankData.resources.credits * bankData.bankConfig.deposit_percent_limit),
        [bankData.resources.credits, bankData.bankConfig.deposit_percent_limit],
    );

    const refreshData = useCallback(async (): Promise<void> => {
        setLoading(true);
        try {
            const next = await fetchBankData();
            setBankData(next);
        } catch (error) {
            const text = error instanceof Error ? error.message : 'Failed to refresh bank data.';
            setMessage({ kind: 'error', text });
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        window.dispatchEvent(new Event('bank-spa-mounted'));
    }, []);

    useEffect(() => {
        const maxCharges = bankData.bankConfig.deposit_max_charges;
        const currentCharges = bankData.stats.deposit_charges;
        const lastDeposit = parseUtcDate(bankData.stats.last_deposit_at);

        if (currentCharges >= maxCharges) {
            setCountdownText('Full');
            return;
        }

        if (!lastDeposit) {
            setCountdownText('--:--:--');
            return;
        }

        const regenMs = bankData.bankConfig.deposit_charge_regen_hours * 60 * 60 * 1000;
        const nextChargeAt = new Date(lastDeposit.getTime() + regenMs);

        const tick = (): void => {
            const diff = nextChargeAt.getTime() - Date.now();
            if (diff <= 0) {
                setCountdownText('Ready!');
                void refreshData();
                return;
            }

            setCountdownText(formatDuration(diff));
        };

        tick();
        const interval = window.setInterval(() => {
            const diff = nextChargeAt.getTime() - Date.now();
            if (diff <= 0) {
                window.clearInterval(interval);
                setCountdownText('Ready!');
                void refreshData();
                return;
            }

            setCountdownText(formatDuration(diff));
        }, 1000);

        return () => window.clearInterval(interval);
    }, [bankData.bankConfig.deposit_charge_regen_hours, bankData.bankConfig.deposit_max_charges, bankData.stats.deposit_charges, bankData.stats.last_deposit_at, refreshData]);

    const handleDeposit = async (event: React.FormEvent<HTMLFormElement>): Promise<void> => {
        event.preventDefault();
        setMessage(null);

        const amount = Number(depositAmount);
        if (!Number.isFinite(amount) || amount <= 0) {
            setMessage({ kind: 'error', text: 'Enter a valid deposit amount.' });
            return;
        }

        setSubmitting('deposit');
        try {
            const response = await postBankDeposit(Math.trunc(amount));
            setMessage({ kind: 'success', text: response.message });
            setDepositAmount('');
            await refreshData();
        } catch (error) {
            const text = error instanceof Error ? error.message : 'Deposit failed.';
            setMessage({ kind: 'error', text });
        } finally {
            setSubmitting(null);
        }
    };

    const handleWithdraw = async (event: React.FormEvent<HTMLFormElement>): Promise<void> => {
        event.preventDefault();
        setMessage(null);

        const amount = Number(withdrawAmount);
        if (!Number.isFinite(amount) || amount <= 0) {
            setMessage({ kind: 'error', text: 'Enter a valid withdrawal amount.' });
            return;
        }

        setSubmitting('withdraw');
        try {
            const response = await postBankWithdraw(Math.trunc(amount));
            setMessage({ kind: 'success', text: response.message });
            setWithdrawAmount('');
            await refreshData();
        } catch (error) {
            const text = error instanceof Error ? error.message : 'Withdraw failed.';
            setMessage({ kind: 'error', text });
        } finally {
            setSubmitting(null);
        }
    };

    const handleTransfer = async (event: React.FormEvent<HTMLFormElement>): Promise<void> => {
        event.preventDefault();
        setMessage(null);

        const amount = Number(transferAmount);
        if (recipientName.trim() === '') {
            setMessage({ kind: 'error', text: 'Enter a recipient name.' });
            return;
        }

        if (!Number.isFinite(amount) || amount <= 0) {
            setMessage({ kind: 'error', text: 'Enter a valid transfer amount.' });
            return;
        }

        setSubmitting('transfer');
        try {
            const response = await postBankTransfer(recipientName.trim(), Math.trunc(amount));
            setMessage({ kind: 'success', text: response.message });
            setTransferAmount('');
            setRecipientName('');
            await refreshData();
        } catch (error) {
            const text = error instanceof Error ? error.message : 'Transfer failed.';
            setMessage({ kind: 'error', text });
        } finally {
            setSubmitting(null);
        }
    };

    const totalAssets = bankData.resources.credits + bankData.resources.banked_credits;
    const charges = bankData.stats.deposit_charges;
    const maxCharges = bankData.bankConfig.deposit_max_charges;
    const isDepositDisabled = submitting !== null || charges <= 0;

    return (
        <div className="structures-page-content bank-spa" aria-live="polite">
            <div className="page-header-container">
                <h1 className="page-title-neon">Interstellar Banking Clan</h1>
                <p className="page-subtitle-tech">Secure Asset Management // Imperial Treasury // Faction Banking</p>
                <div className="flex-center gap-2 mt-2">
                    <div className="badge bg-dark border-secondary">
                        <i className="fas fa-bolt text-warning"></i> Deposit Charges: {charges} / {maxCharges}
                    </div>
                </div>
            </div>

            {message ? (
                <div
                    className={`flash ${message.kind === 'success' ? 'flash-success' : 'flash-error'}`}
                    role="status"
                >
                    {message.text}
                </div>
            ) : null}

            {loading ? <p className="bank-spa-status">Refreshing bank data...</p> : null}

            <div className="structure-card mb-4 bank-spa-summary" style={{ borderColor: 'var(--accent-gold)' }}>
                <div className="card-body-main p-4 text-center">
                    <h2 className="text-uppercase text-muted font-08 mb-2">Total Liquid Assets</h2>
                    <div className="display-4 fw-bold text-light mb-3">
                        <i className="fas fa-coins text-warning me-2"></i>
                        {formatNumber(totalAssets)}
                    </div>

                    <div className="d-flex justify-content-center gap-4">
                        <div className="text-center">
                            <span className="d-block text-success font-08">SECURE (Banked)</span>
                            <span className="d-block fw-bold fs-5">{formatNumber(bankData.resources.banked_credits)}</span>
                        </div>
                        <div className="vr bg-secondary opacity-50"></div>
                        <div className="text-center">
                            <span className="d-block text-danger font-08">EXPOSED (On Hand)</span>
                            <span className="d-block fw-bold fs-5">{formatNumber(bankData.resources.credits)}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div className="structures-grid">
                <div className="structure-card">
                    <div className="card-header-main">
                        <span className="card-icon"><i className="fas fa-arrow-down"></i></span>
                        <div className="card-title-group">
                            <h3 className="card-title">Deposit</h3>
                            <p className="card-level text-muted">Secure your credits.</p>
                        </div>
                    </div>

                    <div className="card-body-main">
                        <form onSubmit={(event) => { void handleDeposit(event); }}>
                            <div className="mb-3">
                                <label htmlFor="bank-deposit-amount" className="text-muted font-07 text-uppercase mb-1">Amount (Max 80%)</label>
                                <div className="input-group">
                                    <input
                                        id="bank-deposit-amount"
                                        type="number"
                                        min={1}
                                        max={maxDeposit}
                                        inputMode="numeric"
                                        className="form-control bg-dark border-secondary text-light"
                                        placeholder="0"
                                        value={depositAmount}
                                        onChange={(event) => setDepositAmount(event.target.value)}
                                        required
                                    />
                                    <button
                                        type="button"
                                        className="btn btn-outline-info"
                                        onClick={() => setDepositAmount(String(maxDeposit))}
                                    >
                                        MAX
                                    </button>
                                </div>
                            </div>

                            <div className="mb-3">
                                <div className="d-flex justify-content-between font-07 text-muted mb-1">
                                    <span>Deposit Charges</span>
                                    <span className={countdownText === 'Full' || countdownText === 'Ready!' ? 'text-success' : 'text-warning'}>{countdownText}</span>
                                </div>
                                <div className="charge-meter d-flex gap-1" aria-hidden="true">
                                    {Array.from({ length: maxCharges }).map((_, index) => (
                                        <div key={index} className={`charge-cell ${index < charges ? 'active' : ''}`}></div>
                                    ))}
                                </div>
                            </div>

                            <button type="submit" className="btn btn-primary w-100" disabled={isDepositDisabled}>
                                <i className="fas fa-lock me-2"></i>
                                {charges <= 0 ? 'Recharging...' : 'Deposit Funds'}
                            </button>
                        </form>
                    </div>
                </div>

                <div className="structure-card">
                    <div className="card-header-main">
                        <span className="card-icon"><i className="fas fa-arrow-up"></i></span>
                        <div className="card-title-group">
                            <h3 className="card-title">Withdraw</h3>
                            <p className="card-level text-muted">Access liquid funds.</p>
                        </div>
                    </div>

                    <div className="card-body-main">
                        <form onSubmit={(event) => { void handleWithdraw(event); }}>
                            <div className="mb-3">
                                <label htmlFor="bank-withdraw-amount" className="text-muted font-07 text-uppercase mb-1">Amount</label>
                                <div className="input-group">
                                    <input
                                        id="bank-withdraw-amount"
                                        type="number"
                                        min={1}
                                        max={bankData.resources.banked_credits}
                                        inputMode="numeric"
                                        className="form-control bg-dark border-secondary text-light"
                                        placeholder="0"
                                        value={withdrawAmount}
                                        onChange={(event) => setWithdrawAmount(event.target.value)}
                                        required
                                    />
                                    <button
                                        type="button"
                                        className="btn btn-outline-info"
                                        onClick={() => setWithdrawAmount(String(bankData.resources.banked_credits))}
                                    >
                                        MAX
                                    </button>
                                </div>
                            </div>

                            <div className="alert alert-dark font-08 text-muted mb-3 border-secondary">
                                <i className="fas fa-info-circle me-1"></i> No fees or limits on withdrawals.
                            </div>

                            <button type="submit" className="btn btn-outline-warning w-100" disabled={submitting !== null}>
                                <i className="fas fa-unlock me-2"></i> Withdraw Funds
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <hr className="my-5 border-secondary" />

            <div id="bank-transfer">
                <div className="page-header-container mb-3">
                    <h2 className="page-title-neon smaller">Wire Transfer</h2>
                </div>

                <div className="structure-card mx-auto bank-spa-transfer-card">
                    <div className="card-header-main">
                        <span className="card-icon"><i className="fas fa-paper-plane"></i></span>
                        <div className="card-title-group">
                            <h3 className="card-title">Send Credits</h3>
                            <p className="card-level text-muted">Transfer to another commander.</p>
                        </div>
                    </div>

                    <div className="card-body-main">
                        <form onSubmit={(event) => { void handleTransfer(event); }}>
                            <div className="mb-3">
                                <label htmlFor="bank-transfer-recipient" className="text-muted font-07 text-uppercase mb-1">Recipient Name</label>
                                <input
                                    id="bank-transfer-recipient"
                                    type="text"
                                    className="form-control bg-dark border-secondary text-light"
                                    placeholder="Commander Name"
                                    value={recipientName}
                                    onChange={(event) => setRecipientName(event.target.value)}
                                    required
                                />
                            </div>

                            <div className="mb-3">
                                <label htmlFor="bank-transfer-amount" className="text-muted font-07 text-uppercase mb-1">Amount</label>
                                <input
                                    id="bank-transfer-amount"
                                    type="number"
                                    min={1}
                                    max={bankData.resources.credits}
                                    inputMode="numeric"
                                    className="form-control bg-dark border-secondary text-light"
                                    placeholder="0"
                                    value={transferAmount}
                                    onChange={(event) => setTransferAmount(event.target.value)}
                                    required
                                />
                            </div>

                            <div className="alert alert-dark font-08 text-warning mb-3 border-secondary">
                                <i className="fas fa-exclamation-triangle me-1"></i> Transfers are irreversible. Ensure the recipient name is correct.
                            </div>

                            <button type="submit" className="btn btn-primary w-100" disabled={submitting !== null}>
                                <i className="fas fa-check-circle me-2"></i> Initiate Transfer
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    );
}