<?php
session_start();

if(!file_exists("config.php")) {
	die("config.php missing. Please use config.default.php as a template (if available).");
}
require_once("config.php");
require_once("include/globals.php");

require_once("include/data-acquisition.php");
require_once("include/data-processing.php");
require_once("include/data-presentation.php");