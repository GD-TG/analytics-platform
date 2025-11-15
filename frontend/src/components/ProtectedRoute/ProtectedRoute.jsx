import React, { useEffect, useState } from 'react';
import { Navigate } from 'react-router-dom';
import { authApi } from '../../api/auth';

const ProtectedRoute = ({ children }) => {
  const [isValidating, setIsValidating] = useState(true);
  const [isAuthenticated, setIsAuthenticated] = useState(false);

  useEffect(() => {
    const validateAuth = async () => {
      const token = localStorage.getItem('token');
      const user = localStorage.getItem('user');

      if (!token || !user) {
        setIsAuthenticated(false);
        setIsValidating(false);
        return;
      }

      try {
        // Проверяем токен на сервере
        const response = await authApi.me();
        if (response && response.success) {
          setIsAuthenticated(true);
        } else {
          // Токен невалиден, очищаем
          localStorage.removeItem('token');
          localStorage.removeItem('user');
          setIsAuthenticated(false);
        }
      } catch (error) {
        // Если сервер недоступен, проверяем только наличие токена в localStorage
        // Это позволяет работать в режиме разработки даже если backend не запущен
        const isServerOffline = error.message === 'SERVER_OFFLINE' || 
                                error.message.includes('Failed to fetch') ||
                                error.message.includes('Не удалось подключиться');
        
        if (isServerOffline) {
          console.warn('Backend сервер недоступен. Используется проверка по localStorage.');
        } else {
          console.warn('Auth validation error:', error.message);
        }
        
        // Если есть токен и пользователь в localStorage, считаем авторизованным
        // Это временное решение для разработки
        if (token && user) {
          try {
            const parsedUser = JSON.parse(user);
            if (parsedUser && parsedUser.id) {
              setIsAuthenticated(true);
            } else {
              setIsAuthenticated(false);
            }
          } catch {
            setIsAuthenticated(false);
          }
        } else {
          setIsAuthenticated(false);
        }
      } finally {
        setIsValidating(false);
      }
    };

    validateAuth();
  }, []);

  if (isValidating) {
    return (
      <div style={{ 
        display: 'flex', 
        justifyContent: 'center', 
        alignItems: 'center', 
        height: '100vh',
        flexDirection: 'column',
        gap: '20px'
      }}>
        <div style={{
          width: '48px',
          height: '48px',
          border: '4px solid #f3f3f3',
          borderTop: '4px solid #e53935',
          borderRadius: '50%',
          animation: 'spin 1s linear infinite'
        }}></div>
        <p style={{ color: '#666' }}>Проверка авторизации...</p>
      </div>
    );
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" replace />;
  }

  return children;
};

export default ProtectedRoute;

