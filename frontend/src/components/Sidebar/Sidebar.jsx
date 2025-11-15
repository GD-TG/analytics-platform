import React, { useState } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import './Sidebar.css';

// SVG иконки
const StatisticsIcon = () => (
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
    <line x1="18" y1="20" x2="18" y2="10"></line>
    <line x1="12" y1="20" x2="12" y2="4"></line>
    <line x1="6" y1="20" x2="6" y2="14"></line>
  </svg>
);

const VisitsIcon = () => (
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
    <circle cx="9" cy="7" r="4"></circle>
    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
  </svg>
);

const SourcesIcon = () => (
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
    <polyline points="23 4 23 10 17 10"></polyline>
    <polyline points="1 20 1 14 7 14"></polyline>
    <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
  </svg>
);

const AgeLeadIcon = () => (
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
    <circle cx="12" cy="7" r="4"></circle>
  </svg>
);

const PurchasesIcon = () => (
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
    <circle cx="9" cy="21" r="1"></circle>
    <circle cx="20" cy="21" r="1"></circle>
    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
  </svg>
);

const TasksIcon = () => (
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
    <polyline points="14 2 14 8 20 8"></polyline>
    <line x1="16" y1="13" x2="8" y2="13"></line>
    <line x1="16" y1="17" x2="8" y2="17"></line>
    <polyline points="10 9 9 9 8 9"></polyline>
  </svg>
);

const ResourcesIcon = () => (
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
    <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
    <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path>
    <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path>
  </svg>
);

const FinanceIcon = () => (
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
    <line x1="12" y1="1" x2="12" y2="23"></line>
    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
  </svg>
);

const LogisticsIcon = () => (
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
    <rect x="1" y="3" width="15" height="13"></rect>
    <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
    <circle cx="5.5" cy="18.5" r="2.5"></circle>
    <circle cx="18.5" cy="18.5" r="2.5"></circle>
  </svg>
);

const InnovationIcon = () => (
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
    <line x1="9" y1="18" x2="15" y2="18"></line>
    <line x1="10" y1="22" x2="14" y2="22"></line>
    <line x1="15.09" y1="14" x2="8.91" y2="14"></line>
    <line x1="17.13" y1="10" x2="6.87" y2="10"></line>
    <path d="M12 2v2"></path>
    <path d="M12 2a6 6 0 0 1 6 6v2a6 6 0 0 1-12 0V8a6 6 0 0 1 6-6z"></path>
  </svg>
);

const ProductionIcon = () => (
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
    <line x1="6" y1="11" x2="10" y2="11"></line>
    <line x1="6" y1="15" x2="10" y2="15"></line>
    <line x1="14" y1="11" x2="18" y2="11"></line>
    <line x1="14" y1="15" x2="18" y2="15"></line>
  </svg>
);

const CompanyIcon = () => (
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
    <polyline points="9 22 9 12 15 12 15 22"></polyline>
  </svg>
);

const MarketingIcon = () => (
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
    <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
    <path d="M2 17l10 5 10-5"></path>
    <path d="M2 12l10 5 10-5"></path>
  </svg>
);

const DocumentsIcon = () => (
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
    <polyline points="14 2 14 8 20 8"></polyline>
    <line x1="16" y1="13" x2="8" y2="13"></line>
    <line x1="16" y1="17" x2="8" y2="17"></line>
    <polyline points="10 9 9 9 8 9"></polyline>
  </svg>
);

const ProcessesIcon = () => (
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
    <polyline points="23 4 23 10 17 10"></polyline>
    <polyline points="1 20 1 14 7 14"></polyline>
    <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
  </svg>
);

const SettingsIcon = () => (
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
    <circle cx="12" cy="12" r="3"></circle>
    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
  </svg>
);

// Иконки для подменю
const FolderIcon = () => (
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
    <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
  </svg>
);

const ListIcon = () => (
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
    <line x1="8" y1="6" x2="21" y2="6"></line>
    <line x1="8" y1="12" x2="21" y2="12"></line>
    <line x1="8" y1="18" x2="21" y2="18"></line>
    <line x1="3" y1="6" x2="3.01" y2="6"></line>
    <line x1="3" y1="12" x2="3.01" y2="12"></line>
    <line x1="3" y1="18" x2="3.01" y2="18"></line>
  </svg>
);

