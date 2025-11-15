import React, { useEffect, useState } from 'react';
import './SettingsOAuth.css';

/**
 * OAuth Settings Component
 * Configure Yandex Metrika and Yandex Direct credentials
 */
export default function SettingsOAuth() {
  const [settings, setSettings] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [activeTab, setActiveTab] = useState('metrika');
  const [savingTab, setSavingTab] = useState(null);

  useEffect(() => {
    fetchSettings();
  }, []);

  const fetchSettings = async () => {
    try {
      const token = localStorage.getItem('auth_token');
      if (!token) {
        setError('Not authenticated');
        return;
      }

      const response = await fetch('/api/settings', {
        headers: { 'Authorization': `Bearer ${token}` },
      });

      if (!response.ok) {
        throw new Error('Failed to fetch settings');
      }

      const data = await response.json();
      setSettings(data);
      setError(null);
    } catch (err) {
      setError(err.message);
      console.error('Settings error:', err);
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="settings-container">
        <div className="loading-spinner">
          <div className="spinner"></div>
          <p>Loading settings...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="settings-container">
        <div className="error-message">
          <p>❌ {error}</p>
          <button onClick={fetchSettings}>Retry</button>
        </div>
      </div>
    );
  }

  return (
    <div className="settings-container">
      <div className="settings-header">
        <h1>⚙️ OAuth Settings</h1>
        <p>Configure your integrations with Yandex services</p>
      </div>

      <div className="settings-content">
        <div className="tabs">
          <button
            className={`tab-button ${activeTab === 'metrika' ? 'active' : ''}`}
            onClick={() => setActiveTab('metrika')}
          >
            🔍 Yandex Metrika
          </button>
          <button
            className={`tab-button ${activeTab === 'direct' ? 'active' : ''}`}
            onClick={() => setActiveTab('direct')}
          >
            📊 Yandex Direct
          </button>
          <button
            className={`tab-button ${activeTab === 'sync' ? 'active' : ''}`}
            onClick={() => setActiveTab('sync')}
          >
            🔄 Sync Settings
          </button>
        </div>

        {activeTab === 'metrika' && settings && (
          <YandexMetrikaForm settings={settings} onSuccess={fetchSettings} savingTab={savingTab} setSavingTab={setSavingTab} />
        )}

        {activeTab === 'direct' && settings && (
          <YandexDirectForm settings={settings} onSuccess={fetchSettings} savingTab={savingTab} setSavingTab={setSavingTab} />
        )}

        {activeTab === 'sync' && settings && (
          <SyncSettingsForm settings={settings} onSuccess={fetchSettings} savingTab={savingTab} setSavingTab={setSavingTab} />
        )}
      </div>
    </div>
  );
}

/**
 * Yandex Metrika Settings Form
 */
function YandexMetrikaForm({ settings, onSuccess, savingTab, setSavingTab }) {
  const [formData, setFormData] = useState({
    client_id: '',
    client_secret: '',
  });
  const [testResult, setTestResult] = useState(null);
  const [testLoading, setTestLoading] = useState(false);
  const [successMessage, setSuccessMessage] = useState('');

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSavingTab('metrika');

    try {
      const token = localStorage.getItem('auth_token');
      const response = await fetch('/api/settings/yandex-metrika', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(formData),
      });

      if (!response.ok) {
        const error = await response.json();
        throw new Error(error.message || 'Failed to save settings');
      }

      const result = await response.json();
      setSuccessMessage(result.message);
      setFormData({ client_id: '', client_secret: '' });
      onSuccess();

      setTimeout(() => setSuccessMessage(''), 3000);
    } catch (error) {
      alert('Error: ' + error.message);
    } finally {
      setSavingTab(null);
    }
  };

  const handleTest = async () => {
    setTestLoading(true);

    try {
      const token = localStorage.getItem('auth_token');
      const response = await fetch('/api/settings/test/yandex-metrika', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
        },
      });

      const result = await response.json();
      setTestResult({
        valid: result.valid,
        message: result.message,
      });
    } catch (error) {
      setTestResult({
        valid: false,
        message: 'Test failed: ' + error.message,
      });
    } finally {
      setTestLoading(false);
    }
  };

  return (
    <div className="form-section">
      <div className="form-card">
        <div className="form-header">
          <h3>Yandex Metrika OAuth</h3>
          {settings.integrations.yandex_metrika.configured && (
            <span className="configured-badge">✅ Configured</span>
          )}
        </div>

        {successMessage && <div className="success-message">{successMessage}</div>}

        <form onSubmit={handleSubmit}>
          <div className="form-group">
            <label>Client ID</label>
            <input
              type="text"
              name="client_id"
              placeholder="Your Yandex Metrika Client ID"
              value={formData.client_id}
              onChange={handleChange}
              required
              minLength={10}
            />
            <small>Get from <a href="https://oauth.yandex.com/client/new" target="_blank" rel="noopener noreferrer">Yandex OAuth</a></small>
          </div>

          <div className="form-group">
            <label>Client Secret</label>
            <input
              type="password"
              name="client_secret"
              placeholder="Your Yandex Metrika Client Secret"
              value={formData.client_secret}
              onChange={handleChange}
              required
              minLength={10}
            />
            <small>Keep this secret safe!</small>
          </div>

          <div className="form-actions">
            <button type="submit" className="btn-primary" disabled={savingTab === 'metrika'}>
              {savingTab === 'metrika' ? '💾 Saving...' : '💾 Save'}
            </button>
            <button
              type="button"
              className="btn-secondary"
              onClick={handleTest}
              disabled={testLoading || !settings.integrations.yandex_metrika.configured}
            >
              {testLoading ? '🔄 Testing...' : '🧪 Test'}
            </button>
          </div>
        </form>

        {testResult && (
          <div className={`test-result ${testResult.valid ? 'success' : 'error'}`}>
            <span>{testResult.valid ? '✅' : '❌'}</span>
            <span>{testResult.message}</span>
          </div>
        )}
      </div>
    </div>
  );
}

