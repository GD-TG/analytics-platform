import BasePage from '../BasePage/BasePage';

const Processes = () => (
  <BasePage
    title="Процессы"
    subtitle="Управление процессами"
    renderContent={() => (
      <div>
        <p>Данные о процессах будут отображаться здесь после настройки API</p>
      </div>
    )}
  />
);

export default Processes;

