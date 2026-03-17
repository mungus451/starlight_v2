import { useEffect, useState } from 'react';
import {
    fetchLeaderboardData,
    type AllianceLeaderboardRow,
    type LeaderboardApiResponse,
    type LeaderboardType,
    type PlayerLeaderboardRow,
} from '../lib/api';

export interface LeaderboardInitialState {
    type: LeaderboardType;
    page: number;
    sort: string;
}

const PLAYER_SORTS = [
    { key: 'level', label: 'Level' },
    { key: 'net_worth', label: 'Net Worth' },
    { key: 'overall_power', label: 'Overall' },
    { key: 'battles_won', label: 'Battles' },
    { key: 'prestige', label: 'Prestige' },
] as const;

const STAT_ICONS = {
    net_worth: 'fas fa-coins',
    population: 'fas fa-users',
    battles_won: 'fas fa-skull-crossbones',
    prestige: 'fas fa-trophy',
    overall_power: 'fas fa-shield-halved',
} as const;

function getUrl(type: LeaderboardType, page: number, sort: string): string {
    return `/leaderboard/${type}/${page}?sort=${encodeURIComponent(sort)}`;
}

function isPlayerRow(row: PlayerLeaderboardRow | AllianceLeaderboardRow): row is PlayerLeaderboardRow {
    return 'character_name' in row;
}

function formatNumber(value: number): string {
    return value.toLocaleString();
}

function podiumRows(data: LeaderboardApiResponse | null): Array<{ row: PlayerLeaderboardRow | AllianceLeaderboardRow; rank: number; className: string }> {
    if (!data || data.pagination.currentPage !== 1 || data.data.length < 3) {
        return [];
    }

    const top = data.data.slice(0, 3);
    return [
        top[1] ? { row: top[1], rank: 2, className: 'silver' } : null,
        top[0] ? { row: top[0], rank: 1, className: 'gold' } : null,
        top[2] ? { row: top[2], rank: 3, className: 'bronze' } : null,
    ].filter((value): value is { row: PlayerLeaderboardRow | AllianceLeaderboardRow; rank: number; className: string } => value !== null);
}

function remainingRows(data: LeaderboardApiResponse | null): Array<PlayerLeaderboardRow | AllianceLeaderboardRow> {
    if (!data) {
        return [];
    }

    if (data.pagination.currentPage === 1 && data.data.length >= 3) {
        return data.data.slice(3);
    }

    return data.data;
}

