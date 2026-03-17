import { useEffect, useState } from 'react';
import {
    fetchGlossaryData,
    type GlossaryData,
    type GlossaryStructure,
    type GlossaryUnit,
    type GlossaryArmoryLoadout,
    type GlossaryDirective,
    type GlossaryAllianceOp,
    type GlossaryResource,
} from '../lib/api';

type TabId = 'structures' | 'units' | 'armory' | 'directives' | 'theater-ops' | 'resources';

const TABS: { id: TabId; label: string }[] = [
    { id: 'structures', label: 'Structures' },
    { id: 'units', label: 'Units' },
    { id: 'armory', label: 'Armory' },
    { id: 'directives', label: 'Directives' },
    { id: 'theater-ops', label: 'Theater Ops' },
    { id: 'resources', label: 'Resources' },
];

const STORAGE_KEY = 'starlight_glossary_active_tab';

function getStoredTab(): TabId {
    try {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (stored && TABS.some((t) => t.id === stored)) {
            return stored as TabId;
        }
    } catch {
        // ignore storage errors
    }
    return 'structures';
}

function structureIcon(key: string): string {
    switch (key) {
        case 'planetary_shield': return 'fa-shield-alt';
        case 'economy_upgrade':
        case 'bank': return 'fa-coins';
        case 'population':
        case 'mercenary_outpost': return 'fa-users';
        case 'armory': return 'fa-cogs';
        case 'neural_uplink':
        case 'subspace_scanner': return 'fa-user-secret';
        default: return 'fa-building';
    }
}

function StructuresTab({ structures }: { structures: Record<string, GlossaryStructure> }) {
    return (
        <div className="structures-grid">
            {Object.entries(structures).map(([key, building]) => (
                <div key={key} className="structure-card">
                    <div className="card-header-main">
                        <div className="card-icon">
                            <i className={`fas ${structureIcon(key)}`}></i>
                        </div>
                        <div className="card-title-group">
                            <h3 className="card-title">{building.name}</h3>
                            <span className="card-category text-muted">{building.category ?? 'General'}</span>
                        </div>
                    </div>
                    <div className="card-body-main">
                        <p className="text-light mb-3" style={{ minHeight: 40, fontSize: '0.9em' }}>
                            {building.description}
                        </p>
                        <div className="stats-row" style={{ display: 'flex', justifyContent: 'space-between', fontSize: '0.85em', borderTop: '1px solid rgba(255,255,255,0.1)', paddingTop: 10 }}>
                            <div>
                                <span className="text-muted">Base Cost:</span><br />
                                <span className="text-warning">{building.base_cost.toLocaleString()}</span>
                            </div>
                            <div>
                                <span className="text-muted">Scale Factor:</span><br />
                                <span className="text-info">x{building.multiplier}</span>
                            </div>
                        </div>
                    </div>
                </div>
            ))}
        </div>
    );
}

function UnitsTab({ units }: { units: Record<string, GlossaryUnit> }) {
    return (
        <>
            <div className="structures-grid">
                {Object.entries(units).map(([key, unit]) => (
                    <div key={key} className="structure-card">
                        <div className="card-header-main">
                            <div className="card-icon">
                                <i className={`fas ${unit.icon}`}></i>
                            </div>
                            <div className="card-title-group">
                                <h3 className="card-title">{unit.name}</h3>
                                <span className="card-category text-muted">{unit.role}</span>
                            </div>
                        </div>
                        <div className="card-body-main">
                            <p className="text-light mb-3" style={{ minHeight: 40, fontSize: '0.9em' }}>
                                {unit.description}
                            </p>
                            <div className="stats-row" style={{ display: 'flex', justifyContent: 'space-between', fontSize: '0.85em', borderTop: '1px solid rgba(255,255,255,0.1)', paddingTop: 10 }}>
                                <div>
                                    <span className="text-muted">Credit Cost:</span><br />
                                    <span className="text-warning">{unit.credits.toLocaleString()}</span>
                                </div>
                                <div>
                                    <span className="text-muted">Citizens:</span><br />
                                    <span className="text-info">{unit.citizens.toLocaleString()}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                ))}
            </div>
            <div className="alert alert-info mt-4">
                <i className="fas fa-info-circle"></i> <strong>Note:</strong> Unit power scales with your Stats (Strength, Defense, etc.) and equipment from the Armory.
            </div>
        </>
    );
}

