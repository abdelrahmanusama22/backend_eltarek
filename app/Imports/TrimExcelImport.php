<?php

namespace App\Imports;

use App\Models\Trim;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TrimExcelImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $id = $row['id'] ?? null;

            if ($id) {
                $trim = Trim::firstOrNew(['id' => $id]);
            } else {
                $trim = new Trim;
            }

            // Map standard text / numeric fields
            if (isset($row['trim_name']) || isset($row['name'])) {
                $trim->name = $row['trim_name'] ?? $row['name'];
            }
            if (isset($row['trim_name_ar']) || isset($row['name_ar'])) {
                $trim->name_ar = $row['trim_name_ar'] ?? $row['name_ar'];
            }
            if (isset($row['legacy_car_id'])) {
                $trim->legacy_car_id = $row['legacy_car_id'];
            }
            if (isset($row['official_price_egp']) || isset($row['price_egp'])) {
                $trim->price_egp = $row['official_price_egp'] ?? $row['price_egp'];
            }
            if (isset($row['markup']) || isset($row['markup_percentage'])) {
                $trim->markup_percentage = $row['markup'] ?? $row['markup_percentage'];
            }
            if (isset($row['total_price_egp']) || isset($row['total_price'])) {
                $trim->total_price = $row['total_price_egp'] ?? $row['total_price'];
            }
            if (isset($row['booking_deposit'])) {
                $trim->booking_deposit = $row['booking_deposit'];
            }
            if (isset($row['zero_interest_price'])) {
                $trim->zero_interest_price = $row['zero_interest_price'];
            }
            if (isset($row['install_price_9']) || isset($row['price_9pct'])) {
                $trim->price_9pct = $row['install_price_9'] ?? $row['price_9pct'];
            }
            if (isset($row['subtitle'])) {
                $trim->subtitle = $row['subtitle'];
            }
            if (isset($row['financing_notes'])) {
                $trim->financing_notes = $row['financing_notes'];
            }

            // Map booleans
            if (isset($row['hold_status']) || isset($row['is_on_hold'])) {
                $trim->is_on_hold = $this->parseBool($row['hold_status'] ?? $row['is_on_hold']);
            }
            if (isset($row['most_popular']) || isset($row['is_most_popular'])) {
                $trim->is_most_popular = $this->parseBool($row['most_popular'] ?? $row['is_most_popular']);
            }
            if (isset($row['active'])) {
                $trim->active = $this->parseBool($row['active']);
            }
            if (isset($row['has_360_view'])) {
                $trim->has_360_view = $this->parseBool($row['has_360_view']);
            }

            // Map JSON fields
            if (isset($row['colors'])) {
                $trim->colors = blank($row['colors']) ? null : json_decode($row['colors'], true);
            }
            if (isset($row['specifications']) || isset($row['specs'])) {
                $val = $row['specifications'] ?? $row['specs'];
                $trim->specs = blank($val) ? null : json_decode($val, true);
            }
            if (isset($row['highlights'])) {
                $trim->highlights = blank($row['highlights']) ? null : json_decode($row['highlights'], true);
            }
            if (isset($row['metrics'])) {
                $trim->metrics = blank($row['metrics']) ? null : json_decode($row['metrics'], true);
            }

            $trim->save();

            // Handle Relationship updates on Vehicle
            if ($trim->vehicle) {
                $vehicleUpdates = [];
                if (isset($row['year'])) {
                    $vehicleUpdates['year'] = $row['year'];
                }
                if (isset($row['category'])) {
                    $vehicleUpdates['category'] = $row['category'];
                }

                if (! empty($vehicleUpdates)) {
                    $trim->vehicle->update($vehicleUpdates);
                }
            }
        }
    }

    private function parseBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on']);
    }
}
