import React, { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
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
      // Работаем локально: сохраняем пользователя в local_users
      const usersRaw = localStorage.getItem('local_users');
      const users = usersRaw ? JSON.parse(usersRaw) : [];

      // Проверяем, не зарегистрирован ли уже пользователь с таким email
      const exists = users.some(u => u.email === email);
      if (exists) {
        setError('Пользователь с таким email уже зарегистрирован.');
        return;
      }

      const newUser = {
        id: Date.now(),
        name,
        email,
        password,
      };

      users.push(newUser);
      localStorage.setItem('local_users', JSON.stringify(users));

      // Сохраняем токен и публичную информацию о пользователе
      const token = Date.now().toString(36) + Math.random().toString(36).slice(2);
      const publicUser = { id: newUser.id, name: newUser.name, email: newUser.email, firstName: newUser.name };
      localStorage.setItem('token', token);
      localStorage.setItem('user', JSON.stringify(publicUser));

      navigate('/dashboard');
    } catch (err) {
      const errorMessage = err.message || 'Ошибка регистрации. Попробуйте снова.';
      setError(errorMessage);
      console.error('Register error:', err);
    } finally {
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

