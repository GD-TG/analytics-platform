import React, { useState } from 'react';
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

  const ageGroups = [
    { key: 'total', label: 'Итого и средние' },
    { key: 'under18', label: 'младше 18 лет' },
    { key: '18-24', label: '18-24 года' },
    { key: '25-34', label: '25-34 года' },
    { key: '35-44', label: '35-44 года' },
    { key: '45-54', label: '45-54 года' },
    { key: '55+', label: '55 лет и старше' },
  ];

  const septemberData = [
    { age: 'total', visits: 3061, visitors: 2364, bounces: '20.52%', depth: '1.58', time: '1:38', views: 4835 },
    { age: 'under18', visits: 1, visitors: 1, bounces: '0.00%', depth: '1.00', time: '0:00', views: 1 },
    { age: '18-24', visits: 3, visitors: 3, bounces: '0.00%', depth: '1.00', time: '0:00', views: 3 },
    { age: '25-34', visits: 37, visitors: 35, bounces: '5.41%', depth: '1.35', time: '0:45', views: 50 },
    { age: '35-44', visits: 20, visitors: 25, bounces: '0.00%', depth: '1.20', time: '1:15', views: 24 },
    { age: '45-54', visits: 16, visitors: 15, bounces: '6.25%', depth: '1.31', time: '0:52', views: 21 },
    { age: '55+', visits: 13, visitors: 13, bounces: '0.00%', depth: '1.15', time: '0:38', views: 15 },
  ];

  const octoberData = [
    { age: 'total', visits: 3095, visitors: 2369, bounces: '24.33%', depth: '1.49', time: '1:47', views: 4601 },
    { age: 'under18', visits: 5, visitors: 5, bounces: '0.00%', depth: '3.00', time: '9:50', views: 15 },
    { age: '18-24', visits: 17, visitors: 16, bounces: '11.76%', depth: '2.12', time: '5:42', views: 36 },
    { age: '25-34', visits: 13, visitors: 13, bounces: '0.00%', depth: '3.08', time: '11:22', views: 40 },
    { age: '35-44', visits: 0, visitors: 0, bounces: '-', depth: '-', time: '-', views: 0 },
    { age: '45-54', visits: 12, visitors: 11, bounces: '0.00%', depth: '1.58', time: '7:09', views: 19 },
    { age: '55+', visits: 7, visitors: 7, bounces: '0.00%', depth: '2.29', time: '7:42', views: 16 },
  ];

  const toggleAge = (key) => {
    setSelectedAges(prev => ({ ...prev, [key]: !prev[key] }));
  };

  const renderTable = (title, data) => (
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
                {data.map((row, index) => (
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

  return (
    <div className="age-lead">
      <div className="age-lead__header">
        <h1 className="age-lead__title">Лид</h1>
      </div>

      <div className="age-lead__content">
        {renderTable('Сентябрь', septemberData)}
        {renderTable('Октябрь', octoberData)}
      </div>
    </div>
  );
};

export default AgeLead;

