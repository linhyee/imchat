create table msg (
	`id` integer primary key not null auto_increment,
	`from` varchar(255),
	`to` varchar(255),
	`msg` text,
	`image` blob,
	`create_time` int
);