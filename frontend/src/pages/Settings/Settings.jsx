import React, { useState } from 'react';
import SettingsOAuth from './SettingsOAuth';
import './Settings.css';

/**
 * Компонент для управления настройками приложения
 * Включает локальные параметры (тема, язык, уведомления) и OAuth настройки
 */
export default function Settings() {
  const [theme, setTheme] = useState(localStorage.getItem('theme') || 'light');
  const [language, setLanguage] = useState(localStorage.getItem('language') || 'en');
  const [notifications, setNotifications] = useState(JSON.parse(localStorage.getItem('notifications') || 'true'));

  const handleThemeChange = (e) => {
    const newTheme = e.target.value;
    setTheme(newTheme);
    localStorage.setItem('theme', newTheme);
    document.documentElement.setAttribute('data-theme', newTheme);
  };

  const handleLanguageChange = (e) => {
    const newLanguage = e.target.value;
    setLanguage(newLanguage);
    localStorage.setItem('language', newLanguage);
  };

  const handleNotificationsChange = (e) => {
    const newNotifications = e.target.checked;
    setNotifications(newNotifications);
    localStorage.setItem('notifications', JSON.stringify(newNotifications));
  };

  return (
    <>
      {/* Local Settings Section */}
      <div className="settings">
        <div className="settings__container">
          <h1>⚙️ Локальные настройки</h1>

          <div className="settings__section">
            <h2>📖 Внешний вид</h2>
            <div className="settings__content">
              <label>Тема оформления:</label>
              <select value={theme} onChange={handleThemeChange}>
                <option value="light">☀️ Светлая</option>
                <option value="dark">🌙 Темная</option>
                <option value="auto">🔄 Авто</option>
              </select>
            </div>
          </div>

          <div className="settings__section">
            <h2>🌍 Язык</h2>
            <div className="settings__content">
              <label>Выберите язык интерфейса:</label>
              <select value={language} onChange={handleLanguageChange}>
                <option value="ru">🇷🇺 Русский</option>
                <option value="en">🇬🇧 Английский</option>
                <option value="fr">🇫🇷 Французский</option>
                <option value="de">🇩🇪 Немецкий</option>
              </select>
            </div>
          </div>

          <div className="settings__section">
            <h2>🔔 Уведомления</h2>
            <div className="settings__content">
              <label>
                <input
                  type="checkbox"
                  checked={notifications}
                  onChange={handleNotificationsChange}
                />
                Включить уведомления
              </label>
            </div>
          </div>
        </div>
      </div>

      {/* OAuth Settings Section */}
      <SettingsOAuth />
    </>
  );
}

