import api from './http.js';

export const reportsApi = {
  // Получить полный отчет для проекта
  getReport: (projectId) => {
    return api.get(`/api/report/${projectId}`);
  },

  // Получить статистику
  getStatistics: (projectId = null) => {
    return api.get('/api/statistics', projectId ? { project_id: projectId } : {});
  },

  // Получить данные визитов
  getVisits: (projectId = null) => {
    return api.get('/api/visits', projectId ? { project_id: projectId } : {});
  },

  // Получить источники трафика
  getSources: (projectId = null) => {
    return api.get('/api/sources', projectId ? { project_id: projectId } : {});
  },

  // Получить возрастные данные
  getAgeData: (projectId = null) => {
    return api.get('/api/age-data', projectId ? { project_id: projectId } : {});
  },

  // Получить проекты с термометром
  getProjectsWithThermometer: () => {
    return api.get('/api/projects-thermometer');
  },
};

