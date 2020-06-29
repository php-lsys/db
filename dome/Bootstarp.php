<?php
use LSYS\Config\File;
include_once __DIR__."/../vendor/autoload.php";
File::dirs(array(
	__DIR__."/config",
));
LSYS\Core::sets(array(
    'environment'=>LSYS\Core::DEVELOP
));
