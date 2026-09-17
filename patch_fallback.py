import os

path = r"C:\flutter\eltarekapp\lib\core\api\backend_gateway.dart"

with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

target = """      try {
        MockApi.hydrate(result.data!);
        debugPrint('o. Catalog hydrated from ${Api.baseUrl}');
      } catch (e) {
        debugPrint('s,? Bootstrap payload rejected, staying on mock: $e');
      }
    } else {
      debugPrint(',1,? Backend unreachable, using bundled catalog');
    }"""
replacement = """      try {
        MockApi.hydrate(result.data!);
        debugPrint('o. Catalog hydrated from ${Api.baseUrl}');
      } catch (e, st) {
        debugPrint('s,? Bootstrap payload rejected: $e\\n$st');
        throw Exception('Bootstrap JSON parsing failed: $e');
      }
    } else {
      debugPrint(',1,? Backend unreachable, result.ok is false');
      throw Exception('Backend unreachable or returned error: ${result.message}');
    }"""

if target in content:
    content = content.replace(target, replacement)
    with open(path, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Patched backend_gateway.dart successfully.")
else:
    print("Target string not found in backend_gateway.dart")

# Now let's empty the mock arrays in mock_data.dart
path_mock = r"C:\flutter\eltarekapp\lib\core\data\mock_data.dart"
with open(path_mock, 'r', encoding='utf-8') as f:
    mock_content = f.read()

import re

# We will empty the lists.
mock_content = re.sub(r'static List<Brand> brands = \[.*?\];', 'static List<Brand> brands = [];', mock_content, flags=re.DOTALL)
mock_content = re.sub(r'static List<Vehicle> vehicles = \[.*?\];', 'static List<Vehicle> vehicles = [];', mock_content, flags=re.DOTALL)
mock_content = re.sub(r'static List<Trim> trims = \[.*?\];', 'static List<Trim> trims = [];', mock_content, flags=re.DOTALL)

with open(path_mock, 'w', encoding='utf-8') as f:
    f.write(mock_content)
print("Patched mock_data.dart arrays to be empty.")

