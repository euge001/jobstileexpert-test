-- 1. Деньги, количества и веса были в DOUBLE → перевёл в DECIMAL, чтобы не ловить плавающие копейки.
-- 2. Все булевые флаги (tinyint(1)/smallint) были NULL → сделал NOT NULL с вменяемыми дефолтами (0/1), чтобы не было третьего состояния.
-- 3. Добавил уникальность для hash, token и number, чтобы не было дублей одного и того же заказа.
-- 4. В orders_article сделал orders_id и article_id обязательными и повесил FOREIGN KEY на orders(id), плюс уникальную пару (orders_id, article_id) на случай, если один артикул в заказе должен идти одной строкой.
-- 5. Выровнял measure до VARCHAR(3) в обеих таблицах.
-- 6.Индексы: оставил составной по (create_date, status) и не создаю отдельный по одному create_date, т.к. смысла в дубле нет.




DROP TABLE IF EXISTS orders_article;
DROP TABLE IF EXISTS orders;

CREATE TABLE orders
(
    id                         INT UNSIGNED AUTO_INCREMENT,
    hash                       VARCHAR(32)              NOT NULL COMMENT 'hash заказа',
    user_id                    INT UNSIGNED            NULL,
    token                      VARCHAR(64)              NOT NULL COMMENT 'уникальный хеш пользователя',
    number                     VARCHAR(10)              NULL COMMENT 'Номер заказа',
    status                     INT        NOT NULL DEFAULT 1 COMMENT 'Статус заказа',
    email                      VARCHAR(100)             NULL COMMENT 'контактный E-mail',
    vat_type                   INT        NOT NULL DEFAULT 0 COMMENT 'Частное лицо или плательщик НДС',
    vat_number                 VARCHAR(100)             NULL COMMENT 'НДС-номер',
    tax_number                 VARCHAR(50)              NULL COMMENT 'Индивидуальный налоговый номер налогоплательщика',
    discount                   SMALLINT   NOT NULL DEFAULT 0 COMMENT 'Процент скидки',
    delivery                   DECIMAL(12,2)           NULL COMMENT 'Стоимость доставки',
    delivery_type              SMALLINT   NOT NULL DEFAULT 0 COMMENT 'Тип доставки: 0 - адрес клинта, 1 - адрес склада',
    delivery_time_min          DATE                     NULL COMMENT 'Минимальный срок доставки',
    delivery_time_max          DATE                     NULL COMMENT 'Максимальный срок доставки',
    delivery_time_confirm_min  DATE                     NULL COMMENT 'Минимальный срок доставки подтверждённый производителем',
    delivery_time_confirm_max  DATE                     NULL COMMENT 'Максимальный срок доставки подтверждённый производителем',
    delivery_time_fast_pay_min DATE                     NULL COMMENT 'Минимальный срок доставки',
    delivery_time_fast_pay_max DATE                     NULL COMMENT 'Максимальный срок доставки',
    delivery_old_time_min      DATE                     NULL COMMENT 'Прошлый минимальный срок доставки',
    delivery_old_time_max      DATE                     NULL COMMENT 'Прошлый максимальный срок доставки',
    delivery_index             VARCHAR(20)              NULL,
    delivery_country           INT UNSIGNED            NULL,
    delivery_region            VARCHAR(50)              NULL,
    delivery_city              VARCHAR(200)             NULL,
    delivery_address           VARCHAR(300)             NULL,
    delivery_building          VARCHAR(200)             NULL,
    delivery_phone_code        VARCHAR(20)              NULL,
    delivery_phone             VARCHAR(20)              NULL,
    sex                        SMALLINT                 NULL COMMENT 'Пол клиента',
    client_name                VARCHAR(255)             NULL COMMENT 'Имя клиента',
    client_surname             VARCHAR(255)             NULL COMMENT 'Фамилия клиента',
    company_name               VARCHAR(255)             NULL COMMENT 'Название компании',
    pay_type                   SMALLINT                 NOT NULL COMMENT 'Выбранный тип оплаты',
    pay_date_execution         DATETIME                 NULL COMMENT 'Дата до которой действует текущая цена заказа',
    offset_date                DATETIME                 NULL COMMENT 'Дата сдвига предполагаемого расчета доставки',
    offset_reason              SMALLINT                 NULL COMMENT 'тип причина сдвига сроков 1 - каникулы на фабрике, 2 - фабрика уточняет сроки пр-ва, 3 - другое',
    proposed_date              DATETIME                 NULL COMMENT 'Предполагаемая дата поставки',
    ship_date                  DATETIME                 NULL COMMENT 'Предполагаемая дата отгрузки',
    tracking_number            VARCHAR(50)              NULL COMMENT 'Номер треккинга',
    manager_name               VARCHAR(20)              NULL COMMENT 'Имя менеджера сопровождающего заказ',
    manager_email              VARCHAR(30)              NULL COMMENT 'Email менеджера сопровождающего заказ',
    manager_phone              VARCHAR(20)              NULL COMMENT 'Телефон менеджера сопровождающего заказ',
    carrier_name               VARCHAR(50)              NULL COMMENT 'Название транспортной компании',
    carrier_contact_data       VARCHAR(255)             NULL COMMENT 'Контактные данные транспортной компании',
    locale                     VARCHAR(5)               NOT NULL COMMENT 'локаль из которой был оформлен заказ',
    cur_rate                   DECIMAL(12,6) NULL DEFAULT 1 COMMENT 'курс на момент оплаты',
    currency                   VARCHAR(3)  NOT NULL DEFAULT 'EUR' COMMENT 'валюта при которой был оформлен заказ',
    measure                    VARCHAR(3)  NOT NULL DEFAULT 'm'   COMMENT 'ед. изм. в которой был оформлен заказ',
    name                       VARCHAR(200)             NOT NULL COMMENT 'Название заказа',
    description                VARCHAR(1000)            NULL COMMENT 'Дополнительная информация',
    create_date                DATETIME                 NOT NULL COMMENT 'Дата создания',
    update_date                DATETIME                 NULL COMMENT 'Дата изменения',
    warehouse_data             LONGTEXT                 NULL COMMENT 'Данные склада: адрес, название, часы работы',
    step                       TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'если true то заказ не будет сброшен в следствии изменений',
    address_equal              TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Адреса плательщика и получателя совпадают (false - разные, true - одинаковые )',
    bank_transfer_requested    TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Запрашивался ли счет на банковский перевод',
    accept_pay                 TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Если true то заказ отправлен в работу',
    cancel_date                DATETIME                 NULL COMMENT 'Конечная дата согласования сроков поставки',
    weight_gross               DECIMAL(12,3)           NULL COMMENT 'Общий вес брутто заказа',
    product_review             TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Оставлен отзыв по коллекциям в заказе',
    mirror                     SMALLINT                 NULL COMMENT 'Метка зеркала на котором создается заказ',
    process                    TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'метка массовой обработки',
    fact_date                  DATETIME                 NULL COMMENT 'Фактическая дата поставки',
    entrance_review            SMALLINT                 NULL COMMENT 'Фиксирует вход клиента на страницу отзыва и последующие клики',
    payment_euro               TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Если true, то оплату посчитать в евро',
    spec_price                 TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'установлена спец цена по заказу',
    show_msg                   TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Показывать спец. сообщение',
    delivery_price_euro        DECIMAL(12,2)           NULL COMMENT 'Стоимость доставки в евро',
    address_payer              INT UNSIGNED            NULL,
    sending_date               DATETIME                 NULL COMMENT 'Расчетная дата поставки',
    delivery_calculate_type    SMALLINT   NOT NULL DEFAULT 0 COMMENT 'Тип расчета: 0 - ручной, 1 - автоматический',
    full_payment_date          DATE                     NULL COMMENT 'Дата полной оплаты заказа',
    bank_details               LONGTEXT                 NULL COMMENT 'Реквизиты банка для возврата средств',
    delivery_apartment_office  VARCHAR(30)              NULL COMMENT 'Квартира/офис',

    PRIMARY KEY (id),
    UNIQUE KEY uq_orders_hash   (hash),
    UNIQUE KEY uq_orders_token  (token),
    UNIQUE KEY uq_orders_number (number),
    KEY IDX_1 (delivery_country),
    KEY IDX_2 (user_id),
    KEY IDX_4 (create_date, status)
)
ENGINE=InnoDB
COMMENT 'Хранит информацию о заказах';


