import React, { useEffect, useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { yandexApi } from '../../api/yandex';
import './YandexCallback.css';

const YandexCallback = () => {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const handleCallback = async () => {
      try {
        const code = searchParams.get('code');
        const errorParam = searchParams.get('error');

        if (errorParam) {
          setError('Ошибка авторизации: ' + errorParam);
          setLoading(false);
          return;
        }

        if (!code) {
          setError('Код авторизации не получен');
          setLoading(false);
          return;
        }

        // Получаем сохраненный redirect_uri
        const redirectUri = sessionStorage.getItem('yandex_redirect_uri') || `${window.location.origin}/auth/yandex/callback`;

        // Обмениваем код на токен (создается YandexAccount на сервере)
        const response = await yandexApi.exchangeCode(code, redirectUri);

        if (response && response.success) {
          const accountId = response.data && (response.data.account_id || response.data.accountId || response.data.id) || response.account_id;
          // Получаем список счетчиков для аккаунта
          const countersResp = await yandexApi.listCounters(accountId);
          const counters = countersResp && countersResp.data ? countersResp.data.counters || countersResp.data : countersResp;

          // Сохраняем counters в state and show selection (simple flow: store in session and navigate to selection)
          sessionStorage.setItem('yandex_counters_' + accountId, JSON.stringify(counters));
          sessionStorage.setItem('yandex_account_id', accountId);
          sessionStorage.removeItem('yandex_redirect_uri');
          // Перенаправляем на страницу выбора счетчиков
          window.location.href = `/yandex/select?account_id=${accountId}`;
        } else {
          setError(response.message || 'Ошибка при авторизации');
          setLoading(false);
        }
      } catch (err) {
        const errorMessage = err.message || 'Ошибка при обработке авторизации';
        setError(errorMessage);
        setLoading(false);
        console.error('Yandex callback error:', err);
      }
    };

    handleCallback();
  }, [searchParams, navigate]);

  if (loading) {
    return (
      <div className="yandex-callback">
        <div className="yandex-callback__container">
          <div className="yandex-callback__loading">
            <div className="yandex-callback__spinner"></div>
            <p>Обработка авторизации...</p>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="yandex-callback">
      <div className="yandex-callback__container">
        {error ? (
          <div className="yandex-callback__error">
            <h2>Ошибка авторизации</h2>
            <p>{error}</p>
            <button
              className="yandex-callback__button"
              onClick={() => navigate('/login')}
            >
              Вернуться к входу
            </button>
          </div>
        ) : (
          <div className="yandex-callback__success">
            <h2>Авторизация успешна!</h2>
            <p>Перенаправление...</p>
          </div>
        )}
      </div>
    </div>
  );
};

export default YandexCallback;

