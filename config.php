<?php
return array(
	'dbs' => "[Database name]",
	'usr' => "[Database user]",
	'pwd' => "[Database password]",
	'webs_table' => "[Table name]",
	'web_params' => array(
		'id INT(11) NOT NULL AUTO_INCREMENT',
		'url VARCHAR(192) NOT NULL',
		'type TEXT',
		'branch TEXT',
		'https CHAR(0) DEFAULT NULL',
		'backup DATETIME',
		'added DATETIME',
		'last_updated DATETIME',
		'available CHAR(0) DEFAULT NULL',
		'repository TEXT',
		'urlhash VARCHAR(32)',
		'PRIMARY KEY (id)',
		'UNIQUE KEY id (id)',
        'UNIQUE KEY urlhash (urlhash)'
	)
);
