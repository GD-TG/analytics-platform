import React, { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
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
      // Локальная проверка пользователя в localStorage (без backend)
      const usersRaw = localStorage.getItem('local_users');
      const users = usersRaw ? JSON.parse(usersRaw) : [];

      const found = users.find(u => u.email === email && u.password === password);

      if (!found) {
        setError('Неправильный email или пароль.');
        return;
      }

      // Генерируем простой токен и сохраняем публичную часть пользователя
      const token = Date.now().toString(36) + Math.random().toString(36).slice(2);
      const publicUser = {
        id: found.id,
        name: found.name,
        email: found.email,
        firstName: found.name,
      };
      localStorage.setItem('token', token);
      localStorage.setItem('user', JSON.stringify(publicUser));
      navigate('/dashboard');
    } catch (err) {
      const errorMessage = err.message || 'Ошибка входа. Проверьте данные.';
      setError(errorMessage);
      console.error('Login error:', err);
    } finally {
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

        <div className="login__footer">
          <p>
            Нет аккаунта?{' '}
            <Link to="/register" className="login__link">
              Зарегистрироваться
            </Link>
          </p>
          <p style={{marginTop:8}}>
            Или подключить Yandex для отчётов: <a href="#" onClick={async (e)=>{e.preventDefault(); const redirectUri = `${window.location.origin}/auth/yandex/callback`; try { const resp = await (await import('../../api/yandex')).yandexApi.getAuthUrl(redirectUri); const url = resp.auth_url || (resp.data && resp.data.auth_url) || (resp.data && resp.data.data && resp.data.data.auth_url) || (resp.data && resp.data.auth_url); if (url) { sessionStorage.setItem('yandex_redirect_uri', redirectUri); window.location.href = url; } else { alert('Не удалось получить URL авторизации'); } } catch(err){ console.error(err); alert('Ошибка при запросе URL авторизации'); } }}>Подключить Yandex</a>
          </p>
        </div>
      </div>
    </div>
  );
};

export default Login;

