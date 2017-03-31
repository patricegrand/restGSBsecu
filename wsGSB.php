<?php

// Traite la demande

require_once("rest/restgsbrapports.php");

$api = new RestGSB();

$api->process();