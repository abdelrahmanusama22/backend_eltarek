import os

path = r"C:\flutter\eltarekapp\lib\core\models\models.dart"

with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

target = "int _asInt(dynamic v, [int fallback = 0]) =>"
replacement = """Map<String, dynamic> _asMap(dynamic value) => value is Map ? Map<String, dynamic>.from(value) : const <String, dynamic>{};

int _asInt(dynamic v, [int fallback = 0]) =>"""

if target in content:
    content = content.replace(target, replacement)
    with open(path, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Patched _asMap successfully.")
else:
    print("Target not found.")

