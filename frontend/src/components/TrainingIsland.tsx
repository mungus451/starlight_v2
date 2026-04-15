import { useCallback, useEffect, useMemo, useReducer, useState } from 'react';
import { fetchTrainingData, postTrainUnits, type TrainingApiResponse, type TrainingUnit } from '../lib/api';

export type TrainingInitialState = TrainingApiResponse;

type Message = {
    kind: 'success' | 'error';
    text: string;
} | null;

function formatNumber(value: number): string {
    return value.toLocaleString();
}

type AmountAction = { type: 'set'; key: string; value: number } | { type: 'reset' };

function amountsReducer(state: Record<string, number>, action: AmountAction): Record<string, number> {
    if (action.type === 'reset') return {};
    return { ...state, [action.key]: action.value };
}

function UnitCard({
    unit,
    amount,
    onChange,
    onMax,
}: {
    unit: TrainingUnit;
    amount: number;
    onChange: (key: string, value: string) => void;
    onMax: (unit: TrainingUnit) => void;
}) {
    return (
        <div className="content-box training-unit-card">
            <img
                src={`/img/${unit.name.toLowerCase()}.avif`}
                alt={`${unit.name} Icon`}
                className="icon"
            />
            <div className="details">
                <p className="training-unit-name">{unit.name}</p>
                <p className="training-unit-desc">{unit.desc}</p>
                <p className="training-unit-stat">Cost: {formatNumber(unit.credits)} Credits</p>
                <p className="training-unit-stat">Owned: {formatNumber(unit.owned)}</p>
            </div>
            <div className="actions">
                <input
                    type="number"
                    min="0"
                    placeholder="0"
                    value={amount || ''}
                    onChange={(e) => onChange(unit.key, e.target.value)}
                    className="training-input-field"
                />
                <button type="button" className="training-max-btn train" onClick={() => onMax(unit)}>
                    Max
                </button>
            </div>
        </div>
    );
}

export function TrainingIsland({ initialState }: { initialState: TrainingInitialState }) {
    const [trainingData, setTrainingData] = useState<TrainingApiResponse>(initialState);
    const [amounts, dispatch] = useReducer(amountsReducer, {});
    const [submitting, setSubmitting] = useState(false);
    const [message, setMessage] = useState<Message>(null);

    useEffect(() => {
        window.dispatchEvent(new Event('training-spa-mounted'));
    }, []);

    const totalCost = useMemo(
        () => trainingData.units.reduce((sum, unit) => sum + unit.credits * (amounts[unit.key] ?? 0), 0),
        [trainingData.units, amounts],
    );

    const totalCitizens = useMemo(
        () => trainingData.units.reduce((sum, unit) => sum + unit.citizens * (amounts[unit.key] ?? 0), 0),
        [trainingData.units, amounts],
    );

    const handleAmountChange = useCallback((key: string, value: string) => {
        const parsed = parseInt(value, 10);
        dispatch({ type: 'set', key, value: Number.isFinite(parsed) && parsed > 0 ? parsed : 0 });
    }, []);

    const handleMax = useCallback(
        (unit: TrainingUnit) => {
            const { credits, untrained_citizens } = trainingData.resources;
            const maxFromCredits = unit.credits > 0 ? Math.floor(credits / unit.credits) : 0;
            const maxFromCitizens = unit.citizens > 0 ? Math.floor(untrained_citizens / unit.citizens) : 0;
            dispatch({ type: 'set', key: unit.key, value: Math.max(0, Math.min(maxFromCredits, maxFromCitizens)) });
        },
        [trainingData.resources],
    );

    const handleSubmit = useCallback(
        async (e: React.FormEvent) => {
            e.preventDefault();
            const toTrain = Object.fromEntries(Object.entries(amounts).filter(([, v]) => v > 0));
            if (Object.keys(toTrain).length === 0) return;

            setSubmitting(true);
            setMessage(null);

            try {
                const result = await postTrainUnits(toTrain);
                setMessage({ kind: 'success', text: result.message });
                const next = await fetchTrainingData();
                setTrainingData(next);
                dispatch({ type: 'reset' });
            } catch (error) {
                const text = error instanceof Error ? error.message : 'Training failed. Please try again.';
                setMessage({ kind: 'error', text });
            } finally {
                setSubmitting(false);
            }
        },
        [amounts],
    );

    const canSubmit = !submitting && totalCost > 0;

    return (
        <div className="training-page-wrapper">
            <main className="training-main-content">
                {message && (
                    <div className={message.kind === 'success' ? 'flash-message-success' : 'flash-message-error'}>
                        {message.text}
                    </div>
                )}

                <div className="content-box training-header">
                    <div className="training-header-grid">
                        <div>
                            <p className="training-header-label">Citizens</p>
                            <p className="training-header-value">
                                {formatNumber(trainingData.resources.untrained_citizens - totalCitizens)}
                            </p>
                        </div>
                        <div>
                            <p className="training-header-label">Credits</p>
                            <p className="training-header-value">
                                {formatNumber(trainingData.resources.credits - totalCost)}
                            </p>
                        </div>
                        <div>
                            <p className="training-header-label">Total Cost</p>
                            <p className="text-lg font-bold text-yellow-400">{formatNumber(totalCost)}</p>
                        </div>
                        <div>
                            <p className="training-header-label">Total Refund</p>
                            <p className="text-lg font-bold text-green-400">0</p>
                        </div>
                    </div>
                </div>

                <div className="tabs-nav">
                    <a className="tab-link active">Train Units</a>
                </div>

                <form onSubmit={handleSubmit} className="training-form">
                    <div className="training-unit-grid">
                        {trainingData.units.map((unit) => (
                            <UnitCard
                                key={unit.key}
                                unit={unit}
                                amount={amounts[unit.key] ?? 0}
                                onChange={handleAmountChange}
                                onMax={handleMax}
                            />
                        ))}
                    </div>
                    <div className="content-box training-submit-container">
                        <button type="submit" disabled={!canSubmit} className="training-submit-btn train">
                            {submitting ? 'Training…' : 'Train All Selected Units'}
                        </button>
                    </div>
                </form>
            </main>
        </div>
    );
}
