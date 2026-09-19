import os
import sys

sys.stdout.reconfigure(encoding='utf-8')

files = [
    r"C:\flutter\eltarekapp\lib\core\api\backend_gateway.dart",
    r"C:\flutter\eltarekapp\lib\core\services\google_auth_service.dart",
    r"C:\flutter\eltarekapp\lib\features\auth\login_screen.dart"
]

for file in files:
    print(f"=== {file} ===")
    try:
        with open(file, "r", encoding="utf-8") as f:
            print(f.read())
    except Exception as e:
        print(e)
    print("\n")
