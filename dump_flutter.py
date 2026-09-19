import sys

file1 = r"C:\flutter\eltarekapp\lib\core\services\google_auth_service.dart"
file2 = r"C:\flutter\eltarekapp\lib\core\api\backend_gateway.dart"
file3 = r"C:\flutter\eltarekapp\lib\features\auth\login_screen.dart"

with open("flutter_code_utf8.txt", "w", encoding="utf-8") as out:
    for fpath in [file1, file2, file3]:
        out.write(f"=== {fpath} ===\n")
        with open(fpath, "r", encoding="utf-8") as f:
            out.write(f.read())
            out.write("\n")
