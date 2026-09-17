import os

path = r"C:\flutter\eltarekapp\lib\core\api\backend_gateway.dart"

with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

target = "debugPrint('Bootstrap payload rejected: $e\n$st');"
replacement = "debugPrint('Bootstrap payload rejected: $e\\n$st');"

content = content.replace(target, replacement)

with open(path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Fixed newline issue in backend_gateway.dart.")
