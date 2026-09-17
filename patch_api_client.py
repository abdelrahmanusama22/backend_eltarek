import os

path = r"C:\flutter\eltarekapp\lib\core\api\api_client.dart"

with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

target = """      final Map<String, dynamic> json = response.body.isEmpty
          ? const {}
          : Map<String, dynamic>.from(jsonDecode(response.body) as Map);"""

replacement = """      final dynamic decoded = response.body.isEmpty ? const {} : jsonDecode(response.body);
      final Map<String, dynamic> json;
      if (decoded is List) {
        json = {'data': decoded};
      } else if (decoded is Map) {
        json = Map<String, dynamic>.from(decoded);
      } else {
        json = {'data': decoded};
      }"""

if target in content:
    content = content.replace(target, replacement)
    with open(path, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Patched api_client.dart successfully.")
else:
    print("Target string not found in api_client.dart.")
