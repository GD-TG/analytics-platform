import React from 'react';
import './Statistics.css';

const Statistics = () => {
  const metrics = [
    {
      id: 1,
      indicator: 'Посетители, кол-во',
      october: 3246,
      september: 3971,
      august: 3476,
      efficiency: -18.26,
      isNegative: true
    },
    {
      id: 2,
      indicator: 'Новые посетители, кол-во',
      october: 2958,
      september: 3642,
      august: 3134,
      efficiency: -18.78,
      isNegative: true
    },
    {
      id: 3,
      indicator: 'Визиты, кол-во',
      october: 4208,
      september: 4906,
      august: 4356,
      efficiency: -13.30,
      isNegative: true
    },
    {
      id: 4,
      indicator: 'Кол-во отказов, %',
      october: 28.00,
      september: 22.78,
      august: 26.80,
      efficiency: 25.81,
      isNegative: false
    },
    {
      id: 5,
      indicator: 'Время на сайте, сек',
      october: 162,
      september: 124,
      august: 118,
      efficiency: 21.43,
      isNegative: false
    },
    {
      id: 6,
      indicator: 'Всего заявок, кол-во',
      october: 85,
      september: 98,
      august: 81,
      efficiency: -15.30,
      isNegative: true
    },
    {
      id: 7,
      indicator: 'Клик на номер, кол-во',
      october: 26,
      september: 308,
      august: 109,
      efficiency: -91.56,
      isNegative: true
    },
    {
      id: 8,
      indicator: 'Заявка',
      october: 12,
      september: 12,
      august: 8,
      efficiency: 0.00,
      isNegative: false
    },
    {
      id: 9,
      indicator: 'Спецпредложения',
      october: 29,
      september: 29,
      august: 24,
      efficiency: 0.00,
      isNegative: false
    },
    {
      id: 10,
      indicator: 'Лизинг',
      october: 0,
      september: 1,
      august: 1,
      efficiency: -100.00,
      isNegative: true
    },
    {
      id: 11,
      indicator: 'Тест-драйв',
      october: 4,
      september: 3,
      august: 6,
      efficiency: 33.33,
      isNegative: false
    },
    {
      id: 12,
      indicator: 'Госпрограмма',
      october: 1,
      september: 5,
      august: 6,
      efficiency: -80.00,
      isNegative: true
    },
    {
      id: 13,
      indicator: 'Звонок CallTouch',
      october: 37,
      september: 41,
      august: 32,
      efficiency: -9.76,
      isNegative: true
    },
    {
      id: 14,
      indicator: 'Таймер',
      october: 0,
      september: 7,
      august: 4,
      efficiency: -100.00,
      isNegative: true
    }
  ];

  const summaryMetrics = [
    { label: 'Трафик', value: 'Упал на 13%', isNegative: true },
    { label: 'Число конверсий', value: 'Упало на 15%', isNegative: true },
    { label: 'Количество отказов', value: 'Выросло на 26%', isNegative: false }
  ];

  return (
    <div className="statistics">
      <div className="statistics__header">
        <h1 className="statistics__title">Статистика / Аналитика сайта</h1>
      </div>

      <div className="statistics__content">
        <div className="statistics__summary">
          {summaryMetrics.map((metric, index) => (
            <div key={index} className="statistics__summary-card">
              <div className="statistics__summary-label">{metric.label}</div>
              <div className={`statistics__summary-value ${metric.isNegative ? 'statistics__summary-value--negative' : 'statistics__summary-value--positive'}`}>
                {metric.isNegative ? '↓' : '↑'} {metric.value}
              </div>
            </div>
          ))}
        </div>

        <div className="statistics__table-container">
          <table className="statistics__table">
            <thead>
              <tr>
                <th>№ п/п</th>
                <th>Показатель</th>
                <th>Октябрь</th>
                <th>Сентябрь</th>
                <th>Август</th>
                <th>Эффективность, %</th>
              </tr>
            </thead>
            <tbody>
              {metrics.map((metric) => (
                <tr key={metric.id}>
                  <td>{metric.id}</td>
                  <td>{metric.indicator}</td>
                  <td>{metric.october.toLocaleString('ru-RU')}</td>
                  <td>{metric.september.toLocaleString('ru-RU')}</td>
                  <td>{metric.august.toLocaleString('ru-RU')}</td>
                  <td className={metric.isNegative ? 'statistics__efficiency--negative' : 'statistics__efficiency--positive'}>
                    {metric.efficiency > 0 ? '+' : ''}{metric.efficiency.toFixed(2)}%
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
};

export default Statistics;

