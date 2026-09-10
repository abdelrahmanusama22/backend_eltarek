import re

filepath = r"c:\flutter\eltarekapp\lib\core\models\models.dart"

with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

# Replace json['key'] ?? 'value' with json['key']?.toString() ?? 'value'
# Handle cases like json['name'] ?? json['other'] ?? ''
# First, replace `json['key'] ??` with `json['key']?.toString() ??`
# But only for strings. Wait, if it's ?? '', it's definitely a string.

content = re.sub(r"json\['([^']+)'\] \?\? ''", r"json['\1']?.toString() ?? ''", content)
content = re.sub(r"json\['([^']+)'\] \?\? json\['([^']+)'\] \?\? ''", r"json['\1']?.toString() ?? json['\2']?.toString() ?? ''", content)
content = re.sub(r"json\['([^']+)'\] \?\? '([^']+)'", r"json['\1']?.toString() ?? '\2'", content)

# logo_url
content = content.replace("json['logo_url'] as String?", "json['logo_url']?.toString()")

# badge
content = content.replace("badge: json['badge'],", "badge: json['badge']?.toString(),")

# List strings
content = content.replace("[for (final t in (json['times'] as List?) ?? const []) '$t'],", "[for (final t in (json['times'] as List?) ?? const []) t?.toString() ?? ''],")

# In GarageCar
content = content.replace("(json['vehicle'] as Map)['name'] ?? ''", "(json['vehicle'] as Map)['name']?.toString() ?? ''")
content = content.replace("(json['name'] ?? '')", "(json['name']?.toString() ?? '')")
content = content.replace("(json['vehicle'] as Map)['image_url'] ?? ''", "(json['vehicle'] as Map)['image_url']?.toString() ?? ''")
content = content.replace("(json['image_url'] ?? '')", "(json['image_url']?.toString() ?? '')")

# In Booking
content = content.replace("trim['name'] ?? ''", "trim['name']?.toString() ?? ''")
content = content.replace("trim['name_ar'] ?? ''", "trim['name_ar']?.toString() ?? ''")
content = content.replace("trim['image_url'] ?? ''", "trim['image_url']?.toString() ?? ''")
content = content.replace("branch['name'] ?? ''", "branch['name']?.toString() ?? ''")
content = content.replace("branch['name_ar'] ?? ''", "branch['name_ar']?.toString() ?? ''")

# Booleans
# json['has_360_view'] == true -> json['has_360_view'] == true or json['has_360_view'] == 'true' (already handles null)
# Wait, `json['has_360_view'] == true` is already null safe (null == true is false). The user said "Do the same for numbers, booleans, and lists". 
# But `== true` doesn't crash on null. Still, I will leave it since it's safe.

with open(filepath, 'w', encoding='utf-8') as f:
    f.write(content)

print("Models patched.")
