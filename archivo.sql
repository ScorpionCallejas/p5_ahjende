CREATE DATABASE IF NOT EXISTS db_test;
USE db_test;

CREATE TABLE archivo (
  `id_arc` int(11) NOT NULL,
  `fec_arc` datetime DEFAULT CURRENT_TIMESTAMP,
  `arc_arc` varchar(500) NOT NULL,
  `nom_arc` varchar(500) NOT NULL,
  `des_arc` text,
  `est_arc` varchar(50) DEFAULT NULL,
  `for_arc` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;