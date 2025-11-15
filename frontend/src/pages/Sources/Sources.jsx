import React, { useState, useEffect } from 'react';
import { PieChart, Pie, Cell, ResponsiveContainer, Legend, Tooltip } from 'recharts';
import { reportsApi } from '../../api/reports';
import './Sources.css';

const Sources = () => {
  const [data, setData] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    try {
      setLoading(true);
      const response = await reportsApi.getSources();
      
      if (response.success && response.data) {
        setData(response.data);
      } else {
        setData([]);
      }
    } catch (error) {
      console.error('Failed to load sources:', error);
      setData([]);
    } finally {
      setLoading(false);
    }
  };

  const renderPieChart = (monthData, total) => {
    if (!monthData || monthData.length === 0) {
      return <div className="sources__chart-empty">Нет данных</div>;
    }

    return (
      <div className="sources__chart-wrapper">
        <ResponsiveContainer width="100%" height={300}>
          <PieChart>
            <Pie
              data={monthData}
              cx="50%"
              cy="50%"
              innerRadius={80}
              outerRadius={120}
              paddingAngle={2}
              dataKey="value"
            >
              {monthData.map((entry, index) => (
                <Cell key={`cell-${index}`} fill={entry.color} />
              ))}
            </Pie>
            <Tooltip formatter={(value) => `${value}%`} />
          </PieChart>
        </ResponsiveContainer>
        <div className="sources__chart-center">
          <div className="sources__chart-total">{total.toLocaleString('ru-RU')}</div>
          <div className="sources__chart-percent">100%</div>
        </div>
      </div>
    );
  };

  if (loading) {
    return (
      <div className="sources">
        <div className="sources__loading">Загрузка данных...</div>
      </div>
    );
  }

  if (data.length === 0) {
    return (
      <div className="sources">
        <div className="sources__header">
          <h1 className="sources__title">Источники трафика</h1>
        </div>
        <div className="sources__empty">
          <p>Нет данных для отображения</p>
          <p className="sources__empty-hint">
            Убедитесь, что OAuth токен настроен и выполнена синхронизация данных
          </p>
        </div>
      </div>
    );
  }

  // Берем последние 2 месяца из данных
  const currentMonth = data[data.length - 1] || null;
  const previousMonth = data[data.length - 2] || null;

  const currentMonthData = currentMonth?.sources || [];
  const previousMonthData = previousMonth?.sources || [];
  const currentTotal = currentMonthData.reduce((sum, item) => sum + (item.count || 0), 0);
  const previousTotal = previousMonthData.reduce((sum, item) => sum + (item.count || 0), 0);

  return (
    <div className="sources">
      <div className="sources__header">
        <h1 className="sources__title">Источники трафика</h1>
      </div>

      <div className="sources__content">
        <div className="sources__charts">
          {currentMonth && (
            <div className="sources__chart-card">
              <div className="sources__chart-header">
                <div>
                  <h3 className="sources__chart-title">Источники трафика</h3>
                  <p className="sources__chart-subtitle">Визиты</p>
                </div>
                <div className="sources__chart-actions">
                  <button className="sources__action-btn" title="Обновить" onClick={loadData}>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                      <polyline points="23 4 23 10 17 10"></polyline>
                      <polyline points="1 20 1 14 7 14"></polyline>
                      <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                    </svg>
                  </button>
                </div>
              </div>
              {renderPieChart(currentMonthData, currentTotal)}
              <div className="sources__chart-legend">
                {currentMonthData.map((item, index) => (
                  <div key={index} className="sources__legend-item">
                    <span className="sources__legend-dot" style={{ backgroundColor: item.color || '#9e9e9e' }}></span>
                    <span className="sources__legend-text">{item.name}: {item.value}%</span>
                  </div>
                ))}
              </div>
              <div className="sources__chart-month">{currentMonth.month_label || 'Текущий месяц'}</div>
            </div>
          )}

          {previousMonth && (
            <div className="sources__chart-card">
              <div className="sources__chart-header">
                <div>
                  <h3 className="sources__chart-title">Источники трафика</h3>
                  <p className="sources__chart-subtitle">Визиты</p>
                </div>
                <div className="sources__chart-actions">
                  <button className="sources__action-btn" title="Обновить" onClick={loadData}>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                      <polyline points="23 4 23 10 17 10"></polyline>
                      <polyline points="1 20 1 14 7 14"></polyline>
                      <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                    </svg>
                  </button>
                </div>
              </div>
              {renderPieChart(previousMonthData, previousTotal)}
              <div className="sources__chart-legend">
                {previousMonthData.map((item, index) => (
                  <div key={index} className="sources__legend-item">
                    <span className="sources__legend-dot" style={{ backgroundColor: item.color || '#9e9e9e' }}></span>
                    <span className="sources__legend-text">{item.name}: {item.value}%</span>
                  </div>
                ))}
              </div>
              <div className="sources__chart-month">{previousMonth.month_label || 'Предыдущий месяц'}</div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default Sources;
