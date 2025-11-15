import BasePage from '../BasePage/BasePage';

const Production = () => (
  <BasePage
    title="Производство"
    subtitle="Управление производством"
    renderContent={() => (
      <div>
        <p>Данные о производстве будут отображаться здесь после настройки API</p>
      </div>
    )}
  />
);

export default Production;

