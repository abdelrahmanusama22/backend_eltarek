import sys

sys.stdout.reconfigure(encoding='utf-8')

file1 = r"C:\flutter\eltarekapp\lib\core\services\google_auth_service.dart"
file2 = r"C:\flutter\eltarekapp\lib\core\api\backend_gateway.dart"
file3 = r"C:\flutter\eltarekapp\lib\features\auth\login_screen.dart"

for fpath in [file1, file2, file3]:
    print(f"=== {fpath} ===")
    with open(fpath, "r", encoding="utf-8") as f:
        print(f.read())
