<?php
$env_path = ".env";
$env_content = file_get_contents($env_path);
$env_content = str_replace("DB_PASSWORD=secret", "DB_PASSWORD=", $env_content);
file_put_contents($env_path, $env_content);
?>