function ArmoryTab({ armory }: { armory: Record<string, GlossaryArmoryLoadout> }) {
    return (
        <div style={{ padding: 15 }}>
            {Object.entries(armory).map(([loadoutKey, loadout]) => (
                <div key={loadoutKey} className="mb-4">
                    <h3 className="text-neon-blue mb-3">{loadout.title}</h3>
                    {Object.entries(loadout.categories).map(([catKey, category]) => (
                        <div key={catKey} className="mb-4">
                            <h5 className="text-muted mb-2">{category.title}</h5>
                            <div className="structures-grid">
                                {Object.entries(category.items).map(([itemKey, item]) => (
                                    <div key={itemKey} className="structure-card">
                                        <div className="card-header-main">
                                            <div className="card-title-group">
                                                <h3 className="card-title">{item.name}</h3>
                                                {item.requires && (
                                                    <span className="card-category text-muted">Requires: {item.requires}</span>
                                                )}
                                            </div>
                                        </div>
                                        <div className="card-body-main">
                                            <p className="text-light mb-3" style={{ fontSize: '0.9em' }}>{item.description}</p>
                                            <div className="stats-row" style={{ display: 'flex', justifyContent: 'space-between', fontSize: '0.85em', borderTop: '1px solid rgba(255,255,255,0.1)', paddingTop: 10 }}>
                                                <div>
                                                    <span className="text-muted">Cost:</span><br />
                                                    <span className="text-warning">{item.cost_credits.toLocaleString()}</span>
                                                </div>
                                                {item.offense !== undefined && (
                                                    <div>
                                                        <span className="text-muted">Offense:</span><br />
                                                        <span className="text-danger">+{item.offense}</span>
                                                    </div>
                                                )}
                                                {item.defense !== undefined && (
                                                    <div>
                                                        <span className="text-muted">Defense:</span><br />
                                                        <span className="text-success">+{item.defense}</span>
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    ))}
                </div>
            ))}
        </div>
    );
}

function DirectivesTab({ directives }: { directives: Record<string, GlossaryDirective> }) {
    return (
        <>
            <div className="row mb-4">
                <div className="col-12">
                    <h3 className="text-neon-blue"><i className="fas fa-satellite-dish"></i> Alliance Command Directives</h3>
                    <p className="text-muted">Directives are strategic mandates set by Alliance Leaders to coordinate members toward a unified objective. Completing directives earns the alliance permanent Merit Badges.</p>
                </div>
            </div>
            <div className="structures-grid">
                {Object.entries(directives).map(([key, dir]) => (
                    <div key={key} className="structure-card">
                        <div className="card-header-main">
                            <div className="card-icon">
                                <i className={`fas ${dir.icon} text-neon-blue`}></i>
                            </div>
                            <div className="card-title-group">
                                <h3 className="card-title">{dir.name}</h3>
                                <span className="card-category text-muted">Command Directive</span>
                            </div>
                        </div>
                        <div className="card-body-main">
                            <p className="text-light mb-3" style={{ minHeight: 40, fontSize: '0.9em' }}>{dir.description}</p>
                            <div className="stats-row" style={{ display: 'flex', justifyContent: 'space-between', fontSize: '0.85em', borderTop: '1px solid rgba(255,255,255,0.1)', paddingTop: 10 }}>
                                <div>
                                    <span className="text-muted">Primary Goal:</span><br />
                                    <span className="text-info">{dir.goal}</span>
                                </div>
                                <div>
                                    <span className="text-muted">Merit Badge:</span><br />
                                    <span className="text-warning">{dir.badge}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                ))}
            </div>
            <div className="alert alert-info mt-4">
                <i className="fas fa-award"></i> <strong>Merit Badges:</strong> Badges upgrade visually as your alliance completes more directives of that type (Bronze → Silver → Gold → Platinum → Diamond → Starlight).
            </div>
        </>
    );
}

function TheaterOpsTab({ allianceOps }: { allianceOps: Record<string, GlossaryAllianceOp> }) {
    return (
        <>
            <div className="row mb-4">
                <div className="col-12">
                    <h3 className="text-neon-blue"><i className="fas fa-tasks"></i> Theater Operations</h3>
                    <p className="text-muted">Temporary, high-intensity missions that require active contributions from alliance members. Completing these operations grants powerful global buffs.</p>
                </div>
            </div>
            <div className="structures-grid">
                {Object.entries(allianceOps).map(([key, op]) => (
                    <div key={key} className="structure-card">
                        <div className="card-header-main">
                            <div className="card-icon">
                                <i className={`fas ${op.icon} text-neon-blue`}></i>
                            </div>
                            <div className="card-title-group">
                                <h3 className="card-title">{op.name}</h3>
                                <span className="card-category text-muted">Alliance Operation</span>
                            </div>
                        </div>
                        <div className="card-body-main">
                            <p className="text-light mb-3" style={{ minHeight: 40, fontSize: '0.9em' }}>{op.description}</p>
                            <div className="stats-row" style={{ display: 'flex', justifyContent: 'space-between', fontSize: '0.85em', borderTop: '1px solid rgba(255,255,255,0.1)', paddingTop: 10 }}>
                                <div>
                                    <span className="text-muted">Requirement:</span><br />
                                    <span className="text-warning">{op.requirement}</span>
                                </div>
                                <div>
                                    <span className="text-muted">Reward:</span><br />
                                    <span className="text-success">{op.reward}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                ))}
            </div>
            <div className="alert alert-info mt-4">
                <i className="fas fa-info-circle"></i> <strong>Alliance Energy (AE):</strong> Participating in operations generates Alliance Energy, which leaders can spend on Tactical Strikes against rival alliances.
            </div>
        </>
    );
}

function ResourcesTab({ resources }: { resources: Record<string, GlossaryResource> }) {
    return (
        <div className="row">
            {Object.entries(resources).map(([key, resource]) => (
                <div key={key} className="col-md-6 col-lg-4 mb-4">
                    <div className="card h-100" style={{ background: 'rgba(13, 17, 23, 0.95)', border: '1px solid rgba(255,255,255,0.1)' }}>
                        <div className="card-body text-center">
                            <div className="mb-3">
                                <i className={`fas ${resource.icon} fa-3x ${resource.color}`}></i>
                            </div>
                            <h4 className="card-title text-light mb-2">{resource.name}</h4>
                            <p className="card-text text-muted mb-4">{resource.description}</p>
                            <div className="text-start p-3 rounded" style={{ background: 'rgba(0,0,0,0.3)' }}>
                                <small className="text-uppercase text-muted" style={{ fontSize: '0.75em' }}>Primary Source</small>
                                <div className="text-light">{resource.source}</div>
                            </div>
                        </div>
                    </div>
                </div>
            ))}
        </div>
    );
}

export function GlossaryIsland() {
    const [activeTab, setActiveTab] = useState<TabId>(getStoredTab);
    const [data, setData] = useState<GlossaryData | null>(null);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        fetchGlossaryData()
            .then((d) => {
                setData(d);
                window.dispatchEvent(new Event('glossary-spa-mounted'));
            })
            .catch((err: unknown) => {
                setError(err instanceof Error ? err.message : 'Failed to load glossary data.');
            });
    }, []);

    const handleTabChange = (tab: TabId) => {
        setActiveTab(tab);
        try {
            localStorage.setItem(STORAGE_KEY, tab);
        } catch {
            // ignore storage errors
        }
    };

    if (error) {
        return (
            <div className="alert alert-danger mt-4">
                <i className="fas fa-exclamation-triangle"></i> {error}
            </div>
        );
    }

    if (!data) {
        return (
            <div style={{ textAlign: 'center', padding: '4rem', color: 'var(--muted)' }}>
                <i className="fas fa-spinner fa-spin fa-2x"></i>
            </div>
        );
    }

    return (
        <>
            <div id="glossary-top" className="row mb-4">
                <div className="col-12">
                    <h1 className="page-title text-center"><i className="fas fa-book-open text-neon-blue"></i> Game Glossary</h1>
                    <p className="text-center text-muted">A comprehensive database of Starlight Dominion technology and resources.</p>
                </div>
            </div>

            <div className="tabs-nav mb-4 justify-content-center">
                {TABS.map((tab) => (
                    <button
                        key={tab.id}
                        className={`tab-link${activeTab === tab.id ? ' active' : ''}`}
                        onClick={() => handleTabChange(tab.id)}
                        type="button"
                    >
                        {tab.label}
                    </button>
                ))}
            </div>

            <div className="tab-content active">
                {activeTab === 'structures' && <StructuresTab structures={data.structures} />}
                {activeTab === 'units' && <UnitsTab units={data.units} />}
                {activeTab === 'armory' && <ArmoryTab armory={data.armory} />}
                {activeTab === 'directives' && <DirectivesTab directives={data.directives} />}
                {activeTab === 'theater-ops' && <TheaterOpsTab allianceOps={data.allianceOps} />}
                {activeTab === 'resources' && <ResourcesTab resources={data.resources} />}
            </div>
        </>
    );
}
