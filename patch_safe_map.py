import os
import re

path = r"C:\flutter\eltarekapp\lib\core\models\models.dart"

with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

helper_target = "int _asInt(dynamic value, [int fallback = 0]) {"
helper_replacement = """Map<String, dynamic> _asMap(dynamic value) => value is Map ? Map<String, dynamic>.from(value) : const <String, dynamic>{};

int _asInt(dynamic value, [int fallback = 0]) {"""

content = content.replace(helper_target, helper_replacement)

target = """  factory TrimSpecs.fromJson(Map<String, dynamic> json) => TrimSpecs(
        tech: TechSpecs.fromJson(json['tech'] as Map<String, dynamic>? ?? {}),
        safety: SafetySpecs.fromJson(json['safety'] as Map<String, dynamic>? ?? {}),
        interior: InteriorSpecs.fromJson(json['interior'] as Map<String, dynamic>? ?? {}),
        exterior: ExteriorSpecs.fromJson(json['exterior'] as Map<String, dynamic>? ?? {}),
      );"""

replacement = """  factory TrimSpecs.fromJson(Map<String, dynamic> json) => TrimSpecs(
        tech: TechSpecs.fromJson(_asMap(json['tech'])),
        safety: SafetySpecs.fromJson(_asMap(json['safety'])),
        interior: InteriorSpecs.fromJson(_asMap(json['interior'])),
        exterior: ExteriorSpecs.fromJson(_asMap(json['exterior'])),
      );"""

content = content.replace(target, replacement)

# We should also patch Vehicle.fromJson's `trims` parsing
# `(json['trims'] as List<dynamic>?)?.map((x) => Trim.fromJson(x as Map<String, dynamic>))`
# If x is empty list `[]`, x as Map throws.
vehicle_trims_target = """        trims: (json['trims'] as List<dynamic>?)
                ?.map((x) => Trim.fromJson(x as Map<String, dynamic>))
                .toList() ??
            const [],"""
vehicle_trims_replacement = """        trims: (json['trims'] as List<dynamic>?)
                ?.where((x) => x is Map)
                ?.map((x) => Trim.fromJson(Map<String, dynamic>.from(x as Map)))
                .toList() ??
            const [],"""
content = content.replace(vehicle_trims_target, vehicle_trims_replacement)

with open(path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Patched models.dart for safe Map parsing.")
