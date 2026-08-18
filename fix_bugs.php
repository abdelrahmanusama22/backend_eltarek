<?php
$auth_path = "app/Http/Controllers/Api/AuthController.php";
$auth_content = file_get_contents($auth_path);
$auth_content = str_replace(
    "\$code = \"1234\";",
    "\$code = str_pad((string) random_int(0, (10 ** \$length) - 1), \$length, '0', STR_PAD_LEFT);",
    $auth_content
);
file_put_contents($auth_path, $auth_content);

$td_path = "app/Http/Controllers/Api/TestDriveController.php";
$td_content = file_get_contents($td_path);
$td_content = str_replace(
    "public function destroy(Request \$request, Booking \$booking): JsonResponse",
    "public function destroy(Request \$request, \$bookingId): JsonResponse",
    $td_content
);
$td_content = str_replace(
    "if (\$booking->user_id !== \$request->user()->id) {\n            return \$this->fail('Booking not found or already cancelled.', 404);\n        }",
    "\$booking = \$request->user()->bookings()->findOrFail(\$bookingId);",
    $td_content
);
file_put_contents($td_path, $td_content);

$env_path = ".env";
$env_content = file_get_contents($env_path);
$env_content = str_replace("APP_DEBUG=true", "APP_DEBUG=false", $env_content);
if (strpos($env_content, "DB_PASSWORD=\n") !== false) {
    $env_content = str_replace("DB_PASSWORD=\n", "DB_PASSWORD=secret\n", $env_content);
} elseif (strpos($env_content, "DB_PASSWORD=\r\n") !== false) {
    $env_content = str_replace("DB_PASSWORD=\r\n", "DB_PASSWORD=secret\r\n", $env_content);
}
file_put_contents($env_path, $env_content);

echo "Bugs fixed successfully!\n";
?>
