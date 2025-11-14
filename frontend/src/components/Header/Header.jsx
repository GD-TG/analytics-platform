import React, { useState, useEffect, useContext } from 'react';
import { UserContext } from '../../contexts/UserContext';
import './Header.css';

const Header = () => {
  const { user } = useContext(UserContext);
  const [currentTime, setCurrentTime] = useState('');

  useEffect(() => {
    const updateTime = () => {
      const now = new Date();
      setCurrentTime(now.toLocaleTimeString('ru-RU', {
        hour: '2-digit',
        minute: '2-digit'
      }));
    };

    updateTime();
    const interval = setInterval(updateTime, 1000);

    return () => clearInterval(interval);
  }, []);

  return (
    <header className="header">
      <div className="header__left">
        <button className="header__menu-btn" aria-label="Меню">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
          </svg>
        </button>
        <div className="header__logo">
          <span className="header__logo-icon">P</span>
          <span className="header__logo-text">Planica</span>
        </div>
      </div>

      <div className="header__center">
        <div className="header__search">
          <input
            type="text"
            className="header__search-input"
            placeholder="Искать клиента, сотрудника, документ"
          />
          <svg className="header__search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <circle cx="11" cy="11" r="8"></circle>
            <path d="m21 21-4.35-4.35"></path>
          </svg>
        </div>
      </div>

      <div className="header__right">
        <div className="header__time">{currentTime}</div>
        <div className="header__user">
          <div className="header__avatar">
            <span>{user.firstName?.[0]}{user.lastName?.[0]}</span>
          </div>
          <span className="header__username">{user.firstName} {user.lastName}</span>
        </div>
        <button className="header__notifications" aria-label="Уведомления">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
          </svg>
        </button>
      </div>
    </header>
  );
};

export default Header;

