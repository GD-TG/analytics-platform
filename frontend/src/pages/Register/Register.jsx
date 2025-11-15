import React, { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { authApi } from '../../api/auth';
import './Register.css';

const Register = () => {
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
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

    if (password !== passwordConfirmation) {
      setError('Пароли не совпадают');
      return;
    }

    setLoading(true);

    try {
      const response = await authApi.register({
        name,
        email,
        password,
        password_confirmation: passwordConfirmation,
      });
      
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
      const errorMessage = err.message || 'Ошибка регистрации. Попробуйте снова.';
      setError(errorMessage);
      console.error('Register error:', err);
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
        sessionStorage.setItem('yandex_redirect_uri', redirectUri);
        window.location.href = response.data.auth_url;
      }
    } catch (err) {
      let errorMessage = err.message || 'Ошибка при регистрации через Yandex';
      
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
    <div className="register">
      <div className="register__container">
        <div className="register__header">
          <div className="register__logo">
            <div className="register__logo-icon">in</div>
            <span className="register__logo-text">Plan in</span>
          </div>
          <h1 className="register__title">Регистрация</h1>
          <p className="register__subtitle">Создайте новый аккаунт</p>
        </div>

        <form className="register__form" onSubmit={handleSubmit}>
          {error && (
            <div className="register__error">
              {error}
            </div>
          )}

          <div className="register__field">
            <label className="register__label">Имя</label>
            <input
              type="text"
              className="register__input"
              value={name}
              onChange={(e) => setName(e.target.value)}
              required
              placeholder="Ваше имя"
            />
          </div>

          <div className="register__field">
            <label className="register__label">Email</label>
            <input
              type="email"
              className="register__input"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
              placeholder="your@email.com"
            />
          </div>

          <div className="register__field">
            <label className="register__label">Пароль</label>
            <input
              type="password"
              className="register__input"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
              minLength={8}
              placeholder="Минимум 8 символов"
            />
          </div>

          <div className="register__field">
            <label className="register__label">Подтверждение пароля</label>
            <input
              type="password"
              className="register__input"
              value={passwordConfirmation}
              onChange={(e) => setPasswordConfirmation(e.target.value)}
              required
              placeholder="Повторите пароль"
            />
          </div>

          <button
            type="submit"
            className="register__button"
            disabled={loading}
          >
            {loading ? 'Регистрация...' : 'Зарегистрироваться'}
          </button>
        </form>

        <div className="register__divider">
          <span>или</span>
        </div>

        <button
          type="button"
          className="register__yandex-button"
          onClick={handleYandexAuth}
          disabled={loading}
        >
          <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z"/>
          </svg>
          <span>Зарегистрироваться через Yandex</span>
        </button>

        <div className="register__footer">
          <p>
            Уже есть аккаунт?{' '}
            <Link to="/login" className="register__link">
              Войти
            </Link>
          </p>
        </div>
      </div>
    </div>
  );
};

export default Register;

