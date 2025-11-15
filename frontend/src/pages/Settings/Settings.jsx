import React, { useState, useEffect } from 'react';
import './Settings.css';

const Settings = () => {
  const [theme, setTheme] = useState('light');
  const [language, setLanguage] = useState('ru');
  const [notifications, setNotifications] = useState({
    email: true,
    push: true,
    sync: true,
  });
  const [autoSync, setAutoSync] = useState(true);
  const [syncInterval, setSyncInterval] = useState(60);

  useEffect(() => {
    // Загружаем сохраненные настройки
    const savedTheme = localStorage.getItem('theme') || 'light';
    const savedLanguage = localStorage.getItem('language') || 'ru';
    const savedNotifications = JSON.parse(localStorage.getItem('notifications') || '{"email":true,"push":true,"sync":true}');
    const savedAutoSync = localStorage.getItem('autoSync') !== 'false';
    const savedSyncInterval = parseInt(localStorage.getItem('syncInterval') || '60');

    setTheme(savedTheme);
    setLanguage(savedLanguage);
    setNotifications(savedNotifications);
    setAutoSync(savedAutoSync);
    setSyncInterval(savedSyncInterval);

    // Применяем тему
    document.documentElement.setAttribute('data-theme', savedTheme);
  }, []);

  const handleThemeChange = (newTheme) => {
    setTheme(newTheme);
    localStorage.setItem('theme', newTheme);
    document.documentElement.setAttribute('data-theme', newTheme);
  };

  const handleLanguageChange = (newLanguage) => {
    setLanguage(newLanguage);
    localStorage.setItem('language', newLanguage);
  };

  const handleNotificationChange = (key) => {
    const newNotifications = { ...notifications, [key]: !notifications[key] };
    setNotifications(newNotifications);
    localStorage.setItem('notifications', JSON.stringify(newNotifications));
  };

  const handleAutoSyncChange = (value) => {
    setAutoSync(value);
    localStorage.setItem('autoSync', value);
  };

  const handleSyncIntervalChange = (value) => {
    setSyncInterval(value);
    localStorage.setItem('syncInterval', value);
  };

  return (
    <div className="settings">
      <div className="settings__header">
        <h1 className="settings__title">Настройки</h1>
        <p className="settings__subtitle">Управление параметрами системы</p>
      </div>

      <div className="settings__content">
        <div className="settings__section">
          <h2 className="settings__section-title">Внешний вид</h2>
          
          <div className="settings__option">
            <div className="settings__option-info">
              <label className="settings__option-label">Тема оформления</label>
              <p className="settings__option-description">Выберите светлую или темную тему</p>
            </div>
            <div className="settings__option-control">
              <div className="settings__theme-switch">
                <button
                  className={`settings__theme-btn ${theme === 'light' ? 'settings__theme-btn--active' : ''}`}
                  onClick={() => handleThemeChange('light')}
                >
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <circle cx="12" cy="12" r="5"></circle>
                    <line x1="12" y1="1" x2="12" y2="3"></line>
                    <line x1="12" y1="21" x2="12" y2="23"></line>
                    <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                    <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                    <line x1="1" y1="12" x2="3" y2="12"></line>
                    <line x1="21" y1="12" x2="23" y2="12"></line>
                    <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                    <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                  </svg>
                  <span>Светлая</span>
                </button>
                <button
                  className={`settings__theme-btn ${theme === 'dark' ? 'settings__theme-btn--active' : ''}`}
                  onClick={() => handleThemeChange('dark')}
                >
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                  </svg>
                  <span>Темная</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <div className="settings__section">
          <h2 className="settings__section-title">Язык</h2>
          
          <div className="settings__option">
            <div className="settings__option-info">
              <label className="settings__option-label">Язык интерфейса</label>
              <p className="settings__option-description">Выберите предпочитаемый язык</p>
            </div>
            <div className="settings__option-control">
              <select
                className="settings__select"
                value={language}
                onChange={(e) => handleLanguageChange(e.target.value)}
              >
                <option value="ru">Русский</option>
                <option value="en">English</option>
              </select>
            </div>
          </div>
        </div>

        <div className="settings__section">
          <h2 className="settings__section-title">Уведомления</h2>
          
          <div className="settings__option">
            <div className="settings__option-info">
              <label className="settings__option-label">Email уведомления</label>
              <p className="settings__option-description">Получать уведомления на email</p>
            </div>
            <div className="settings__option-control">
              <label className="settings__toggle">
                <input
                  type="checkbox"
                  checked={notifications.email}
                  onChange={() => handleNotificationChange('email')}
                />
                <span className="settings__toggle-slider"></span>
              </label>
            </div>
          </div>

          <div className="settings__option">
            <div className="settings__option-info">
              <label className="settings__option-label">Push уведомления</label>
              <p className="settings__option-description">Получать push-уведомления в браузере</p>
            </div>
            <div className="settings__option-control">
              <label className="settings__toggle">
                <input
                  type="checkbox"
                  checked={notifications.push}
                  onChange={() => handleNotificationChange('push')}
                />
                <span className="settings__toggle-slider"></span>
              </label>
            </div>
          </div>

          <div className="settings__option">
            <div className="settings__option-info">
              <label className="settings__option-label">Уведомления о синхронизации</label>
              <p className="settings__option-description">Уведомлять о завершении синхронизации данных</p>
            </div>
            <div className="settings__option-control">
              <label className="settings__toggle">
                <input
                  type="checkbox"
                  checked={notifications.sync}
                  onChange={() => handleNotificationChange('sync')}
                />
                <span className="settings__toggle-slider"></span>
              </label>
            </div>
          </div>
        </div>

        <div className="settings__section">
          <h2 className="settings__section-title">Синхронизация</h2>
          
          <div className="settings__option">
            <div className="settings__option-info">
              <label className="settings__option-label">Автоматическая синхронизация</label>
              <p className="settings__option-description">Автоматически синхронизировать данные</p>
            </div>
            <div className="settings__option-control">
              <label className="settings__toggle">
                <input
                  type="checkbox"
                  checked={autoSync}
                  onChange={(e) => handleAutoSyncChange(e.target.checked)}
                />
                <span className="settings__toggle-slider"></span>
              </label>
            </div>
          </div>

          {autoSync && (
            <div className="settings__option">
              <div className="settings__option-info">
                <label className="settings__option-label">Интервал синхронизации</label>
                <p className="settings__option-description">Частота автоматической синхронизации (минуты)</p>
              </div>
              <div className="settings__option-control">
                <input
                  type="number"
                  className="settings__input"
                  min="5"
                  max="1440"
                  step="5"
                  value={syncInterval}
                  onChange={(e) => handleSyncIntervalChange(parseInt(e.target.value))}
                />
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default Settings;

