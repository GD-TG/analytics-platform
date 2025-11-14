import React from 'react';
import { PieChart, Pie, Cell, ResponsiveContainer, Legend, Tooltip } from 'recharts';
import './Sources.css';

const Sources = () => {
  const octoberData = [
    { name: 'Переходы по рекламе', value: 70.35, count: 2981, color: '#9c27b0' },
    { name: 'Переходы из поисковых систем', value: 18.40, count: 780, color: '#e91e63' },
    { name: 'Прямые заходы', value: 6.09, count: 258, color: '#03a9f4' },
    { name: 'Переходы по ссылкам на сайтах', value: 4.43, count: 188, color: '#00bcd4' },
    { name: 'Внутренние переходы', value: 0.57, count: 24, color: '#ffc107' },
    { name: 'Остальные', value: 0.12, count: 5, color: '#9e9e9e' },
  ];

  const septemberData = [
    { name: 'Переходы по рекламе', value: 65.49, count: 3213, color: '#9c27b0' },
    { name: 'Переходы из поисковых систем', value: 24.46, count: 1200, color: '#e91e63' },
    { name: 'Прямые заходы', value: 5.12, count: 251, color: '#03a9f4' },
    { name: 'Переходы по ссылкам на сайтах', value: 4.42, count: 217, color: '#00bcd4' },
    { name: 'Внутренние переходы', value: 0.49, count: 24, color: '#ffc107' },
    { name: 'Остальные', value: 0.02, count: 1, color: '#9e9e9e' },
  ];

  const tableData = [
    {
      source: 'Итого и среднее',
      visits: 4906,
      visitors: 3871,
      bounces: '22.78%',
      depth: '1.55',
      time: '1:24'
    },
    {
      source: 'Переходы по рекламе',
      visits: 3213,
      visitors: 2504,
      bounces: '27.30%',
      depth: '1.35',
      time: '1:00'
    },
    {
      source: 'Переходы из поисковых систем',
      visits: 1200,
      visitors: 987,
      bounces: '18.50%',
      depth: '1.85',
      time: '2:15'
    },
    {
      source: 'Прямые заходы',
      visits: 251,
      visitors: 198,
      bounces: '15.20%',
      depth: '2.10',
      time: '3:30'
    },
  ];

  const renderPieChart = (data, total) => (
    <div className="sources__chart-wrapper">
      <ResponsiveContainer width="100%" height={300}>
        <PieChart>
          <Pie
            data={data}
            cx="50%"
            cy="50%"
            innerRadius={80}
            outerRadius={120}
            paddingAngle={2}
            dataKey="value"
          >
            {data.map((entry, index) => (
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

  return (
    <div className="sources">
      <div className="sources__header">
        <h1 className="sources__title">Источники трафика</h1>
      </div>

      <div className="sources__content">
        <div className="sources__charts">
          <div className="sources__chart-card">
            <div className="sources__chart-header">
              <div>
                <h3 className="sources__chart-title">Источники трафика</h3>
                <p className="sources__chart-subtitle">Визиты</p>
              </div>
              <div className="sources__chart-actions">
                <button className="sources__action-btn" title="Обновить">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <polyline points="23 4 23 10 17 10"></polyline>
                    <polyline points="1 20 1 14 7 14"></polyline>
                    <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                  </svg>
                </button>
                <button className="sources__action-btn" title="Еще">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <circle cx="12" cy="12" r="1"></circle>
                    <circle cx="12" cy="5" r="1"></circle>
                    <circle cx="12" cy="19" r="1"></circle>
                  </svg>
                </button>
              </div>
            </div>
            {renderPieChart(octoberData, 4239)}
            <div className="sources__chart-legend">
              {octoberData.map((item, index) => (
                <div key={index} className="sources__legend-item">
                  <span className="sources__legend-dot" style={{ backgroundColor: item.color }}></span>
                  <span className="sources__legend-text">{item.name}: {item.value}%</span>
                </div>
              ))}
            </div>
            <div className="sources__chart-month">Октябрь</div>
          </div>

          <div className="sources__chart-card">
            <div className="sources__chart-header">
              <div>
                <h3 className="sources__chart-title">Источники трафика</h3>
                <p className="sources__chart-subtitle">Визиты</p>
              </div>
              <div className="sources__chart-actions">
                <button className="sources__action-btn" title="Обновить">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <polyline points="23 4 23 10 17 10"></polyline>
                    <polyline points="1 20 1 14 7 14"></polyline>
                    <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                  </svg>
                </button>
                <button className="sources__action-btn" title="Еще">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <circle cx="12" cy="12" r="1"></circle>
                    <circle cx="12" cy="5" r="1"></circle>
                    <circle cx="12" cy="19" r="1"></circle>
                  </svg>
                </button>
              </div>
            </div>
            {renderPieChart(septemberData, 4906)}
            <div className="sources__chart-legend">
              {septemberData.map((item, index) => (
                <div key={index} className="sources__legend-item">
                  <span className="sources__legend-dot" style={{ backgroundColor: item.color }}></span>
                  <span className="sources__legend-text">{item.name}: {item.value}%</span>
                </div>
              ))}
            </div>
            <div className="sources__chart-month">Сентябрь</div>
          </div>

          <div className="sources__chart-card">
            <div className="sources__chart-header">
              <div>
                <h3 className="sources__chart-title">Источники трафика</h3>
                <p className="sources__chart-subtitle">Визиты</p>
              </div>
              <div className="sources__chart-actions">
                <button className="sources__action-btn" title="Обновить">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <polyline points="23 4 23 10 17 10"></polyline>
                    <polyline points="1 20 1 14 7 14"></polyline>
                    <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                  </svg>
                </button>
                <button className="sources__action-btn" title="Еще">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <circle cx="12" cy="12" r="1"></circle>
                    <circle cx="12" cy="5" r="1"></circle>
                    <circle cx="12" cy="19" r="1"></circle>
                  </svg>
                </button>
              </div>
            </div>
            {renderPieChart(septemberData, 4906)}
            <div className="sources__chart-legend">
              {septemberData.map((item, index) => (
                <div key={index} className="sources__legend-item">
                  <span className="sources__legend-dot" style={{ backgroundColor: item.color }}></span>
                  <span className="sources__legend-text">{item.name}: {item.value}%</span>
                </div>
              ))}
            </div>
            <div className="sources__chart-month">Сентябрь</div>
          </div>
        </div>

        <div className="sources__table-section">
          <h2 className="sources__table-title">Сентябрь</h2>
          <div className="sources__table-container">
            <table className="sources__table">
              <thead>
                <tr>
                  <th>
                    <input type="checkbox" />
                  </th>
                  <th>Визиты</th>
                  <th>Посетители</th>
                  <th>Отказы</th>
                  <th>Глубина просмотра</th>
                  <th>Время на сайте</th>
                </tr>
              </thead>
              <tbody>
                {tableData.map((row, index) => (
                  <tr key={index}>
                    <td>
                      <input type="checkbox" />
                    </td>
                    <td>{row.visits.toLocaleString('ru-RU')}</td>
                    <td>{row.visitors.toLocaleString('ru-RU')}</td>
                    <td>{row.bounces}</td>
                    <td>{row.depth}</td>
                    <td>{row.time}</td>
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

export default Sources;

