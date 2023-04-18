CREATE TABLE `spaces`
(
    `id`    int(11)      NOT NULL AUTO_INCREMENT,
    `name`  varchar(200) NOT NULL,
    `notes` text         NOT NULL DEFAULT '',
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

CREATE TABLE `mealtypes`
(
    `id`               int(11)      NOT NULL AUTO_INCREMENT,
    `name`             varchar(200) NOT NULL,
    `space`            int(11)      NOT NULL,
    `notificationTime` time DEFAULT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`space`) REFERENCES `spaces` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

CREATE TABLE `meals`
(
    `id`    int(11)      NOT NULL AUTO_INCREMENT,
    `date`  DATE         NOT NULL,
    `text`  varchar(200) NOT NULL,
    `url`   varchar(2048) DEFAULT NULL,
    `type`  int(11)      NOT NULL,
    `space` int(11)      NOT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`type`) REFERENCES `mealtypes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`space`) REFERENCES `spaces` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `space_date` (`space`, `date`),
    INDEX `space_text` (`space`, `text`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

CREATE TABLE `notifications`
(
    `id`        int(11)  NOT NULL AUTO_INCREMENT,
    `meal`      int(11)  NOT NULL,
    `time`      DATETIME NOT NULL,
    `text`      varchar(200)      DEFAULT NULL,
    `triggered` boolean  NOT NULL DEFAULT false,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`meal`) REFERENCES `meals` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX (`time`),
    INDEX (`triggered`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;