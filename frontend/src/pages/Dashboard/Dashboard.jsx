import React, { useEffect, useState } from 'react';
import './Dashboard.css';

/**
 * Main Dashboard Component
 * Shows sync status, statistics, and recent syncs
 */
export default function Dashboard() {
  const [syncStatus, setSyncStatus] = useState(null);
  const [stats, setStats] = useState(null);
  const [recentSyncs, setRecentSyncs] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    fetchDashboardData();
    // Refresh every 30 seconds
    const interval = setInterval(fetchDashboardData, 30000);
    return () => clearInterval(interval);
  }, []);

  const fetchDashboardData = async () => {
    try {
      const token = localStorage.getItem('auth_token');
      if (!token) {
        setError('Not authenticated');
        return;
      }

      const [statusRes, statsRes, syncsRes] = await Promise.all([
        fetch('/api/dashboard/sync-status', {
          headers: { 'Authorization': `Bearer ${token}` },
        }),
        fetch('/api/dashboard/stats', {
          headers: { 'Authorization': `Bearer ${token}` },
        }),
        fetch('/api/dashboard/recent-syncs?limit=5', {
          headers: { 'Authorization': `Bearer ${token}` },
        }),
      ]);

      if (!statusRes.ok || !statsRes.ok || !syncsRes.ok) {
        throw new Error('Failed to fetch dashboard data');
      }

      const statusData = await statusRes.json();
      const statsData = await statsRes.json();
      const syncsData = await syncsRes.json();

      setSyncStatus(statusData);
      setStats(statsData);
      setRecentSyncs(syncsData.syncs || []);
      setError(null);
    } catch (err) {
      setError(err.message);
      console.error('Dashboard error:', err);
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="dashboard-container">
        <div className="loading-spinner">
          <div className="spinner"></div>
          <p>Loading dashboard...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="dashboard-container">
        <div className="error-message">
          <p>❌ {error}</p>
          <button onClick={fetchDashboardData}>Retry</button>
        </div>
      </div>
    );
  }

  return (
    <div className="dashboard-container">
      <div className="dashboard-header">
        <h1>📊 Sync Dashboard</h1>
        <button className="refresh-btn" onClick={fetchDashboardData}>
          🔄 Refresh
        </button>
      </div>

      {syncStatus && <SyncStatusSection data={syncStatus} />}
      {stats && <StatsSection data={stats} />}
      {recentSyncs.length > 0 && <RecentSyncsSection syncs={recentSyncs} />}
    </div>
  );
}

/**
 * Sync Status Section
 */
function SyncStatusSection({ data }) {
  const { summary, accounts } = data;

  return (
    <div className="dashboard-section">
      <h2>🔄 Sync Status</h2>

      <div className="summary-grid">
        <div className="summary-card">
          <div className="card-label">Total Accounts</div>
          <div className="card-value">{summary.total_accounts}</div>
          <div className="card-subtext">
            {summary.active_accounts} active
          </div>
        </div>

        <div className="summary-card">
          <div className="card-label">Total Counters</div>
          <div className="card-value">{summary.total_counters}</div>
          <div className="card-subtext">
            {summary.synced_counters} synced
          </div>
        </div>

        <div className="summary-card">
          <div className="card-label">Sync Status</div>
          <div className="card-value">{summary.sync_percentage}%</div>
          <div className="progress-bar">
            <div
              className="progress-fill"
              style={{ width: `${summary.sync_percentage}%` }}
            ></div>
          </div>
        </div>

        <div className="summary-card">
          <div className="card-label">Interval</div>
          <div className="card-value">{summary.sync_interval_minutes}m</div>
          <div className="card-subtext">between syncs</div>
        </div>
      </div>

      {summary.pending_counters > 0 && (
        <div className="alert alert-warning">
          ⏳ {summary.pending_counters} counter(s) pending first sync
        </div>
      )}

      {summary.overdue_counters > 0 && (
        <div className="alert alert-danger">
          🔴 {summary.overdue_counters} counter(s) overdue for sync
        </div>
      )}

      <div className="accounts-list">
        <h3>Accounts & Counters</h3>
        {accounts.map((account) => (
          <AccountCard key={account.id} account={account} />
        ))}
      </div>
    </div>
  );
}