CREATE TABLE orders_article
(
    id                        INT UNSIGNED AUTO_INCREMENT,
    orders_id                 INT UNSIGNED      NOT NULL,
    article_id                INT UNSIGNED      NOT NULL COMMENT 'ID коллекции',
    amount                    DECIMAL(12,4)    NOT NULL COMMENT 'количество артикулов в ед. измерения',
    price                     DECIMAL(12,2)    NOT NULL COMMENT 'Цена на момент оплаты заказа',
    price_eur                 DECIMAL(12,2)    NULL COMMENT 'Цена в Евро по заказу',
    currency                  VARCHAR(3)       NULL COMMENT 'Валюта для которой установлена цена',
    measure                   VARCHAR(3)       NULL COMMENT 'Ед. изм. для которой установлена цена',
    delivery_time_min         DATE             NULL COMMENT 'Минимальный срок доставки',
    delivery_time_max         DATE             NULL COMMENT 'Максимальный срок доставки',
    weight                    DECIMAL(12,3)    NOT NULL COMMENT 'вес упаковки',
    multiple_pallet           SMALLINT         NULL COMMENT 'Кратность палете, 1 - кратно упаковке, 2 - кратно палете, 3 - не меньше палеты',
    packaging_count           DECIMAL(12,4)    NOT NULL COMMENT 'Количество кратно которому можно добавлять товар в заказ',
    pallet                    DECIMAL(12,4)    NOT NULL COMMENT 'количество в палете на момент заказа',
    packaging                 DECIMAL(12,4)    NOT NULL COMMENT 'количество в упаковке',
    swimming_pool             TINYINT(1)       NOT NULL DEFAULT 0 COMMENT 'Плитка специально для бассейна',

    PRIMARY KEY (id),
    KEY IDX_318C0B7C7294869C (article_id),
    KEY IDX_318C0B7C7FC358ED (orders_id),
    UNIQUE KEY uq_orders_article_order_article (orders_id, article_id),

    CONSTRAINT fk_orders_article_orders
        FOREIGN KEY (orders_id) REFERENCES orders(id)
            ON DELETE CASCADE
)
ENGINE=InnoDB
COMMENT 'Хранит информацию об артикулах заказа';
