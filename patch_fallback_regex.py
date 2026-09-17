import os
import re

path = r"C:\flutter\eltarekapp\lib\core\api\backend_gateway.dart"

with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

# Replace the bootstrap method body
pattern = r"static Future<void> bootstrap\(\) async \{.*?\n  \}"
replacement = """static Future<void> bootstrap() async {
    final result = await Api.get('/bootstrap');
    if (result.ok && result.data != null) {
      try {
        MockApi.hydrate(result.data!);
        debugPrint('Catalog hydrated from ${Api.baseUrl}');
      } catch (e, st) {
        debugPrint('Bootstrap payload rejected: $e\\n$st');
        throw Exception('Bootstrap JSON parsing failed: $e');
      }
    } else {
      debugPrint('Backend unreachable, result.ok is false');
      throw Exception('Backend unreachable or returned error: ${result.message}');
    }
  }"""

if re.search(pattern, content, flags=re.DOTALL):
    content = re.sub(pattern, replacement, content, flags=re.DOTALL)
    with open(path, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Patched backend_gateway.dart successfully.")
else:
    print("Regex not found in backend_gateway.dart")

