import os
import re

path = r"C:\flutter\eltarekapp\lib\core\models\models.dart"

with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

# Add this.trims to constructor
target_constructor = """    required this.engineSummary,
    this.monthlyFromEgp,
    this.badge,
  });"""
replacement_constructor = """    required this.engineSummary,
    this.monthlyFromEgp,
    this.badge,
    this.trims = const [],
  });"""

if target_constructor in content:
    content = content.replace(target_constructor, replacement_constructor)

# Add trims mapping to fromJson
target_fromjson = """        monthlyFromEgp: json['monthly_from_egp'] == null
            ? null
            : _asInt(json['monthly_from_egp']),
        badge: json['badge']?.toString(),
      );"""
replacement_fromjson = """        monthlyFromEgp: json['monthly_from_egp'] == null
            ? null
            : _asInt(json['monthly_from_egp']),
        badge: json['badge']?.toString(),
        trims: (json['trims'] as List<dynamic>?)
                ?.map((x) => Trim.fromJson(x as Map<String, dynamic>))
                .toList() ??
            const [],
      );"""

if target_fromjson in content:
    content = content.replace(target_fromjson, replacement_fromjson)

# Add final List<Trim> trims;
target_fields = """  final String engineSummary;
  final int? monthlyFromEgp;
  final String? badge;
}"""
replacement_fields = """  final String engineSummary;
  final int? monthlyFromEgp;
  final String? badge;
  final List<Trim> trims;
}"""

if target_fields in content:
    content = content.replace(target_fields, replacement_fields)

with open(path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Patched models.dart successfully.")
