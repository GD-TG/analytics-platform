import React from 'react';
import './Dashboard.css';

const Dashboard = () => {
  return (
    <div className="dashboard">
      <div className="dashboard__header">
        <h1 className="dashboard__title">Добро пожаловать в Planica</h1>
        <p className="dashboard__subtitle">Ваша аналитическая платформа</p>
      </div>
      
      <div className="dashboard__body">
        <div className="dashboard-cards">
          <div className="dashboard-card">
            <h3>Продажи</h3>
            <p>Обзор продаж и метрики</p>
          </div>
          <div className="dashboard-card">
            <h3>Задачи</h3>
            <p>Текущие задачи и проекты</p>
          </div>
          <div className="dashboard-card">
            <h3>Финансы</h3>
            <p>Финансовые показатели</p>
          </div>
          <div className="dashboard-card">
            <h3>Аналитика</h3>
            <p>Отчеты и аналитика</p>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Dashboard;