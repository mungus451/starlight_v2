import { useEffect, useMemo, useState } from 'react';
import {
    fetchStructuresData,
    postStructureUpgrade,
    type StructureItem,
    type StructuresApiResponse,
} from '../lib/api';

export type StructuresInitialState = StructuresApiResponse;

type Message = {
    kind: 'success' | 'error';
    text: string;
} | null;

function formatNumber(value: number): string {
    return value.toLocaleString();
}

function findStructureByKey(
    categories: Record<string, StructureItem[]>,
    structureKey: string | null,
): { categoryName: string; structure: StructureItem } | null {
    if (!structureKey) {
        return null;
    }

    for (const [categoryName, structures] of Object.entries(categories)) {
        const structure = structures.find((candidate) => candidate.key === structureKey);
        if (structure) {
            return { categoryName, structure };
        }
    }

    return null;
}

export function StructuresIsland({ initialState }: { initialState: StructuresInitialState }) {
    const [structuresData, setStructuresData] = useState<StructuresApiResponse>(initialState);
    const [selectedKey, setSelectedKey] = useState<string | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const [loading, setLoading] = useState(false);
    const [message, setMessage] = useState<Message>(null);

    useEffect(() => {
        window.dispatchEvent(new Event('structures-spa-mounted'));
    }, []);

    const selectedEntry = useMemo(
        () => findStructureByKey(structuresData.categories, selectedKey),
        [selectedKey, structuresData.categories],
    );

    const refreshData = async (): Promise<void> => {
        setLoading(true);
        try {
            const next = await fetchStructuresData();
            setStructuresData(next);
        } catch (error) {
            const text = error instanceof Error ? error.message : 'Failed to refresh structures data.';
            setMessage({ kind: 'error', text });
        } finally {
            setLoading(false);
        }
    };

    const handleUpgrade = async (): Promise<void> => {
        if (!selectedEntry || submitting || selectedEntry.structure.is_max_level) {
            return;
        }

        setSubmitting(true);
        setMessage(null);

        try {
            const result = await postStructureUpgrade(selectedEntry.structure.key);
            setMessage({ kind: 'success', text: result.message });
            await refreshData();
        } catch (error) {
            const text = error instanceof Error ? error.message : 'Structure upgrade failed.';
            setMessage({ kind: 'error', text });
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <div className="structures-page-content structures-spa" aria-live="polite">
            <div className="page-header-container">
                <h1 className="page-title-neon">Strategic Structures</h1>
                <p className="page-subtitle-tech">Construct and enhance your imperial infrastructure.</p>
                <div className="flex-center gap-2 mt-2">
                    <div className="badge bg-dark border-secondary">
                        Available Credits: {formatNumber(structuresData.resources.credits)}
                    </div>
                </div>
            </div>

            {message ? (
                <div
                    className={message.kind === 'success' ? 'structures-spa-flash structures-spa-flash-success' : 'structures-spa-flash structures-spa-flash-error'}
                    role="status"
                >
                    {message.text}
                </div>
            ) : null}

            {loading ? <p className="structures-spa-status">Refreshing structures data...</p> : null}

            <div className="structures-two-column-layout">
                <div className="requisition-grid" role="list" aria-label="Structure categories">
                    {Object.entries(structuresData.categories).map(([categoryName, structures]) => (
                        <div key={categoryName} className="structures-spa-category">
                            <h2 className="category-header">{categoryName}</h2>
                            {structures.map((structure) => {
                                const isSelected = selectedKey === structure.key;

                                return (
                                    <button
                                        key={structure.key}
                                        type="button"
                                        className={`unit-row interactive structures-spa-row ${structure.is_max_level ? 'max-level' : ''} ${isSelected ? 'active' : ''}`}
                                        onClick={() => setSelectedKey(structure.key)}
                                        aria-pressed={isSelected}
                                        aria-describedby={`structure-meta-${structure.key}`}
                                    >
                                        <span className="unit-icon-box" aria-hidden="true" dangerouslySetInnerHTML={{ __html: structure.icon }} />

                                        <span className="unit-info">
                                            <span className="structures-spa-name">{structure.name}</span>
                                            <span className="meta" id={`structure-meta-${structure.key}`}>
                                                Level: {structure.current_level} / {structure.max_level}
                                            </span>
                                        </span>

                                        <span className="unit-controls">
                                            {structure.is_max_level ? (
                                                <span className="badge bg-success">MAX</span>
                                            ) : !structure.can_afford ? (
                                                <span className="badge bg-danger">INSUFFICIENT</span>
                                            ) : (
                                                <span className="cost-preview text-warning">
                                                    {formatNumber(structure.upgrade_cost_credits ?? 0)} Cr
                                                </span>
                                            )}
                                        </span>
                                    </button>
                                );
                            })}
                        </div>
                    ))}
                </div>

                <div className="tactical-inspector" aria-labelledby="structures-inspector-title">
                    <div className="inspector-header">
                        <h2 className="inspector-title" id="structures-inspector-title">
                            {selectedEntry ? selectedEntry.structure.name.toUpperCase() : 'SELECT STRUCTURE'}
                        </h2>
                    </div>

                    <div className="wireframe-container">
                        <div className="wireframe-placeholder">
                            <span
                                id="insp-icon"
                                aria-hidden="true"
                                dangerouslySetInnerHTML={{ __html: selectedEntry?.structure.icon ?? '' }}
                            />
                        </div>
                    </div>

                    <div className="inspector-body">
                        <p className="lore-text">
                            {selectedEntry
                                ? selectedEntry.structure.description
                                : 'Select a structure from the requisition grid to view details and manage construction.'}
                        </p>

                        {selectedEntry ? (
                            selectedEntry.structure.is_max_level ? (
                                <div className="alert alert-success text-center">
                                    <i className="fas fa-check-circle fa-2x mb-2"></i>
                                    <h3 className="alert-heading">MAXIMUM LEVEL REACHED</h3>
                                    <p>This structure has been fully upgraded.</p>
                                </div>
                            ) : (
                                <div>
                                    <div className="d-flex justify-content-between mb-2">
                                        <span className="text-muted">Current Level:</span>
                                        <strong>{selectedEntry.structure.current_level} / {selectedEntry.structure.max_level}</strong>
                                    </div>
                                    <div className="d-flex justify-content-between mb-3 structures-spa-benefit-row">
                                        <span className="text-muted">Next Level Benefit:</span>
                                        <strong className="text-success structures-spa-benefit-value">{selectedEntry.structure.benefit_text || 'No listed bonus'}</strong>
                                    </div>

                                    <hr className="border-secondary" />

                                    <h3 className="text-neon-blue">UPGRADE COST</h3>
                                    <div className="d-flex justify-content-between mb-3">
                                        <span className="text-muted">Credits:</span>
                                        <strong className="text-warning">
                                            {selectedEntry.structure.upgrade_cost_credits !== null
                                                ? `${formatNumber(selectedEntry.structure.upgrade_cost_credits)} Credits`
                                                : 'N/A'}
                                        </strong>
                                    </div>

                                    <button
                                        type="button"
                                        className="btn btn-primary w-100"
                                        disabled={submitting || !selectedEntry.structure.can_afford}
                                        onClick={() => { void handleUpgrade(); }}
                                    >
                                        <i className="fas fa-hammer"></i>{' '}
                                        {submitting
                                            ? 'Upgrading...'
                                            : selectedEntry.structure.can_afford
                                                ? 'Begin Construction'
                                                : 'Insufficient Funds'}
                                    </button>
                                </div>
                            )
                        ) : null}
                    </div>
                </div>
            </div>
        </div>
    );
}