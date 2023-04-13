CREATE TABLE `mealtypes`
(
    `id`   int(11)      NOT NULL AUTO_INCREMENT,
    `name` varchar(200) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

CREATE TABLE `spaces`
(
    `id`   int(11)      NOT NULL AUTO_INCREMENT,
    `name` varchar(200) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

CREATE TABLE `meals`
(
    `id`                  int(11)      NOT NULL AUTO_INCREMENT,
    `date`                DATE         NOT NULL,
    `text`                varchar(200) NOT NULL,
    `url`                 varchar(2048)         DEFAULT NULL,
    `notificationEnabled` boolean      NOT NULL DEFAULT false,
    `notificationTime`    varchar(5)            DEFAULT NULL,
    `type`                int(11)      NOT NULL,
    `space`               int(11)      NOT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`type`) REFERENCES `mealtypes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`space`) REFERENCES `spaces` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `space_date` (`space`, `date`),
    INDEX `space_text` (`space`, `text`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;