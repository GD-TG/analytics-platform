import BasePage from '../BasePage/BasePage';

const Tasks = () => (
  <BasePage
    title="Задачи и проекты"
    subtitle="Управление задачами и проектами"
    renderContent={() => (
      <div>
        <p>Данные о задачах будут отображаться здесь после настройки API</p>
      </div>
    )}
  />
);

export default Tasks;