const PieChartIcon = () => (
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
    <path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path>
    <path d="M22 12A10 10 0 0 0 12 2v10z"></path>
  </svg>
);

const UsersIcon = () => (
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
    <circle cx="9" cy="7" r="4"></circle>
    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
  </svg>
);

const WrenchIcon = () => (
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
  </svg>
);

const Sidebar = () => {
  const [isExpanded, setIsExpanded] = useState(false);
  const [hoveredItem, setHoveredItem] = useState(null);
  const navigate = useNavigate();
  const location = useLocation();

  // Структура подменю для каждого пункта
  const subMenus = {
    'tasks': [
      { label: 'Проекты', icon: FolderIcon, path: '/tasks/projects' },
      { label: 'Задачи', icon: TasksIcon, path: '/tasks/tasks' },
      { label: 'Шаблоны', icon: DocumentsIcon, path: '/tasks/templates' },
    ],
    'resources': [
      { label: 'Товары', icon: PurchasesIcon, path: '/resources/products' },
      { label: 'Склад', icon: ProductionIcon, path: '/resources/warehouse' },
    ],
    'finance': [
      { label: 'Поступления', icon: FinanceIcon, path: '/finance/income' },
      { label: 'Отделы', icon: UsersIcon, path: '/finance/departments' },
      { label: 'Оплата', icon: FinanceIcon, path: '/finance/payment' },
      { label: 'Расходы', icon: ResourcesIcon, path: '/finance/expenses' },
      { label: 'К выплате', icon: FinanceIcon, path: '/finance/payable' },
      { label: 'Счета', icon: DocumentsIcon, path: '/finance/accounts' },
    ],
    'innovation': [
      { label: 'Патенты', icon: DocumentsIcon, path: '/innovation/patents' },
      { label: 'Разработки', icon: InnovationIcon, path: '/innovation/developments' },
    ],
    'production': [
      { label: 'Процессы', icon: WrenchIcon, path: '/production/processes' },
      { label: 'Товар', icon: PurchasesIcon, path: '/production/products' },
      { label: 'Услуга', icon: TasksIcon, path: '/production/services' },
    ],
    'marketing': [
      { label: 'Кампании', icon: UsersIcon, path: '/marketing/campaigns' },
      { label: 'Инструменты', icon: WrenchIcon, path: '/marketing/tools' },
      { label: 'Сегменты', icon: PieChartIcon, path: '/marketing/segments' },
    ],
    'settings': [
      { label: 'Списки', icon: ListIcon, path: '/settings/lists' },
      { label: 'Роли', icon: UsersIcon, path: '/settings/roles' },
      { label: 'Стадии', icon: WrenchIcon, path: '/settings/stages' },
    ],
  };

  const menuItems = [
    { id: 'statistics', label: 'Статистика', icon: StatisticsIcon, path: '/statistics' },
    { id: 'visits', label: 'Визиты', icon: VisitsIcon, path: '/visits' },
    { id: 'sources', label: 'Источники', icon: SourcesIcon, path: '/sources' },
    { id: 'age-lead', label: 'Возраст/Лид', icon: AgeLeadIcon, path: '/age-lead' },
    { id: 'purchases', label: 'Закупки', icon: PurchasesIcon, path: '/purchases' },
    { id: 'tasks', label: 'Задачи и проекты', icon: TasksIcon, path: '/tasks', hasSubmenu: true },
    { id: 'resources', label: 'Ресурсы', icon: ResourcesIcon, path: '/resources', hasSubmenu: true },
    { id: 'finance', label: 'Финансы', icon: FinanceIcon, path: '/finance', hasSubmenu: true },
    { id: 'logistics', label: 'Логистика', icon: LogisticsIcon, path: '/logistics' },
    { id: 'innovation', label: 'Инноватика', icon: InnovationIcon, path: '/innovation', hasSubmenu: true },
    { id: 'production', label: 'Производство', icon: ProductionIcon, path: '/production', hasSubmenu: true },
    { id: 'company', label: 'Компания', icon: CompanyIcon, path: '/company' },
    { id: 'marketing', label: 'Маркетинг', icon: MarketingIcon, path: '/marketing', hasSubmenu: true },
    { id: 'documents', label: 'Документы', icon: DocumentsIcon, path: '/documents' },
    { id: 'processes', label: 'Процессы', icon: ProcessesIcon, path: '/processes' },
  ];

  const isActive = (path) => {
    return location.pathname === path || location.pathname.startsWith(path + '/');
  };

  const handleMouseEnter = (itemId) => {
    setIsExpanded(true);
    if (subMenus[itemId]) {
      setHoveredItem(itemId);
    }
  };

  const handleMouseLeave = () => {
    setIsExpanded(false);
    setHoveredItem(null);
  };

  return (
    <aside 
      className={`sidebar ${isExpanded ? 'sidebar--expanded' : 'sidebar--collapsed'}`}
      onMouseEnter={() => setIsExpanded(true)}
      onMouseLeave={handleMouseLeave}
    >
      <nav className="sidebar__nav">
        <ul className="sidebar__menu">
          {menuItems.map((item) => {
            const IconComponent = item.icon;
            const hasSubmenu = subMenus[item.id];
            const isHovered = hoveredItem === item.id;
            
            return (
              <li 
                key={item.id} 
                className="sidebar__menu-item"
                onMouseEnter={() => hasSubmenu && setHoveredItem(item.id)}
                onMouseLeave={() => hasSubmenu && setHoveredItem(null)}
              >
                <button
                  className={`sidebar__menu-link ${isActive(item.path) ? 'sidebar__menu-link--active' : ''}`}
                  onClick={() => navigate(item.path)}
                  title={!isExpanded ? item.label : ''}
                >
                  <span className="sidebar__menu-icon">
                    <IconComponent />
                  </span>
                  {isExpanded && (
                    <span className="sidebar__menu-label">{item.label}</span>
                  )}
                </button>
                
                {/* Подменю */}
                {hasSubmenu && isHovered && isExpanded && (
                  <div className="sidebar__submenu">
                    {subMenus[item.id].map((subItem) => {
                      const SubIcon = subItem.icon;
                      return (
                        <button
                          key={subItem.path}
                          className={`sidebar__submenu-item ${isActive(subItem.path) ? 'sidebar__submenu-item--active' : ''}`}
                          onClick={() => navigate(subItem.path)}
                        >
                          <span className="sidebar__submenu-icon">
                            <SubIcon />
                          </span>
                          <span className="sidebar__submenu-label">{subItem.label}</span>
                        </button>
                      );
                    })}
                  </div>
                )}
              </li>
            );
          })}
        </ul>
      </nav>

      <div className="sidebar__divider"></div>

      <div className="sidebar__footer">
        <div
          className="sidebar__menu-item"
          onMouseEnter={() => setHoveredItem('settings')}
          onMouseLeave={() => setHoveredItem(null)}
        >
          <button
            className={`sidebar__settings ${isActive('/settings') ? 'sidebar__settings--active' : ''}`}
            onClick={() => navigate('/settings')}
            title={!isExpanded ? 'Настройки' : ''}
          >
            <span className="sidebar__settings-icon">
              <SettingsIcon />
            </span>
            {isExpanded && <span className="sidebar__settings-label">Настройки</span>}
          </button>
          
          {/* Подменю для настроек */}
          {hoveredItem === 'settings' && isExpanded && subMenus.settings && (
            <div className="sidebar__submenu">
              {subMenus.settings.map((subItem) => {
                const SubIcon = subItem.icon;
                return (
                  <button
                    key={subItem.path}
                    className={`sidebar__submenu-item ${isActive(subItem.path) ? 'sidebar__submenu-item--active' : ''}`}
                    onClick={() => navigate(subItem.path)}
                  >
                    <span className="sidebar__submenu-icon">
                      <SubIcon />
                    </span>
                    <span className="sidebar__submenu-label">{subItem.label}</span>
                  </button>
                );
              })}
            </div>
          )}
        </div>
      </div>
    </aside>
  );
};

export default Sidebar;
