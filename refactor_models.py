import os

base_dir = r"c:\flutter\eltarekapp\lib\features"
models_path = os.path.join(base_dir, "showroom", "models_screen.dart")

models_content = """import 'package:flutter/material.dart';

import '../../core/data/mock_data.dart';
import '../../core/state/app_state.dart';
import '../../core/widgets/ui.dart';
import '../../core/utils/formatters.dart';
import '../../core/theme/app_colors.dart';
import '../trims/trims_screen.dart';

class ModelsScreen extends StatelessWidget {
  const ModelsScreen({super.key, required this.brandId});
  final int brandId;

  @override
  Widget build(BuildContext context) {
    final state = AppScope.of(context);
    final brand = MockApi.brands.firstWhere(
      (b) => b.id == brandId,
      orElse: () => MockApi.brands.first,
    );
    final models = MockApi.brandModels(brandId);

    return Scaffold(
      appBar: AppBar(
        leading: const DirectionalBackButton(),
        title: Text(state.isArabic ? brand.nameAr : brand.name),
      ),
      body: models.isEmpty
          ? const Center(child: Text('No models available'))
          : ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: models.length,
              separatorBuilder: (_, __) => const SizedBox(height: 16),
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
                        color: Colors.black.withOpacity(0.06),
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
                                    color: Colors.black.withOpacity(0.6),
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
                                          const Text(
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
                                            '${formatNumber(vehicle.startingPriceEgp)} EGP',
                                            style: Theme.of(context).textTheme.titleMedium?.copyWith(
                                              fontWeight: FontWeight.w900,
                                              color: AppColors.red,
                                            ),
                                          ),
                                        ],
                                      ),
                                    ),

                                    // Actions
                                    TextButton.icon(
                                      onPressed: () {},
                                      icon: const Icon(Icons.compare_arrows_rounded, size: 16),
                                      label: const Text('Compare', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                                      style: TextButton.styleFrom(
                                        foregroundColor: AppColors.ink,
                                        backgroundColor: AppColors.mist.withOpacity(0.3),
                                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                                      ),
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
            ),
    );
  }
}
"""

with open(models_path, "w", encoding="utf-8") as f:
    f.write(models_content)

print("models_screen updated")
