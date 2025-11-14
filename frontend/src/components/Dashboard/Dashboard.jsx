import React, { useState, useEffect } from 'react';
import { reportsApi } from '../../api/reports';
import './Dashboard.css';

const Dashboard = () => {
  const [projects, setProjects] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadProjects();
  }, []);

  const loadProjects = async () => {
    try {
      setLoading(true);
      const response = await reportsApi.getProjectsWithThermometer();
      
      if (response.success && response.data) {
        setProjects(response.data);
      }
    } catch (error) {
      console.error('Failed to load projects:', error);
      // Fallback на тестовые данные
      setProjects([
        { id: 1, name: 'Проект 1', thermometer: '🔥' },
        { id: 2, name: 'Проект 2', thermometer: '🌤' },
        { id: 3, name: 'Проект 3', thermometer: '❄' },
      ]);
    } finally {
      setLoading(false);
    }
  };

  const getThermometerLabel = (thermometer) => {
    switch (thermometer) {
      case '🔥':
        return 'Проект растёт';
      case '🌤':
        return 'Стабильно';
      case '❄':
        return 'Есть падения';
      default:
        return 'Стабильно';
    }
  };

  const getThermometerClass = (thermometer) => {
    switch (thermometer) {
      case '🔥':
        return 'dashboard__thermometer--hot';
      case '🌤':
        return 'dashboard__thermometer--stable';
      case '❄':
        return 'dashboard__thermometer--cold';
      default:
        return 'dashboard__thermometer--stable';
    }
  };

  if (loading) {
    return (
      <div className="dashboard">
        <div className="dashboard__loading">Загрузка проектов...</div>
      </div>
    );
  }

  return (
    <div className="dashboard">
      <div className="dashboard__header">
        <h1 className="dashboard__title">Аналитический термометр</h1>
        <p className="dashboard__subtitle">Статус проектов на основе ключевых метрик</p>
      </div>
      
      <div className="dashboard__body">
        <div className="dashboard__projects">
          {projects.map((project) => (
            <div key={project.id} className="dashboard__project-card">
              <div className="dashboard__project-header">
                <h3 className="dashboard__project-name">{project.name}</h3>
                <div className={`dashboard__thermometer ${getThermometerClass(project.thermometer)}`}>
                  <span className="dashboard__thermometer-icon">{project.thermometer}</span>
                  <span className="dashboard__thermometer-label">
                    {getThermometerLabel(project.thermometer)}
                  </span>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};

export default Dashboard;
