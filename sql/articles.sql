-- ---------------------------------------------------------------------------
-- Demo schema for Simple Priority Management.
--
-- The technique needs exactly one thing from your table: an integer
-- `priority` column that is NOT NULL and carries a UNIQUE index. Everything
-- else in this file is demo dressing.
--
-- Replace `articles` / `title` with your own table and display column.
-- ---------------------------------------------------------------------------

DROP TABLE IF EXISTS `articles`;

CREATE TABLE `articles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `priority` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed data: priorities must be unique, and starting at 1 with no gaps keeps
-- the demo consistent with what the application maintains at runtime.
INSERT INTO `articles` (`title`, `priority`) VALUES
('Normalising a third-party API', 1),
('Writing the deployment checklist', 2),
('Reviewing pull request 412', 3),
('Drafting the Q4 support rota', 4),
('Auditing the image upload handler', 5),
('Archiving the old staging database', 6);
