import { useEffect, useMemo, useState } from 'react';
import { fetchProfileData, type ProfileApiResponse } from '../lib/api';

export type ProfileInitialState = ProfileApiResponse;

type Message = {
    kind: 'error';
    text: string;
} | null;

function formatNumber(value: number): string {
    return value.toLocaleString();
}

export function ProfileIsland({
    initialState,
    csrfToken,
    targetId,
}: {
    initialState: ProfileInitialState;
    csrfToken: string;
    targetId: number;
}) {
    const [profileData, setProfileData] = useState<ProfileApiResponse>(initialState);
    const [message, setMessage] = useState<Message>(null);
    const [attackModalOpen, setAttackModalOpen] = useState(false);
    const [spyModalOpen, setSpyModalOpen] = useState(false);
    const [attackTurns, setAttackTurns] = useState(1);

    useEffect(() => {
        window.dispatchEvent(new Event('profile-spa-mounted'));
    }, []);

    useEffect(() => {
        if (targetId <= 0) {
            return;
        }

        let cancelled = false;

        const refreshProfile = async (): Promise<void> => {
            try {
                const next = await fetchProfileData(targetId);
                if (!cancelled) {
                    setProfileData(next);
                }
            } catch (error) {
                if (!cancelled) {
                    const text = error instanceof Error ? error.message : 'Failed to load profile.';
                    setMessage({ kind: 'error', text });
                }
            }
        };

        void refreshProfile();

        return () => {
            cancelled = true;
        };
    }, [targetId]);

    const attackFormAction = useMemo(() => '/battle/attack', []);
    const spyFormAction = useMemo(() => '/spy/handle', []);
    const inviteFormAction = useMemo(() => `/alliance/invite/${profileData.profile.id}`, [profileData.profile.id]);

    return (
        <>
            <div className="container-full">
                <div className="dashboard-grid">
                    <div className="player-header">
                        <div className="player-info">
                            {profileData.profile.profile_picture_url ? (
                                <img
                                    src={`/serve/avatar/${profileData.profile.profile_picture_url}`}
                                    alt="Avatar"
                                    className="player-avatar"
                                />
                            ) : (
                                <svg className="player-avatar player-avatar-svg" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                    <path fillRule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clipRule="evenodd"></path>
                                </svg>
                            )}

                            <div>
                                <h2>{profileData.profile.character_name}</h2>
                                <span className="sub-text">
                                    {profileData.alliance ? (
                                        <a href={`/alliance/profile/${profileData.alliance.id}`}>
                                            [{profileData.alliance.tag}] {profileData.alliance.name}
                                        </a>
                                    ) : (
                                        'No Alliance'
                                    )}
                                </span>
                                <span className="sub-text" style={{ fontSize: '0.8rem' }}>
                                    Member Since: {profileData.profile.formatted_created_at}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div className="data-card grid-col-span-1">
                        <div className="card-header">
                            <h3>Public Stats</h3>
                        </div>
                        <ul className="card-stats-list">
                            <li><span>Level</span> <span>{profileData.stats.level}</span></li>
                            <li><span>Net Worth</span> <span>{formatNumber(profileData.stats.net_worth)}</span></li>
                            <li><span>War Prestige</span> <span>{formatNumber(profileData.stats.war_prestige)}</span></li>
                        </ul>
                    </div>

                    <div className="data-card grid-col-span-2">
                        <div className="card-header">
                            <h3>Commander Bio</h3>
                        </div>
                        <div style={{ fontSize: '0.95rem', color: 'var(--muted)', lineHeight: 1.6, whiteSpace: 'pre-wrap' }}>
                            {profileData.profile.bio.trim() !== '' ? profileData.profile.bio : 'This commander has not written a bio.'}
                        </div>
                    </div>

                    <div className="data-card grid-col-span-3">
                        <div className="card-header">
                            <h3>Actions</h3>
                        </div>
                        <div style={{ display: 'flex', gap: '1rem', flexWrap: 'wrap' }}>
                            <button
                                className="btn-submit btn-reject"
                                type="button"
                                style={{ width: 'auto', margin: 0 }}
                                onClick={() => setAttackModalOpen(true)}
                            >
                                Attack
                            </button>

                            <button
                                className="btn-submit btn-accent"
                                type="button"
                                style={{ width: 'auto', margin: 0 }}
                                onClick={() => setSpyModalOpen(true)}
                            >
                                Spy
                            </button>

                            {profileData.viewer.can_invite ? (
                                <form action={inviteFormAction} method="POST" style={{ margin: 0 }}>
                                    <input type="hidden" name="csrf_token" value={csrfToken} />
                                    <button type="submit" className="btn-submit btn-accept" style={{ width: 'auto', margin: 0 }}>
                                        Invite to Alliance
                                    </button>
                                </form>
                            ) : null}
                        </div>
                    </div>
                </div>
            </div>

            {message ? (
                <div className="flash-message-error" role="status">
                    {message.text}
                </div>
            ) : null}

            {attackModalOpen ? (
                <div className="modal-overlay active" id="attack-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="attack-modal-title">
                    <div className="modal-content">
                        <div className="modal-header">
                            <h3 id="attack-modal-title">Confirm Attack</h3>
                            <button className="modal-close-btn" type="button" aria-label="Close attack modal" onClick={() => setAttackModalOpen(false)}>
                                &times;
                            </button>
                        </div>
                        <form action={attackFormAction} method="POST">
                            <input type="hidden" name="csrf_token" value={csrfToken} />
                            <input type="hidden" name="attack_type" value="plunder" />
                            <input type="hidden" name="target_id" value={String(profileData.profile.id)} />

                            <div className="modal-summary">
                                Launch a full scale attack on <strong>{profileData.profile.character_name}</strong>?
                            </div>

                            <div className="mb-3">
                                <label htmlFor="attack_turns" className="form-label text-muted">Select Attack Turns (1-10):</label>
                                <select
                                    name="attack_turns"
                                    id="attack_turns"
                                    className="form-select bg-dark text-light border-secondary"
                                    value={attackTurns}
                                    onChange={(event) => setAttackTurns(Math.max(1, Math.min(10, Number(event.target.value))))}
                                >
                                    {Array.from({ length: 10 }).map((_, index) => {
                                        const turns = index + 1;
                                        return (
                                            <option key={turns} value={turns}>
                                                {turns} Turn(s)
                                            </option>
                                        );
                                    })}
                                </select>
                            </div>

                            <button type="submit" className="btn-submit btn-reject" style={{ width: '100%' }}>Launch Attack</button>
                        </form>
                    </div>
                </div>
            ) : null}

            {spyModalOpen ? (
                <div className="modal-overlay active" id="spy-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="spy-modal-title">
                    <div className="modal-content">
                        <div className="modal-header">
                            <h3 id="spy-modal-title">Confirm Espionage</h3>
                            <button className="modal-close-btn" type="button" aria-label="Close spy modal" onClick={() => setSpyModalOpen(false)}>
                                &times;
                            </button>
                        </div>
                        <form action={spyFormAction} method="POST">
                            <input type="hidden" name="csrf_token" value={csrfToken} />
                            <input type="hidden" name="target_id" value={String(profileData.profile.id)} />

                            <div className="modal-summary">
                                Deploy spies against <strong>{profileData.profile.character_name}</strong>?
                            </div>
                            <button type="submit" className="btn-submit btn-accent" style={{ width: '100%' }}>Launch Operation</button>
                        </form>
                    </div>
                </div>
            ) : null}
        </>
    );
}
