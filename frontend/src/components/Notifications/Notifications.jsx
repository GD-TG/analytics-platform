import React, { useState, useEffect, useRef } from 'react';
import './Notifications.css';

const Notifications = () => {
  const [isOpen, setIsOpen] = useState(false);
  const [notifications, setNotifications] = useState([]);
  const [unreadCount, setUnreadCount] = useState(0);
  const dropdownRef = useRef(null);

  useEffect(() => {
    // Загружаем уведомления
    loadNotifications();
    
    // Обновляем уведомления каждые 30 секунд
    const interval = setInterval(loadNotifications, 30000);
    
    return () => clearInterval(interval);
  }, []);

  useEffect(() => {
    // Закрываем dropdown при клике вне его
    const handleClickOutside = (event) => {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
        setIsOpen(false);
      }
    };

    if (isOpen) {
      document.addEventListener('mousedown', handleClickOutside);
    }

    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, [isOpen]);

  const loadNotifications = async () => {
    try {
      // Здесь можно добавить запрос к API
      // Пока используем моковые данные на основе последних обновлений
      const mockNotifications = generateMockNotifications();
      setNotifications(mockNotifications);
      setUnreadCount(mockNotifications.filter(n => !n.read).length);
    } catch (error) {
      console.error('Failed to load notifications:', error);
    }
  };

  const generateMockNotifications = () => {
    const now = new Date();
    const types = ['sync', 'update', 'warning', 'success'];
    const messages = {
      sync: 'Синхронизация данных завершена',
      update: 'Обновлены метрики за последний период',
      warning: 'Требуется внимание: падение метрик',
      success: 'Новые данные успешно загружены'
    };
    
    const notifications = [];
    
    // Генерируем 3-5 уведомлений
    const count = Math.floor(Math.random() * 3) + 3;
    
    for (let i = 0; i < count; i++) {
      const type = types[Math.floor(Math.random() * types.length)];
      const minutesAgo = Math.floor(Math.random() * 60) + 1;
      const time = new Date(now.getTime() - minutesAgo * 60000);
      
      notifications.push({
        id: i + 1,
        type,
        message: messages[type],
        time,
        read: i > 2, // Первые 3 непрочитанные
      });
    }
    
    return notifications.sort((a, b) => b.time - a.time);
  };

  const formatTime = (date) => {
    const now = new Date();
    const diff = now - date;
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);

    if (minutes < 1) return 'только что';
    if (minutes < 60) return `${minutes} мин назад`;
    if (hours < 24) return `${hours} ч назад`;
    if (days < 7) return `${days} дн назад`;
    return date.toLocaleDateString('ru-RU', { day: 'numeric', month: 'short' });
  };

  const markAsRead = (id) => {
    setNotifications(notifications.map(n => 
      n.id === id ? { ...n, read: true } : n
    ));
    setUnreadCount(Math.max(0, unreadCount - 1));
  };

  const markAllAsRead = () => {
    setNotifications(notifications.map(n => ({ ...n, read: true })));
    setUnreadCount(0);
  };

  const getNotificationIcon = (type) => {
    switch (type) {
      case 'sync':
        return (
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <polyline points="23 4 23 10 17 10"></polyline>
            <polyline points="1 20 1 14 7 14"></polyline>
            <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
          </svg>
        );
      case 'update':
        return (
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <circle cx="12" cy="12" r="10"></circle>
            <polyline points="12 6 12 12 16 14"></polyline>
          </svg>
        );
      case 'warning':
        return (
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
            <line x1="12" y1="9" x2="12" y2="13"></line>
            <line x1="12" y1="17" x2="12.01" y2="17"></line>
          </svg>
        );
      case 'success':
        return (
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
          </svg>
        );
      default:
        return null;
    }
  };

  const getNotificationClass = (type) => {
    switch (type) {
      case 'sync': return 'notifications__item--sync';
      case 'update': return 'notifications__item--update';
      case 'warning': return 'notifications__item--warning';
      case 'success': return 'notifications__item--success';
      default: return '';
    }
  };

  return (
    <div className="notifications" ref={dropdownRef}>
      <button 
        className="notifications__button" 
        onClick={() => setIsOpen(!isOpen)}
        aria-label="Уведомления"
      >
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
          <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
          <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
        </svg>
        {unreadCount > 0 && (
          <span className="notifications__badge">{unreadCount > 9 ? '9+' : unreadCount}</span>
        )}
      </button>

      {isOpen && (
        <div className="notifications__dropdown">
          <div className="notifications__header">
            <h3 className="notifications__title">Уведомления</h3>
            {unreadCount > 0 && (
              <button 
                className="notifications__mark-all"
                onClick={markAllAsRead}
              >
                Отметить все как прочитанные
              </button>
            )}
          </div>

          <div className="notifications__list">
            {notifications.length === 0 ? (
              <div className="notifications__empty">
                <p>Нет уведомлений</p>
              </div>
            ) : (
              notifications.map((notification) => (
                <div
                  key={notification.id}
                  className={`notifications__item ${getNotificationClass(notification.type)} ${!notification.read ? 'notifications__item--unread' : ''}`}
                  onClick={() => markAsRead(notification.id)}
                >
                  <div className="notifications__item-icon">
                    {getNotificationIcon(notification.type)}
                  </div>
                  <div className="notifications__item-content">
                    <p className="notifications__item-message">{notification.message}</p>
                    <span className="notifications__item-time">{formatTime(notification.time)}</span>
                  </div>
                  {!notification.read && (
                    <div className="notifications__item-dot"></div>
                  )}
                </div>
              ))
            )}
          </div>
        </div>
      )}
    </div>
  );
};

export default Notifications;

