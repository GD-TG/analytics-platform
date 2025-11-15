import React, { useEffect, useState } from 'react';
import { useSearchParams, useNavigate } from 'react-router-dom';
import { yandexApi } from '../../api/yandex';
import './YandexSelect.css';

const YandexSelect = () => {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const accountId = searchParams.get('account_id') || sessionStorage.getItem('yandex_account_id');
  const [counters, setCounters] = useState([]);
  const [selected, setSelected] = useState(new Set());
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    const load = async () => {
      setLoading(true);
      try {
        let data = sessionStorage.getItem('yandex_counters_' + accountId);
        if (data) {
          const parsed = JSON.parse(data);
          setCounters(parsed.counters || parsed || []);
        } else {
          const resp = await yandexApi.listCounters(accountId);
          const parsed = resp && resp.data ? resp.data.counters || resp.data : resp;
          setCounters(parsed || []);
        }
      } catch (e) {
        setError('Не удалось загрузить счетчики');
        console.error(e);
      } finally {
        setLoading(false);
      }
    };

    if (!accountId) {
      setError('account_id не указан');
      return;
    }

    load();
  }, [accountId]);

  const toggle = (id) => {
    const next = new Set(selected);
    if (next.has(id)) next.delete(id); else next.add(id);
    setSelected(next);
  };

  const save = async () => {
    if (!accountId) return;
    setLoading(true);
    setError('');
    try {
      const toSave = counters.filter(c => selected.has(c.id || c.counterId || c.counter_id)).map(c => ({ id: c.id || c.counterId || c.counter_id, name: c.name || c.title || c['counter-name'] }));
      const payload = { account_id: parseInt(accountId, 10), counters: toSave };
      const resp = await yandexApi.saveCounters(payload);
      if (resp && resp.success) {
        // Saved, redirect to dashboard or project page
        navigate('/dashboard');
      } else {
        setError(resp && (resp.message || resp.error) ? (resp.message || resp.error) : 'Ошибка при сохранении');
      }
    } catch (e) {
      setError('Ошибка при сохранении счетчиков');
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="yandex-select">
      <div className="yandex-select__container">
        <h2>Выберите счетчики для синхронизации</h2>
        {error && <div className="yandex-select__error">{error}</div>}
        {loading ? (
          <p>Загрузка...</p>
        ) : (
          <div className="yandex-select__list">
            {counters.length === 0 && <p>Счётчики не найдены.</p>}
            {counters.map((c) => {
              const id = c.id || c.counterId || c.counter_id;
              return (
                <label key={id} className="yandex-select__item">
                  <input type="checkbox" checked={selected.has(id)} onChange={() => toggle(id)} />
                  <span>{c.name || c.title || ('Counter ' + id)}</span>
                </label>
              );
            })}
          </div>
        )}

        <div className="yandex-select__actions">
          <button onClick={() => navigate(-1)}>Отмена</button>
          <button onClick={save} disabled={loading || selected.size === 0}>{loading ? 'Сохранение...' : 'Сохранить выбранные'}</button>
        </div>
      </div>
    </div>
  );
};

export default YandexSelect;