/**
 * Yandex Direct Settings Form
 */
function YandexDirectForm({ settings, onSuccess, savingTab, setSavingTab }) {
  const [formData, setFormData] = useState({
    client_id: '',
    client_secret: '',
  });
  const [testResult, setTestResult] = useState(null);
  const [testLoading, setTestLoading] = useState(false);
  const [successMessage, setSuccessMessage] = useState('');

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSavingTab('direct');

    try {
      const token = localStorage.getItem('auth_token');
      const response = await fetch('/api/settings/yandex-direct', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(formData),
      });

      if (!response.ok) {
        const error = await response.json();
        throw new Error(error.message || 'Failed to save settings');
      }

      const result = await response.json();
      setSuccessMessage(result.message);
      setFormData({ client_id: '', client_secret: '' });
      onSuccess();

      setTimeout(() => setSuccessMessage(''), 3000);
    } catch (error) {
      alert('Error: ' + error.message);
    } finally {
      setSavingTab(null);
    }
  };

  const handleTest = async () => {
    setTestLoading(true);

    try {
      const token = localStorage.getItem('auth_token');
      const response = await fetch('/api/settings/test/yandex-direct', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
        },
      });

      const result = await response.json();
      setTestResult({
        valid: result.valid,
        message: result.message,
      });
    } catch (error) {
      setTestResult({
        valid: false,
        message: 'Test failed: ' + error.message,
      });
    } finally {
      setTestLoading(false);
    }
  };

  return (
    <div className="form-section">
      <div className="form-card">
        <div className="form-header">
          <h3>Yandex Direct OAuth</h3>
          {settings.integrations.yandex_direct.configured && (
            <span className="configured-badge">✅ Configured</span>
          )}
        </div>

        {successMessage && <div className="success-message">{successMessage}</div>}

        <form onSubmit={handleSubmit}>
          <div className="form-group">
            <label>Client ID</label>
            <input
              type="text"
              name="client_id"
              placeholder="Your Yandex Direct Client ID"
              value={formData.client_id}
              onChange={handleChange}
              required
              minLength={10}
            />
            <small>Get from <a href="https://oauth.yandex.com/client/new" target="_blank" rel="noopener noreferrer">Yandex OAuth</a></small>
          </div>

          <div className="form-group">
            <label>Client Secret</label>
            <input
              type="password"
              name="client_secret"
              placeholder="Your Yandex Direct Client Secret"
              value={formData.client_secret}
              onChange={handleChange}
              required
              minLength={10}
            />
            <small>Keep this secret safe!</small>
          </div>

          <div className="form-actions">
            <button type="submit" className="btn-primary" disabled={savingTab === 'direct'}>
              {savingTab === 'direct' ? '💾 Saving...' : '💾 Save'}
            </button>
            <button
              type="button"
              className="btn-secondary"
              onClick={handleTest}
              disabled={testLoading || !settings.integrations.yandex_direct.configured}
            >
              {testLoading ? '🔄 Testing...' : '🧪 Test'}
            </button>
          </div>
        </form>

        {testResult && (
          <div className={`test-result ${testResult.valid ? 'success' : 'error'}`}>
            <span>{testResult.valid ? '✅' : '❌'}</span>
            <span>{testResult.message}</span>
          </div>
        )}
      </div>
    </div>
  );
}

/**
 * Sync Settings Form
 */
function SyncSettingsForm({ settings, onSuccess, savingTab, setSavingTab }) {
  const [formData, setFormData] = useState({
    interval_minutes: settings.sync.interval_minutes,
    enabled: settings.sync.enabled,
  });
  const [successMessage, setSuccessMessage] = useState('');

  const handleChange = (e) => {
    const { name, value, type, checked } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : parseInt(value),
    }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSavingTab('sync');

    try {
      const token = localStorage.getItem('auth_token');
      const response = await fetch('/api/settings/sync', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(formData),
      });

      if (!response.ok) {
        const error = await response.json();
        throw new Error(error.message || 'Failed to save settings');
      }

      const result = await response.json();
      setSuccessMessage(result.message);
      onSuccess();

      setTimeout(() => setSuccessMessage(''), 3000);
    } catch (error) {
      alert('Error: ' + error.message);
    } finally {
      setSavingTab(null);
    }
  };

  return (
    <div className="form-section">
      <div className="form-card">
        <h3>Sync Preferences</h3>

        {successMessage && <div className="success-message">{successMessage}</div>}

        <form onSubmit={handleSubmit}>
          <div className="form-group">
            <label>Enable Automatic Sync</label>
            <div className="checkbox-group">
              <input
                type="checkbox"
                name="enabled"
                checked={formData.enabled}
                onChange={handleChange}
                id="sync-enabled"
              />
              <label htmlFor="sync-enabled">
                {formData.enabled ? '✅ Enabled' : '⏹️ Disabled'}
              </label>
            </div>
          </div>

          <div className="form-group">
            <label>Sync Interval (minutes)</label>
            <input
              type="number"
              name="interval_minutes"
              value={formData.interval_minutes}
              onChange={handleChange}
              min={5}
              max={1440}
              disabled={!formData.enabled}
            />
            <small>Between {formData.interval_minutes} minutes</small>
          </div>

          <div className="form-actions">
            <button type="submit" className="btn-primary" disabled={savingTab === 'sync'}>
              {savingTab === 'sync' ? '💾 Saving...' : '💾 Save'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
