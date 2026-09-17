import os

base_dir = r"c:\flutter\eltarekapp\lib\features"

# 1. Update showroom_screen.dart
showroom_path = os.path.join(base_dir, "showroom", "showroom_screen.dart")
with open(showroom_path, "r", encoding="utf-8") as f:
    showroom = f.read()

# I will replace the itemBuilder inside GridView.builder
new_brand_card = """
                        itemBuilder: (context, index) {
                          final brand = brands[index];
                          final fallbackInitial = (brand.name.isNotEmpty) ? brand.name[0].toUpperCase() : '?';
                          
                          return Container(
                            decoration: BoxDecoration(
                              color: Colors.white,
                              borderRadius: BorderRadius.circular(16),
                              boxShadow: [
                                BoxShadow(
                                  color: Colors.black.withValues(alpha: 0.05),
                                  blurRadius: 10,
                                  offset: const Offset(0, 4),
                                ),
                              ],
                            ),
                            child: Material(
                              color: Colors.transparent,
                              child: InkWell(
                                borderRadius: BorderRadius.circular(16),
                                onTap: () => Navigator.of(context).push(MaterialPageRoute(
                                  builder: (_) => ModelsScreen(brandId: brand.id),
                                )),
                                child: Padding(
                                  padding: const EdgeInsets.all(16.0),
                                  child: Column(
                                    mainAxisAlignment: MainAxisAlignment.center,
                                    children: [
                                      Expanded(
                                        child: (brand.logoUrl == null || brand.logoUrl!.isEmpty)
                                            ? Container(
                                                decoration: BoxDecoration(
                                                  shape: BoxShape.circle,
                                                  color: AppColors.mist.withValues(alpha: 0.5),
                                                ),
                                                alignment: Alignment.center,
                                                child: Text(
                                                  fallbackInitial,
                                                  style: const TextStyle(
                                                    fontSize: 32,
                                                    fontWeight: FontWeight.bold,
                                                    color: AppColors.slate,
                                                  ),
                                                ),
                                              )
                                            : NetImage(
                                                url: brand.logoUrl,
                                                fit: BoxFit.contain,
                                              ),
                                      ),
                                      const SizedBox(height: 12),
                                      Text(
                                        state.isArabic ? brand.nameAr : brand.name,
                                        textAlign: TextAlign.center,
                                        style: Theme.of(context).textTheme.titleSmall?.copyWith(
                                          fontWeight: FontWeight.w600,
                                          color: AppColors.ink,
                                        ),
                                        maxLines: 1,
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                    ],
                                  ),
                                ),
                              ),
                            ),
                          );
                        },
"""
import re
showroom = re.sub(r"itemBuilder:\s*\(context,\s*index\)\s*\{[\s\S]*?\},", new_brand_card.strip() + ",", showroom)

with open(showroom_path, "w", encoding="utf-8") as f:
    f.write(showroom)

# 2. Update models_screen.dart
models_path = os.path.join(base_dir, "showroom", "models_screen.dart")
with open(models_path, "r", encoding="utf-8") as f:
    models = f.read()

# Add needed imports if missing (like formatPrice)
if "formatPrice" not in models:
    models = models.replace("import '../../core/widgets/ui.dart';", "import '../../core/widgets/ui.dart';\nimport '../../core/utils/formatters.dart';\nimport '../../core/theme/app_colors.dart';")

new_model_card = """
              itemBuilder: (context, index) {
                final vehicle = models[index];
                final brandName = state.isArabic ? brand.nameAr : brand.name;
                final modelName = state.isArabic ? vehicle.modelAr : vehicle.model;
                
                return Container(
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(20),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withValues(alpha: 0.06),
                        blurRadius: 15,
                        offset: const Offset(0, 5),
                      ),
                    ],
                  ),
                  clipBehavior: Clip.antiAlias,
                  child: Material(
                    color: Colors.transparent,
                    child: InkWell(
                      onTap: () => Navigator.of(context).push(MaterialPageRoute(
                        builder: (_) => TrimsScreen(vehicleId: vehicle.id),
                      )),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          // Banner Image with Badge
                          Stack(
                            children: [
                              NetImage(
                                url: vehicle.imageUrl,
                                aspectRatio: 16 / 9,
                              ),
                              Positioned(
                                top: 12,
                                left: 12,
                                child: Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                  decoration: BoxDecoration(
                                    color: Colors.black.withValues(alpha: 0.6),
                                    borderRadius: BorderRadius.circular(8),
                                  ),
                                  child: Text(
                                    vehicle.category.toUpperCase(),
                                    style: const TextStyle(
                                      color: Colors.white,
                                      fontSize: 10,
                                      fontWeight: FontWeight.w600,
                                      letterSpacing: 0.5,
                                    ),
                                  ),
                                ),
                              ),
                            ],
                          ),
                          
                          // Content Section
                          Padding(
                            padding: const EdgeInsets.all(16.0),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                // Title
                                Text(
                                  '$brandName $modelName ${vehicle.year}',
                                  style: Theme.of(context).textTheme.titleLarge?.copyWith(
                                    fontWeight: FontWeight.bold,
                                    color: AppColors.ink,
                                  ),
                                ),
                                const SizedBox(height: 4),
                                
                                // Subtitle / Engine Summary
                                Text(
                                  '$brandName • ${vehicle.engineSummary.isNotEmpty ? vehicle.engineSummary : 'Standard'}',
                                  style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                                    color: AppColors.slate,
                                  ),
                                ),
                                const SizedBox(height: 16),
                                
                                // Price and Action
                                Row(
                                  crossAxisAlignment: CrossAxisAlignment.end,
                                  children: [
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Text(
                                            'STARTING FROM',
                                            style: TextStyle(
                                              fontSize: 10,
                                              fontWeight: FontWeight.bold,
                                              color: AppColors.slate,
                                              letterSpacing: 0.5,
                                            ),
                                          ),
                                          const SizedBox(height: 2),
                                          Text(
                                            '${formatPrice(vehicle.startingPriceEgp)} EGP',
                                            style: Theme.of(context).textTheme.titleMedium?.copyWith(
                                              fontWeight: FontWeight.w900,
                                              color: AppColors.red,
                                            ),
                                          ),
                                        ],
                                      ),
                                    ),
                                    
                                    // Actions
                                    Container(
                                      padding: const EdgeInsets.all(8),
                                      decoration: BoxDecoration(
                                        shape: BoxShape.circle,
                                        color: AppColors.mist.withValues(alpha: 0.4),
                                      ),
                                      child: const Icon(Icons.compare_arrows_rounded, size: 20, color: AppColors.ink),
                                    ),
                                    const SizedBox(width: 8),
                                    Container(
                                      padding: const EdgeInsets.all(8),
                                      decoration: const BoxDecoration(
                                        shape: BoxShape.circle,
                                        color: AppColors.ink,
                                      ),
                                      child: const Icon(Icons.arrow_forward_ios_rounded, size: 16, color: Colors.white),
                                    ),
                                  ],
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                );
              },
"""
models = re.sub(r"itemBuilder:\s*\(context,\s*index\)\s*\{[\s\S]*?\},", new_model_card.strip() + ",", models)

with open(models_path, "w", encoding="utf-8") as f:
    f.write(models)

print("UI Refactored.")
