import os
import re

models_path = r"c:\flutter\eltarekapp\lib\core\models\models.dart"
showroom_path = r"c:\flutter\eltarekapp\lib\features\showroom\showroom_screen.dart"

# Fix models.dart
with open(models_path, "r", encoding="utf-8") as f:
    content = f.read()

content = content.replace("name: json['name']?.toString() ?? '',", "name: json['name']?.toString() ?? 'Unknown Brand',")
content = content.replace("logoUrl: json['logo_url']?.toString(),", "logoUrl: json['logo_url']?.toString() ?? '',")

with open(models_path, "w", encoding="utf-8") as f:
    f.write(content)

# Fix showroom_screen.dart
with open(showroom_path, "r", encoding="utf-8") as f:
    showroom = f.read()

replacement = """(brand.logoUrl == null || brand.logoUrl!.isEmpty)
                                      ? const Center(child: Icon(Icons.directions_car, size: 48, color: AppColors.silver))
                                      : NetImage(
                                          url: brand.logoUrl,
                                          fit: BoxFit.contain,
                                        )"""

# In showroom_screen.dart, replace the NetImage block
showroom = re.sub(
    r"NetImage\(\s*url:\s*brand\.logoUrl,\s*fit:\s*BoxFit\.contain,\s*\)",
    replacement,
    showroom
)

with open(showroom_path, "w", encoding="utf-8") as f:
    f.write(showroom)

print("Fixes applied.")
