# pc-conf

/pc-configurator/
├── index.php              # Главная страница конфигуратора
├── config.php             # Настройки подключения к БД
├── functions.php          # Вспомогательные функции
├── db/
│   └── database.sql       # SQL-скрипт для создания БД
├── components/
│   ├── header.php         # Шапка сайта
│   └── footer.php         # Подвал сайта
├── api/
│   ├── get_components.php # API для получения комплектующих
│   └── save_config.php    # API для сохранения конфигурации
├── assets/
│   ├── css/
│   │   └── style.css      # Пользовательские стили
│   ├── js/
│   │   └── configurator.js # Скрипт для работы конфигуратора
│   └── img/               # Изображения комплектующих
└── admin/                 # Админ-панель для управления базой комплектующих
    ├── index.php
    ├── components.php
    └── add_component.php