export function LeaderboardIsland({ initialState }: { initialState: LeaderboardInitialState }) {
    const [data, setData] = useState<LeaderboardApiResponse | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [request, setRequest] = useState<LeaderboardInitialState>(initialState);

    useEffect(() => {
        let cancelled = false;

        const load = async (): Promise<void> => {
            setLoading(true);
            setError(null);

            try {
                const next = await fetchLeaderboardData(request.type, request.page, request.sort);
                if (cancelled) {
                    return;
                }

                setData(next);
                window.dispatchEvent(new Event('leaderboard-spa-mounted'));
            } catch (err) {
                if (!cancelled) {
                    setError(err instanceof Error ? err.message : 'Failed to load leaderboard.');
                }
            } finally {
                if (!cancelled) {
                    setLoading(false);
                }
            }
        };

        void load();

        return () => {
            cancelled = true;
        };
    }, [request]);

    useEffect(() => {
        const onPopState = (): void => {
            const pathParts = window.location.pathname.split('/').filter(Boolean);
            const type = pathParts[1] === 'alliances' ? 'alliances' : 'players';
            const pageValue = Number(pathParts[2] ?? '1');
            const page = Number.isFinite(pageValue) && pageValue > 0 ? pageValue : 1;
            const sort = new URLSearchParams(window.location.search).get('sort') ?? 'net_worth';

            setRequest({ type, page, sort });
        };

        window.addEventListener('popstate', onPopState);
        return () => window.removeEventListener('popstate', onPopState);
    }, []);

    const navigate = (type: LeaderboardType, page: number, sort: string): void => {
        const nextRequest = { type, page, sort };
        window.history.pushState(nextRequest, '', getUrl(type, page, sort));
        setRequest(nextRequest);
    };

    const activeType = data?.type ?? request.type;
    const activeSort = data?.currentSort ?? request.sort;
    const pagination = data?.pagination;
    const displayPodium = podiumRows(data);
    const rows = remainingRows(data);

    return (
        <div className="structures-page-content" aria-live="polite">
            <div className="page-header-container">
                <h1 className="page-title-neon">Galactic Registry</h1>
                <p className="page-subtitle-tech">Universal Command Rankings // Alliance Standings</p>
                <div className="flex-center gap-2 mt-2">
                    <div className="badge bg-dark border-secondary">
                        Page {pagination?.currentPage ?? request.page} of {pagination?.totalPages ?? '...'}
                    </div>
                    <div className="badge bg-dark border-info">
                        Sorted By: {activeSort.replace(/_/g, ' ')}
                    </div>
                </div>
            </div>

            <div className="structure-nav-container mb-4">
                <a
                    href={getUrl('players', 1, activeType === 'players' ? activeSort : 'net_worth')}
                    className={`structure-nav-btn ${activeType === 'players' ? 'active' : ''}`}
                    onClick={(event) => {
                        event.preventDefault();
                        navigate('players', 1, activeType === 'players' ? activeSort : 'net_worth');
                    }}
                >
                    <i className="fas fa-user-astronaut"></i> Top Commanders
                </a>
                <a
                    href={getUrl('alliances', 1, 'net_worth')}
                    className={`structure-nav-btn ${activeType === 'alliances' ? 'active' : ''}`}
                    onClick={(event) => {
                        event.preventDefault();
                        navigate('alliances', 1, 'net_worth');
                    }}
                >
                    <i className="fas fa-flag"></i> Top Alliances
                </a>
            </div>

            {loading ? <p className="leaderboard-spa-status">Loading leaderboard...</p> : null}
            {error ? <div className="alert alert-danger">{error}</div> : null}

            {!loading && !error && displayPodium.length > 0 ? (
                <div className="podium-container mb-5">
                    {displayPodium.map((spot) => {
                        const avatarUrl = isPlayerRow(spot.row)
                            ? (spot.row.profile_picture_url ? `/serve/avatar/${spot.row.profile_picture_url}` : null)
                            : (spot.row.profile_picture_url ? `/serve/alliance_avatar/${spot.row.profile_picture_url}` : null);
                        const profileUrl = isPlayerRow(spot.row)
                            ? `/profile/${spot.row.id}`
                            : `/alliance/profile/${spot.row.id}`;
                        const name = isPlayerRow(spot.row) ? spot.row.character_name : spot.row.name;

                        return (
                            <div key={`${spot.rank}-${spot.row.id}`} className={`podium-card ${spot.className}`}>
                                <div className="rank-badge">#{spot.rank}</div>
                                <div className="podium-avatar-wrapper">
                                    {avatarUrl ? (
                                        <img src={avatarUrl} alt="Avatar" />
                                    ) : (
                                        <div className="avatar-placeholder"><i className="fas fa-user"></i></div>
                                    )}
                                </div>
                                <h4 className="podium-name"><a href={profileUrl}>{name}</a></h4>

                                {isPlayerRow(spot.row) ? (
                                    <>
                                        {spot.row.alliance_id ? (
                                            <div className="podium-alliance-link mb-3">
                                                <a href={`/alliance/profile/${spot.row.alliance_id}`} className="alliance-tag">
                                                    [{spot.row.alliance_tag ?? ''}]
                                                </a>
                                            </div>
                                        ) : (
                                            <div className="text-muted mb-3">No Alliance</div>
                                        )}

                                        <div className="podium-stats-grid">
                                            <div className="podium-stat">
                                                <i className="fas fa-star"></i>
                                                <span>Lvl {spot.row.level ?? 0}</span>
                                            </div>
                                            <div className="podium-stat">
                                                <i className={`${STAT_ICONS.net_worth} text-success`}></i>
                                                <span>{formatNumber(spot.row.net_worth)}</span>
                                            </div>
                                            <div className="podium-stat">
                                                <i className={`${STAT_ICONS.overall_power} text-warning`}></i>
                                                <span>{formatNumber(spot.row.overall_power)}</span>
                                            </div>
                                            <div className="podium-stat">
                                                <i className={`${STAT_ICONS.battles_won} text-danger`}></i>
                                                <span>{spot.row.battles_won}W / {spot.row.battles_lost}L</span>
                                            </div>
                                            <div className="podium-stat">
                                                <i className={`${STAT_ICONS.prestige} text-info`}></i>
                                                <span>{formatNumber(spot.row.war_prestige)}</span>
                                            </div>
                                        </div>
                                    </>
                                ) : (
                                    <div className="podium-stats-grid leaderboard-spa-single-col">
                                        <div className="podium-stat">
                                            <i className={`${STAT_ICONS.net_worth} text-success`}></i>
                                            <span>{formatNumber(spot.row.net_worth)}</span>
                                        </div>
                                        <div className="podium-stat">
                                            <i className={`${STAT_ICONS.population} text-info`}></i>
                                            <span>{formatNumber(spot.row.member_count)} Members</span>
                                        </div>
                                    </div>
                                )}
                            </div>
                        );
                    })}
                </div>
            ) : null}

            {!loading && !error ? (
                <div className="structure-card table-card">
                    <div className="card-header-main" style={{ background: 'rgba(0,0,0,0.4)' }}>
                        <div className="card-icon"><i className="fas fa-list-ol"></i></div>
                        <div className="card-title-group">
                            <span>Registry Data</span>
                            <h4>Rankings Registry</h4>
                        </div>
                    </div>

                    <div className="card-body-main p-0">
                        <div className="table-responsive">
                            <table className="registry-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Commander</th>
                                        {activeType === 'players' ? (
                                            <>
                                                <th>Alliance</th>
                                                {PLAYER_SORTS.map((sortOption) => (
                                                    <th key={sortOption.key} className={activeSort === sortOption.key ? 'active-sort' : ''}>
                                                        <a
                                                            href={getUrl(activeType, 1, sortOption.key)}
                                                            onClick={(event) => {
                                                                event.preventDefault();
                                                                navigate(activeType, 1, sortOption.key);
                                                            }}
                                                        >
                                                            {sortOption.label}
                                                        </a>
                                                    </th>
                                                ))}
                                            </>
                                        ) : (
                                            <>
                                                <th>Tag</th>
                                                <th>Members</th>
                                                <th className="active-sort">
                                                    <a
                                                        href={getUrl(activeType, 1, 'net_worth')}
                                                        onClick={(event) => {
                                                            event.preventDefault();
                                                            navigate(activeType, 1, 'net_worth');
                                                        }}
                                                    >
                                                        Net Worth
                                                    </a>
                                                </th>
                                            </>
                                        )}
                                    </tr>
                                </thead>
                                <tbody>
                                    {rows.length === 0 && displayPodium.length === 0 ? (
                                        <tr><td colSpan={10} className="text-center p-5 text-muted">No records found in this sector.</td></tr>
                                    ) : (
                                        rows.map((row) => {
                                            const avatarUrl = isPlayerRow(row)
                                                ? (row.profile_picture_url ? `/serve/avatar/${row.profile_picture_url}` : null)
                                                : (row.profile_picture_url ? `/serve/alliance_avatar/${row.profile_picture_url}` : null);
                                            const profileUrl = isPlayerRow(row) ? `/profile/${row.id}` : `/alliance/profile/${row.id}`;
                                            const name = isPlayerRow(row) ? row.character_name : row.name;

                                            return (
                                                <tr key={`${row.rank}-${row.id}`}>
                                                    <td><span className="rank-num">{row.rank}</span></td>
                                                    <td className="commander-cell">
                                                        <div className="mini-avatar">
                                                            {avatarUrl ? <img src={avatarUrl} alt="" /> : <i className="fas fa-user"></i>}
                                                        </div>
                                                        <div>
                                                            <a href={profileUrl} className="name-link">{name}</a>
                                                            {isPlayerRow(row) ? <span className="lvl-label">Lvl {row.level}</span> : null}
                                                        </div>
                                                    </td>

                                                    {isPlayerRow(row) ? (
                                                        <>
                                                            <td>
                                                                {row.alliance_id ? (
                                                                    <a href={`/alliance/profile/${row.alliance_id}`} className="alliance-tag">
                                                                        [{row.alliance_tag ?? ''}]
                                                                    </a>
                                                                ) : (
                                                                    <span className="text-muted">-</span>
                                                                )}
                                                            </td>
                                                            <td className="metric-cell">{row.level}</td>
                                                            <td className="metric-cell text-success">{formatNumber(row.net_worth)}</td>
                                                            <td className="metric-cell text-warning">{formatNumber(row.overall_power)}</td>
                                                            <td>
                                                                <span className="text-success">{row.battles_won}W</span>
                                                                <span className="text-muted">/</span>
                                                                <span className="text-danger">{row.battles_lost}L</span>
                                                            </td>
                                                            <td className="metric-cell text-neon-blue">{formatNumber(row.war_prestige)}</td>
                                                        </>
                                                    ) : (
                                                        <>
                                                            <td><span className="alliance-tag">[{row.tag}]</span></td>
                                                            <td className="metric-cell">{formatNumber(row.member_count)}</td>
                                                            <td className="metric-cell text-success">{formatNumber(row.net_worth)}</td>
                                                        </>
                                                    )}
                                                </tr>
                                            );
                                        })
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            ) : null}

            {!loading && !error && pagination && pagination.totalPages > 1 ? (
                <div className="pagination-v2 mt-4">
                    {pagination.currentPage > 1 ? (
                        <a
                            href={getUrl(activeType, pagination.currentPage - 1, activeSort)}
                            className="pag-btn"
                            onClick={(event) => {
                                event.preventDefault();
                                navigate(activeType, pagination.currentPage - 1, activeSort);
                            }}
                        >
                            <i className="fas fa-chevron-left"></i>
                        </a>
                    ) : (
                        <span className="pag-btn leaderboard-spa-disabled"><i className="fas fa-chevron-left"></i></span>
                    )}

                    <div className="pag-numbers">
                        {Array.from({ length: Math.min(pagination.totalPages, 5) }, (_, index) => {
                            const start = Math.max(1, pagination.currentPage - 2);
                            const end = Math.min(pagination.totalPages, pagination.currentPage + 2);
                            const page = start + index;

                            if (page > end) {
                                return null;
                            }

                            return (
                                <a
                                    key={page}
                                    href={getUrl(activeType, page, activeSort)}
                                    className={`pag-num ${page === pagination.currentPage ? 'active' : ''}`}
                                    onClick={(event) => {
                                        event.preventDefault();
                                        navigate(activeType, page, activeSort);
                                    }}
                                >
                                    {page}
                                </a>
                            );
                        })}
                    </div>

                    {pagination.currentPage < pagination.totalPages ? (
                        <a
                            href={getUrl(activeType, pagination.currentPage + 1, activeSort)}
                            className="pag-btn"
                            onClick={(event) => {
                                event.preventDefault();
                                navigate(activeType, pagination.currentPage + 1, activeSort);
                            }}
                        >
                            <i className="fas fa-chevron-right"></i>
                        </a>
                    ) : (
                        <span className="pag-btn leaderboard-spa-disabled"><i className="fas fa-chevron-right"></i></span>
                    )}
                </div>
            ) : null}
        </div>
    );
}