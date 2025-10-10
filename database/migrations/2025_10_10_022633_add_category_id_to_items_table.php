<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Add category_id column if it doesn't exist
        if (!Schema::hasColumn('items', 'category_id')) {
            Schema::table('items', function (Blueprint $table) {
                $table->foreignId('category_id')->nullable()->after('name')->constrained()->nullOnDelete();
            });
        }

        // Step 2: Migrate existing category data
        $this->migrateCategories();

        // Step 3: Drop any indexes on category column before dropping it
        if (Schema::hasColumn('items', 'category')) {
            Schema::table('items', function (Blueprint $table) {
                // Drop index if it exists (for SQLite compatibility)
                DB::statement('DROP INDEX IF EXISTS items_category_index');
            });

            // Step 4: Remove old category column
            Schema::table('items', function (Blueprint $table) {
                $table->dropColumn('category');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore category column
        Schema::table('items', function (Blueprint $table) {
            $table->string('category')->nullable()->after('name');
        });

        // Restore category data from relations
        $items = DB::table('items')->whereNotNull('category_id')->get();
        foreach ($items as $item) {
            $category = DB::table('categories')->find($item->category_id);
            if ($category) {
                DB::table('items')->where('id', $item->id)->update(['category' => $category->name]);
            }
        }

        // Drop foreign key and column
        Schema::table('items', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });
    }

    /**
     * Migrate existing categories from text to table
     */
    private function migrateCategories(): void
    {
        // Get unique categories from items
        $categories = DB::table('items')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->select('category')
            ->distinct()
            ->get();

        $categoryMap = [];
        $sortOrder = 0;

        foreach ($categories as $categoryRow) {
            $categoryName = trim($categoryRow->category);

            if (empty($categoryName)) {
                continue;
            }

            // Create category if it doesn't exist
            $existingCategory = DB::table('categories')
                ->where('name', $categoryName)
                ->first();

            if (!$existingCategory) {
                $categoryId = DB::table('categories')->insertGetId([
                    'name' => $categoryName,
                    'slug' => Str::slug($categoryName),
                    'is_active' => true,
                    'sort_order' => $sortOrder++,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $categoryId = $existingCategory->id;
            }

            $categoryMap[$categoryName] = $categoryId;
        }

        // Update items with category_id
        foreach ($categoryMap as $categoryName => $categoryId) {
            DB::table('items')
                ->where('category', $categoryName)
                ->update(['category_id' => $categoryId]);
        }
    }
};
