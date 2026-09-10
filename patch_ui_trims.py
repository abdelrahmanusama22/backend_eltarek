import os

files_to_patch = [
    r"C:\flutter\eltarekapp\lib\features\brands\brand_models_screen.dart",
    r"C:\flutter\eltarekapp\lib\features\home\home_screen.dart",
]

for path in files_to_patch:
    with open(path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    target = "MockApi.trimsForVehicle(vehicle.id)"
    replacement = "vehicle.trims"
    
    if target in content:
        content = content.replace(target, replacement)
        with open(path, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"Patched {os.path.basename(path)}")
    else:
        print(f"Target not found in {os.path.basename(path)}")

path_trims_screen = r"C:\flutter\eltarekapp\lib\features\trims\trims_screen.dart"
with open(path_trims_screen, 'r', encoding='utf-8') as f:
    content = f.read()

target = "MockApi.trimsForVehicle(vehicleId)"
replacement = "vehicle.trims"
if target in content:
    content = content.replace(target, replacement)
    with open(path_trims_screen, 'w', encoding='utf-8') as f:
        f.write(content)
    print(f"Patched trims_screen.dart")
else:
    print("Target not found in trims_screen.dart")

