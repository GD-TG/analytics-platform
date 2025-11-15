import BasePage from '../BasePage/BasePage';

const Company = () => (
  <BasePage
    title="Компания"
    subtitle="Информация о компании"
    renderContent={() => (
      <div>
        <p>Данные о компании будут отображаться здесь после настройки API</p>
      </div>
    )}
  />
);

export default Company;

