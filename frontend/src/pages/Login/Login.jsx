import React, { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { authApi } from '../../api/auth';
import './Login.css';

const Login = () => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();

  // Если уже авторизован, перенаправляем на dashboard
  React.useEffect(() => {
    const token = localStorage.getItem('token');
    const user = localStorage.getItem('user');
    if (token && user) {
      navigate('/dashboard', { replace: true });
    }
  }, [navigate]);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      const response = await authApi.login({ email, password });
      
      if (response.success) {
        localStorage.setItem('token', response.data.token);
        const userData = {
          ...response.data.user,
          firstName: response.data.user.first_name || response.data.user.firstName,
          lastName: response.data.user.last_name || response.data.user.lastName,
        };
        localStorage.setItem('user', JSON.stringify(userData));
        navigate('/dashboard');
      }
    } catch (err) {
      const errorMessage = err.message || 'Ошибка входа. Проверьте данные.';
      setError(errorMessage);
      console.error('Login error:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleYandexAuth = async () => {
    try {
      setError('');
      setLoading(true);
      
      const redirectUri = `${window.location.origin}/auth/yandex/callback`;
      const response = await authApi.getYandexAuthUrl(redirectUri);
      
      if (response.success && response.data.auth_url) {
        // Сохраняем redirect_uri для callback
        sessionStorage.setItem('yandex_redirect_uri', redirectUri);
        // Перенаправляем на Yandex OAuth
        window.location.href = response.data.auth_url;
      }
    } catch (err) {
      let errorMessage = err.message || 'Ошибка при авторизации через Yandex';
      
      // Более понятное сообщение об ошибке
      if (errorMessage.includes('Не удалось подключиться') || errorMessage.includes('Failed to fetch')) {
        errorMessage = 'Backend сервер не запущен. Запустите backend командой: php artisan serve или start-backend.bat';
      }
      
      setError(errorMessage);
      console.error('Yandex auth error:', err);
      setLoading(false);
    }
  };

  return (
    <div className="login">
      <div className="login__container">
        <div className="login__header">
          <div className="login__logo">
            <div className="login__logo-icon">in</div>
            <span className="login__logo-text">Plan in</span>
          </div>
          <h1 className="login__title">Вход в систему</h1>
          <p className="login__subtitle">Введите ваши данные для входа</p>
        </div>

        <form className="login__form" onSubmit={handleSubmit}>
          {error && (
            <div className="login__error">
              {error}
            </div>
          )}

          <div className="login__field">
            <label className="login__label">Email</label>
            <input
              type="email"
              className="login__input"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
              placeholder="your@email.com"
            />
          </div>

          <div className="login__field">
            <label className="login__label">Пароль</label>
            <input
              type="password"
              className="login__input"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
              placeholder="••••••••"
            />
          </div>

          <button
            type="submit"
            className="login__button"
            disabled={loading}
          >
            {loading ? 'Вход...' : 'Войти'}
          </button>
        </form>

        <div className="login__divider">
          <span>или</span>
        </div>

        <button
          type="button"
          className="login__yandex-button"
          onClick={handleYandexAuth}
          disabled={loading}
        >
          <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z"/>
          </svg>
          <span>Войти через Yandex</span>
        </button>

        <div className="login__footer">
          <p>
            Нет аккаунта?{' '}
            <Link to="/register" className="login__link">
              Зарегистрироваться
            </Link>
          </p>
        </div>
      </div>
    </div>
  );
};

export default Login;

