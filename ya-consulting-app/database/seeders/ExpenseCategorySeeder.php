<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ExpenseCategory;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            // Main d'œuvre
            ['name' => "Main d'œuvre directe",   'parent_type' => 'main_oeuvre', 'color' => '#6366F1'],
            ['name' => "Sous-traitance",           'parent_type' => 'main_oeuvre', 'color' => '#8B5CF6'],

            // Matériel
            ['name' => 'Achat matériel',           'parent_type' => 'materiel',    'color' => '#F59E0B'],
            ['name' => 'Location équipement',      'parent_type' => 'materiel',    'color' => '#D97706'],

            // Transport
            ['name' => 'Déplacement / Transport',  'parent_type' => 'transport',   'color' => '#10B981'],
            ['name' => 'Carburant',                 'parent_type' => 'transport',   'color' => '#059669'],

            // Autres
            ['name' => 'Communication',             'parent_type' => 'autres',      'color' => '#3B82F6'],
            ['name' => 'Impôts / Taxes',            'parent_type' => 'autres',      'color' => '#EF4444'],
            ['name' => 'Frais administratifs',      'parent_type' => 'autres',      'color' => '#EC4899'],
            ['name' => 'Divers',                    'parent_type' => 'autres',      'color' => '#6B7280'],
        ];

        foreach ($categories as $cat) {
            ExpenseCategory::firstOrCreate(
                ['name' => $cat['name']],
                array_merge($cat, ['is_custom' => false])
            );
        }

        $this->command->info('✅ Catégories de dépenses créées (' . count($categories) . ')');
    }
}
