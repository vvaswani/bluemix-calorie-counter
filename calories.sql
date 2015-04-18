
-- --------------------------------------------------------

--
-- Table structure for table 'meals'
--

DROP TABLE IF EXISTS meals;
CREATE TABLE meals (
  id int(11) NOT NULL AUTO_INCREMENT, 
  uid varchar(255) NOT NULL, 
  calories decimal(10,2) NOT NULL, 
  rdate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, 
  ip varchar(20) NOT NULL, 
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8

--
-- Table structure for table 'users'
--

DROP TABLE IF EXISTS users;
CREATE TABLE users (
  id int(11) NOT NULL AUTO_INCREMENT, 
  email varchar(255) NOT NULL, 
  `password` varchar(255) NOT NULL, 
  code varchar(255) DEFAULT NULL, 
  `status` int(11) NOT NULL, 
  rdate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, 
  ip varchar(20) NOT NULL, 
  PRIMARY KEY (id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

