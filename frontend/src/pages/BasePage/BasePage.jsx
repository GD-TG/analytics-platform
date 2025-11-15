import React, { useState, useEffect } from 'react';
import { reportsApi } from '../../api/reports';
import './BasePage.css';

const BasePage = ({ title, subtitle, apiEndpoint, renderContent }) => {
  const [data, setData] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    try {
      setLoading(true);
      setError(null);
      const response = apiEndpoint ? await apiEndpoint() : { success: true, data: [] };
      
      if (response.success && response.data) {
        setData(Array.isArray(response.data) ? response.data : [response.data]);
      } else {
        setData([]);
      }
    } catch (err) {
      console.error(`Failed to load ${title}:`, err);
      setError(err.message);
      setData([]);
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="base-page">
        <div className="base-page__loading">Загрузка данных...</div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="base-page">
        <div className="base-page__header">
          <h1 className="base-page__title">{title}</h1>
          {subtitle && <p className="base-page__subtitle">{subtitle}</p>}
        </div>
        <div className="base-page__error">
          <p>Ошибка загрузки данных: {error}</p>
          <button className="base-page__retry-btn" onClick={loadData}>
            Попробовать снова
          </button>
        </div>
      </div>
    );
  }

  if (data.length === 0) {
    return (
      <div className="base-page">
        <div className="base-page__header">
          <h1 className="base-page__title">{title}</h1>
          {subtitle && <p className="base-page__subtitle">{subtitle}</p>}
        </div>
        <div className="base-page__empty">
          <p>Нет данных для отображения</p>
          <p className="base-page__empty-hint">
            Убедитесь, что OAuth токен настроен и выполнена синхронизация данных
          </p>
        </div>
      </div>
    );
  }

  return (
    <div className="base-page">
      <div className="base-page__header">
        <h1 className="base-page__title">{title}</h1>
        {subtitle && <p className="base-page__subtitle">{subtitle}</p>}
      </div>
      <div className="base-page__content">
        {renderContent ? renderContent(data, loadData) : (
          <div className="base-page__default-content">
            <p>Данные загружены успешно. Всего записей: {data.length}</p>
          </div>
        )}
      </div>
    </div>
  );
};

export default BasePage;

