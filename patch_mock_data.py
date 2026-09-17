import os
import re

path = r"C:\flutter\eltarekapp\lib\core\data\mock_data.dart"

with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

target = """      vehicles = parse('vehicles', Vehicle.fromJson) ?? vehicles;
      branches = parse('branches', Branch.fromJson) ?? branches;"""
replacement = """      vehicles = parse('vehicles', Vehicle.fromJson) ?? vehicles;
      trims = parse('trims', Trim.fromJson) ?? trims;
      branches = parse('branches', Branch.fromJson) ?? branches;"""

if target in content:
    content = content.replace(target, replacement)
    with open(path, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Patched mock_data.dart successfully.")
else:
    print("Target string not found in mock_data.dart.")
