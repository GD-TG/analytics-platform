import React, { useState } from 'react';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';
import './Visits.css';

const Visits = () => {
  const [viewMode, setViewMode] = useState('chart');

  // Данные для графика
  const data = [];
  const daysInMonth = 31;
  for (let i = 1; i <= daysInMonth; i++) {
    data.push({
      date: `${String(i).padStart(2, '0')}.10.25`,
      directNetworks: Math.floor(Math.random() * 150) + 50,
      directSearch: Math.floor(Math.random() * 100) + 30,
      googleSearch: Math.floor(Math.random() * 80) + 20,
      directEntries: Math.floor(Math.random() * 60) + 15,
      yandexSearch: Math.floor(Math.random() * 70) + 20,
    });
  }

  const lines = [
    { key: 'directNetworks', name: 'Переходы по рекламе — Яндекс.Директ — Сети', color: '#4caf50' },
    { key: 'directSearch', name: 'Переходы по рекламе — Яндекс.Директ — Поиск', color: '#ffc107' },
    { key: 'googleSearch', name: 'Переходы из поисковых систем — Google — Не определено', color: '#e53935' },
    { key: 'directEntries', name: 'Прямые заходы — Не определено', color: '#2196f3' },
    { key: 'yandexSearch', name: 'Переходы из поисковых систем — Яндекс — Не определено', color: '#9c27b0' },
  ];

  const [visibleLines, setVisibleLines] = useState({
    directNetworks: true,
    directSearch: true,
    googleSearch: true,
    directEntries: true,
    yandexSearch: true,
  });

  const toggleLine = (key) => {
    setVisibleLines(prev => ({ ...prev, [key]: !prev[key] }));
  };

  return (
    <div className="visits">
      <div className="visits__header">
        <h1 className="visits__title">WEB-аналитика / Визиты</h1>
      </div>

      <div className="visits__content">
        <div className="visits__chart-card">
          <div className="visits__chart-header">
            <h2 className="visits__chart-title">Визиты</h2>
            <div className="visits__chart-controls">
              <button
                className={`visits__control-btn ${viewMode === 'chart' ? 'visits__control-btn--active' : ''}`}
                onClick={() => setViewMode('chart')}
                title="График"
              >
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <line x1="3" y1="12" x2="7" y2="12"></line>
                  <line x1="7" y1="8" x2="7" y2="16"></line>
                  <line x1="11" y1="4" x2="11" y2="20"></line>
                  <line x1="15" y1="12" x2="19" y2="12"></line>
                  <line x1="19" y1="8" x2="19" y2="16"></line>
                </svg>
              </button>
              <button
                className={`visits__control-btn ${viewMode === 'table' ? 'visits__control-btn--active' : ''}`}
                onClick={() => setViewMode('table')}
                title="Таблица"
              >
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                  <line x1="9" y1="3" x2="9" y2="21"></line>
                  <line x1="3" y1="9" x2="21" y2="9"></line>
                </svg>
              </button>
              <button className="visits__control-btn" title="Календарь">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                  <line x1="16" y1="2" x2="16" y2="6"></line>
                  <line x1="8" y1="2" x2="8" y2="6"></line>
                  <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
              </button>
              <div className="visits__dropdown">
                <span>6/6</span>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
              </div>
            </div>
          </div>

          {viewMode === 'chart' && (
            <div className="visits__chart-wrapper">
              <ResponsiveContainer width="100%" height={400}>
                <LineChart data={data} margin={{ top: 5, right: 30, left: 20, bottom: 5 }}>
                  <CartesianGrid strokeDasharray="3 3" stroke="#e0e0e0" />
                  <XAxis 
                    dataKey="date" 
                    stroke="#666"
                    style={{ fontSize: '12px' }}
                  />
                  <YAxis 
                    stroke="#666"
                    style={{ fontSize: '12px' }}
                    domain={[0, 175]}
                  />
                  <Tooltip 
                    contentStyle={{ 
                      backgroundColor: '#fff', 
                      border: '1px solid #e0e0e0',
                      borderRadius: '4px'
                    }}
                  />
                  <Legend />
                  {lines.map(line => 
                    visibleLines[line.key] && (
                      <Line
                        key={line.key}
                        type="monotone"
                        dataKey={line.key}
                        name={line.name}
                        stroke={line.color}
                        strokeWidth={2}
                        dot={false}
                      />
                    )
                  )}
                </LineChart>
              </ResponsiveContainer>
            </div>
          )}

          {viewMode === 'table' && (
            <div className="visits__table-wrapper">
              <table className="visits__table">
                <thead>
                  <tr>
                    <th>Дата</th>
                    {lines.map(line => (
                      <th key={line.key}>{line.name}</th>
                    ))}
                  </tr>
                </thead>
                <tbody>
                  {data.map((row, index) => (
                    <tr key={index}>
                      <td>{row.date}</td>
                      {lines.map(line => (
                        <td key={line.key}>{row[line.key]}</td>
                      ))}
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}

          <div className="visits__legend">
            <div className="visits__legend-header">
              <button className="visits__hide-chart-btn">Скрыть график</button>
            </div>
            <div className="visits__legend-items">
              {lines.map(line => (
                <label key={line.key} className="visits__legend-item">
                  <input
                    type="checkbox"
                    checked={visibleLines[line.key]}
                    onChange={() => toggleLine(line.key)}
                  />
                  <span 
                    className="visits__legend-color" 
                    style={{ backgroundColor: line.color }}
                  ></span>
                  <span className="visits__legend-label">{line.name}</span>
                </label>
              ))}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Visits;

