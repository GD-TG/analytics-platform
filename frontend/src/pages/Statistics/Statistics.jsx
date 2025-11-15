import React, { useState, useEffect, useRef } from 'react';
import { reportsApi } from '../../api/reports';
import { generatePDFReport } from '../../utils/pdfGenerator';
import './Statistics.css';

const Statistics = () => {
  const [metrics, setMetrics] = useState([]);
  const [summaryMetrics, setSummaryMetrics] = useState([]);
  const [loading, setLoading] = useState(true);
  const [monthColors, setMonthColors] = useState({});
  const [exportingPDF, setExportingPDF] = useState(false);
  const reportRef = useRef(null);

  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    try {
      setLoading(true);
      const response = await reportsApi.getStatistics();
      
      if (response.success && response.data && response.data.length > 0) {
        const data = response.data;
        
        // Формируем метрики для таблицы
        const metricsData = formatMetricsData(data);
        setMetrics(metricsData);
        
        // Формируем summary метрики
        const summary = calculateSummaryMetrics(data);
        setSummaryMetrics(summary);
        
        // Рассчитываем цвета месяцев
        const colors = calculateMonthColors(data);
        setMonthColors(colors);
      } else {
        // Нет данных - показываем пустое состояние
        setMetrics([]);
        setSummaryMetrics([]);
      }
    } catch (error) {
      console.error('Failed to load statistics:', error);
      // Не показываем тестовые данные - только ошибку
      setMetrics([]);
      setSummaryMetrics([]);
    } finally {
      setLoading(false);
    }
  };

  const formatMetricsData = (data) => {
    if (!data || data.length < 3) {
      return [];
    }

    // Сортируем по дате (M, M-1, M-2)
    const sorted = [...data].sort((a, b) => {
      return new Date(a.month) - new Date(b.month);
    });

    const [august, september, october] = sorted;

    return [
      {
        id: 1,
        indicator: 'Посетители, кол-во',
        october: october?.users || 0,
        september: september?.users || 0,
        august: august?.users || 0,
        efficiency: calculateEfficiency(october?.users, september?.users),
        isNegative: (october?.users || 0) < (september?.users || 0),
      },
      {
        id: 2,
        indicator: 'Новые посетители, кол-во',
        october: october?.users || 0,
        september: september?.users || 0,
        august: august?.users || 0,
        efficiency: calculateEfficiency(october?.users, september?.users),
        isNegative: (october?.users || 0) < (september?.users || 0),
      },
      {
        id: 3,
        indicator: 'Визиты, кол-во',
        october: october?.visits || 0,
        september: september?.visits || 0,
        august: august?.visits || 0,
        efficiency: calculateEfficiency(october?.visits, september?.visits),
        isNegative: (october?.visits || 0) < (september?.visits || 0),
      },
      {
        id: 4,
        indicator: 'Кол-во отказов, %',
        october: october?.bounce_rate || 0,
        september: september?.bounce_rate || 0,
        august: august?.bounce_rate || 0,
        efficiency: calculateEfficiency(october?.bounce_rate, september?.bounce_rate),
        isNegative: (october?.bounce_rate || 0) > (september?.bounce_rate || 0),
      },
      {
        id: 5,
        indicator: 'Время на сайте, сек',
        october: october?.avg_duration || 0,
        september: september?.avg_duration || 0,
        august: august?.avg_duration || 0,
        efficiency: calculateEfficiency(october?.avg_duration, september?.avg_duration),
        isNegative: (october?.avg_duration || 0) < (september?.avg_duration || 0),
      },
      {
        id: 6,
        indicator: 'Всего заявок, кол-во',
        october: october?.conversions || 0,
        september: september?.conversions || 0,
        august: august?.conversions || 0,
        efficiency: calculateEfficiency(october?.conversions, september?.conversions),
        isNegative: (october?.conversions || 0) < (september?.conversions || 0),
      },
    ];
  };

  const calculateEfficiency = (current, previous) => {
    if (!previous || previous === 0) return 0;
    return ((current - previous) / previous) * 100;
  };

  const calculateSummaryMetrics = (data) => {
    if (!data || data.length < 2) {
      return [];
    }

    const [september, october] = data.slice(-2).reverse();
    
    const trafficChange = calculateEfficiency(october?.visits, september?.visits);
    const conversionsChange = calculateEfficiency(october?.conversions, september?.conversions);
    const bounceChange = calculateEfficiency(october?.bounce_rate, september?.bounce_rate);

    return [
      { 
        label: 'Трафик', 
        value: `${trafficChange > 0 ? 'Вырос' : 'Упал'} на ${Math.abs(trafficChange).toFixed(0)}%`, 
        isNegative: trafficChange < 0 
      },
      { 
        label: 'Число конверсий', 
        value: `${conversionsChange > 0 ? 'Выросло' : 'Упало'} на ${Math.abs(conversionsChange).toFixed(0)}%`, 
        isNegative: conversionsChange < 0 
      },
      { 
        label: 'Количество отказов', 
        value: `${bounceChange > 0 ? 'Выросло' : 'Упало'} на ${Math.abs(bounceChange).toFixed(0)}%`, 
        isNegative: bounceChange > 0 
      },
    ];
  };

  const calculateMonthColors = (data) => {
    if (!data || data.length < 2) return {};
    
    const colors = {};
    const keyMetrics = ['visits', 'conversions', 'users'];
    
    data.forEach((month, index) => {
      if (index === 0) return; // Пропускаем первый месяц
      
      const prevMonth = data[index - 1];
      let positiveCount = 0;
      let negativeCount = 0;
      
      keyMetrics.forEach(metric => {
        const current = month[metric] || 0;
        const previous = prevMonth[metric] || 0;
        
        if (current > previous) {
          positiveCount++;
        } else if (current < previous) {
          negativeCount++;
        }
      });
      
      // 🟢 хороший → рост ключевых метрик
      // 🟡 средний → стабильность
      // 🔴 плохой → падение
      if (positiveCount > negativeCount) {
        colors[month.month] = 'green';
      } else if (negativeCount > positiveCount) {
        colors[month.month] = 'red';
      } else {
        colors[month.month] = 'yellow';
      }
    });
    
    return colors;
  };


  const getMonthColorClass = (month) => {
    const color = monthColors[month] || 'yellow';
    return `statistics__month--${color}`;
  };

  const formatMonthName = (monthStr) => {
    if (!monthStr) return '';
    const [year, month] = monthStr.split('-');
    const date = new Date(year, month - 1);
    return date.toLocaleDateString('ru-RU', { month: 'long', year: 'numeric' });
  };

  if (loading) {
    return (
      <div className="statistics">
        <div className="statistics__loading">Загрузка данных...</div>
      </div>
    );
  }

  if (metrics.length === 0) {
    return (
      <div className="statistics">
        <div className="statistics__header">
          <h1 className="statistics__title">Статистика / Аналитика сайта</h1>
        </div>
        <div className="statistics__empty">
          <p>Нет данных для отображения</p>
          <p className="statistics__empty-hint">
            Убедитесь, что OAuth токен настроен и выполнена синхронизация данных
          </p>
        </div>
      </div>
    );
  }

  const handleExportPDF = async () => {
    try {
      setExportingPDF(true);
      const filename = `statistics-report-${new Date().toISOString().split('T')[0]}.pdf`;
      await generatePDFReport('statistics-report', filename, {
        orientation: 'landscape',
        format: 'a4',
      });
    } catch (error) {
      console.error('Failed to export PDF:', error);
      const errorMessage = error.message.includes('not installed') 
        ? 'PDF библиотеки не установлены. Выполните: npm install jspdf html2canvas'
        : 'Ошибка при экспорте PDF. Попробуйте еще раз.';
      alert(errorMessage);
    } finally {
      setExportingPDF(false);
    }
  };

  return (
    <div className="statistics" id="statistics-report" ref={reportRef}>
      <div className="statistics__header">
        <h1 className="statistics__title">Статистика / Аналитика сайта</h1>
        <button
          className="statistics__export-btn"
          onClick={handleExportPDF}
          disabled={exportingPDF || metrics.length === 0}
          title="Экспортировать в PDF"
        >
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
            <polyline points="7 10 12 15 17 10"></polyline>
            <line x1="12" y1="15" x2="12" y2="3"></line>
          </svg>
          {exportingPDF ? 'Экспорт...' : 'Экспорт PDF'}
        </button>
      </div>

      <div className="statistics__content">
        {summaryMetrics.length > 0 && (
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
        )}

        <div className="statistics__table-container">
          <table className="statistics__table">
            <thead>
              <tr>
                <th>№ п/п</th>
                <th>Показатель</th>
                <th className={getMonthColorClass('2024-10')}>Октябрь</th>
                <th className={getMonthColorClass('2024-09')}>Сентябрь</th>
                <th className={getMonthColorClass('2024-08')}>Август</th>
                <th>Эффективность, %</th>
              </tr>
            </thead>
            <tbody>
              {metrics.map((metric) => (
                <tr key={metric.id}>
                  <td>{metric.id}</td>
                  <td>{metric.indicator}</td>
                  <td 
                    className={getMonthColorClass('2024-10')}
                    data-hover={`Было: ${metric.september.toLocaleString('ru-RU')} → Стало: ${metric.october.toLocaleString('ru-RU')}\nРазница: ${metric.october - metric.september > 0 ? '+' : ''}${(metric.october - metric.september).toLocaleString('ru-RU')} / ${metric.efficiency > 0 ? '+' : ''}${metric.efficiency.toFixed(2)}%`}
                  >
                    {metric.october.toLocaleString('ru-RU')}
                  </td>
                  <td 
                    className={getMonthColorClass('2024-09')}
                    data-hover={`Было: ${metric.august.toLocaleString('ru-RU')} → Стало: ${metric.september.toLocaleString('ru-RU')}\nРазница: ${metric.september - metric.august > 0 ? '+' : ''}${(metric.september - metric.august).toLocaleString('ru-RU')} / ${calculateEfficiency(metric.september, metric.august) > 0 ? '+' : ''}${calculateEfficiency(metric.september, metric.august).toFixed(2)}%`}
                  >
                    {metric.september.toLocaleString('ru-RU')}
                  </td>
                  <td className={getMonthColorClass('2024-08')}>
                    {metric.august.toLocaleString('ru-RU')}
                  </td>
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