/**
 * Account Card
 */
function AccountCard({ account }) {
  const [expanded, setExpanded] = useState(false);

  const statusIcon = account.revoked ? '❌' : '✅';
  const statusText = account.revoked ? 'REVOKED' : 'ACTIVE';

  return (
    <div className="account-card">
      <div
        className="account-header"
        onClick={() => setExpanded(!expanded)}
      >
        <div className="account-title">
          <span className="status-icon">{statusIcon}</span>
          <span>Account {account.id}</span>
          <span className="counter-count">
            ({account.counters.length} counters)
          </span>
        </div>
        <span className={`expand-icon ${expanded ? 'expanded' : ''}`}>
          ▼
        </span>
      </div>

      {expanded && (
        <div className="account-body">
          {account.counters.map((counter) => (
            <CounterItem key={counter.id} counter={counter} />
          ))}
        </div>
      )}
    </div>
  );
}

/**
 * Counter Item
 */
function CounterItem({ counter }) {
  const statusConfig = {
    synced: { icon: '✅', color: 'success', text: 'Synced' },
    pending: { icon: '⏳', color: 'warning', text: 'Pending' },
    overdue: { icon: '🔴', color: 'danger', text: 'Overdue' },
    inactive: { icon: '⏹️', color: 'disabled', text: 'Inactive' },
  };

  const config = statusConfig[counter.status] || statusConfig.pending;

  return (
    <div className={`counter-item status-${config.color}`}>
      <div className="counter-info">
        <span className="status-icon">{config.icon}</span>
        <div>
          <div className="counter-name">{counter.name || `Counter ${counter.counter_id}`}</div>
          <div className="counter-meta">
            {counter.last_fetched_at ? (
              <span>Last synced: {formatDate(counter.last_fetched_at)}</span>
            ) : (
              <span>Never synced</span>
            )}
          </div>
        </div>
      </div>
      <div className={`status-badge status-${config.color}`}>{config.text}</div>
    </div>
  );
}

/**
 * Stats Section
 */
function StatsSection({ data }) {
  return (
    <div className="dashboard-section">
      <h2>📈 Statistics</h2>

      <div className="stats-grid">
        <StatCard
          label="Total Records"
          value={data.total_records.toLocaleString()}
          icon="📊"
        />
        <StatCard
          label="Counters with Data"
          value={data.counters_with_data}
          icon="🔢"
        />
        <StatCard
          label="Total Visits"
          value={data.total_visits.toLocaleString()}
          icon="👥"
        />
        <StatCard
          label="Total Users"
          value={data.total_users.toLocaleString()}
          icon="👤"
        />
      </div>

      <div className="date-range">
        <div>
          <span>Earliest Date:</span>
          <strong>{data.earliest_date ? formatDate(data.earliest_date) : 'N/A'}</strong>
        </div>
        <div>
          <span>Latest Date:</span>
          <strong>{data.latest_date ? formatDate(data.latest_date) : 'N/A'}</strong>
        </div>
      </div>
    </div>
  );
}

/**
 * Stat Card
 */
function StatCard({ label, value, icon }) {
  return (
    <div className="stat-card">
      <div className="stat-icon">{icon}</div>
      <div className="stat-content">
        <div className="stat-label">{label}</div>
        <div className="stat-value">{value}</div>
      </div>
    </div>
  );
}

/**
 * Recent Syncs Section
 */
function RecentSyncsSection({ syncs }) {
  return (
    <div className="dashboard-section">
      <h2>⏱️ Recent Syncs</h2>

      <div className="syncs-list">
        {syncs.map((sync, index) => (
          <div key={index} className="sync-item">
            <span className="sync-icon">✅</span>
            <span className="sync-counter">Counter {sync.counter_id}</span>
            <span className="sync-time">{sync.time_ago}</span>
          </div>
        ))}
      </div>
    </div>
  );
}

/**
 * Format date helper
 */
function formatDate(dateString) {
  const date = new Date(dateString);
  return new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date);
}
