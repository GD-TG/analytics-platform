import React, { useState, useEffect } from 'react';
import { reportsApi } from '../../api/reports';
import './AgeLead.css';

const AgeLead = () => {
  const [selectedAges, setSelectedAges] = useState({
    total: true,
    under18: true,
    '18-24': true,
    '25-34': true,
    '35-44': true,
    '45-54': true,
    '55+': true,
  });
  const [data, setData] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    try {
      setLoading(true);
      const response = await reportsApi.getAgeData();
      
      if (response.success && response.data) {
        setData(response.data);
      } else {
        setData([]);
      }
    } catch (error) {
      console.error('Failed to load age data:', error);
      setData([]);
    } finally {
      setLoading(false);
    }
  };

  const ageGroups = [
    { key: 'total', label: 'Итого и средние' },
    { key: 'under18', label: 'младше 18 лет' },
    { key: '18-24', label: '18-24 года' },
    { key: '25-34', label: '25-34 года' },
    { key: '35-44', label: '35-44 года' },
    { key: '45-54', label: '45-54 года' },
    { key: '55+', label: '55 лет и старше' },
  ];

  const formatAgeData = (monthData) => {
    if (!monthData || !monthData.data || monthData.data.length === 0) {
      return [];
    }

    return monthData.data.map(item => ({
      age: item.age_group || 'total',
      visits: item.visits || 0,
      visitors: item.users || 0,
      bounces: item.bounce_rate ? `${item.bounce_rate.toFixed(2)}%` : '0.00%',
      depth: item.avg_duration ? (item.avg_duration / 60).toFixed(2) : '0.00',
      time: item.avg_duration ? formatTime(item.avg_duration) : '0:00',
      views: item.views || 0,
    }));
  };

  const formatTime = (seconds) => {
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${mins}:${String(secs).padStart(2, '0')}`;
  };

  const toggleAge = (key) => {
    setSelectedAges(prev => ({ ...prev, [key]: !prev[key] }));
  };

  const renderTable = (title, tableData) => {
    if (!tableData || tableData.length === 0) {
      return (
        <div className="age-lead__section">
          <h2 className="age-lead__section-title">{title}</h2>
          <div className="age-lead__empty">Нет данных</div>
        </div>
      );
    }

    return (
      <div className="age-lead__section">
        <h2 className="age-lead__section-title">{title}</h2>
        <div className="age-lead__table-container">
          <div className="age-lead__table-wrapper">
            <div className="age-lead__age-list">
              <div className="age-lead__age-header">
                <span>Возраст</span>
              </div>
              {ageGroups.map(group => (
                <label key={group.key} className="age-lead__age-item">
                  <input
                    type="checkbox"
                    checked={selectedAges[group.key]}
                    onChange={() => toggleAge(group.key)}
                  />
                  <span className="age-lead__age-label">{group.label}</span>
                </label>
              ))}
            </div>
            <div className="age-lead__data-table">
              <table className="age-lead__table">
                <thead>
                  <tr>
                    <th>Визиты</th>
                    <th>Посетители</th>
                    <th>Отказы</th>
                    <th>Глубина просмотра</th>
                    <th>Время на сайте</th>
                    <th>Просмотры</th>
                  </tr>
                </thead>
                <tbody>
                  {tableData.map((row, index) => (
                    <tr key={index} className={!selectedAges[row.age] ? 'age-lead__row--hidden' : ''}>
                      <td>{row.visits.toLocaleString('ru-RU')}</td>
                      <td>{row.visitors.toLocaleString('ru-RU')}</td>
                      <td>{row.bounces}</td>
                      <td>{row.depth}</td>
                      <td>{row.time}</td>
                      <td>{row.views.toLocaleString('ru-RU')}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    );
  };

  if (loading) {
    return (
      <div className="age-lead">
        <div className="age-lead__loading">Загрузка данных...</div>
      </div>
    );
  }

  if (data.length === 0) {
    return (
      <div className="age-lead">
        <div className="age-lead__header">
          <h1 className="age-lead__title">Лид</h1>
        </div>
        <div className="age-lead__empty">
          <p>Нет данных для отображения</p>
          <p className="age-lead__empty-hint">
            Убедитесь, что OAuth токен настроен и выполнена синхронизация данных
          </p>
        </div>
      </div>
    );
  }

  // Берем последние 2 месяца
  const currentMonth = data[data.length - 1] || null;
  const previousMonth = data[data.length - 2] || null;

  const currentMonthData = currentMonth ? formatAgeData(currentMonth) : [];
  const previousMonthData = previousMonth ? formatAgeData(previousMonth) : [];

  return (
    <div className="age-lead">
      <div className="age-lead__header">
        <h1 className="age-lead__title">Лид</h1>
      </div>

      <div className="age-lead__content">
        {currentMonth && renderTable(currentMonth.month_label || 'Текущий месяц', currentMonthData)}
        {previousMonth && renderTable(previousMonth.month_label || 'Предыдущий месяц', previousMonthData)}
      </div>
    </div>
  );
};

export default AgeLead;
