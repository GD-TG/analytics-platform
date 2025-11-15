import BasePage from '../BasePage/BasePage';

const Innovation = () => (
  <BasePage
    title="Инноватика"
    subtitle="Инновационные проекты и разработки"
    renderContent={() => (
      <div>
        <p>Данные об инновациях будут отображаться здесь после настройки API</p>
      </div>
    )}
  />
);

export default Innovation;

