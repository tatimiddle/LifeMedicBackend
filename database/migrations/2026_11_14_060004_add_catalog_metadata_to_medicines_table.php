<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicines', function (Blueprint $table): void {
            if (! Schema::hasColumn('medicines', 'catalog_type')) {
                $table->string('catalog_type', 30)->default('medicine')->index();
            }
            if (! Schema::hasColumn('medicines', 'source_name')) {
                $table->string('source_name', 255)->nullable();
            }
            if (! Schema::hasColumn('medicines', 'active_ingredient')) {
                $table->string('active_ingredient', 200)->nullable();
            }
            if (! Schema::hasColumn('medicines', 'formula')) {
                $table->json('formula')->nullable();
            }
            if (! Schema::hasColumn('medicines', 'formula_summary')) {
                $table->string('formula_summary', 255)->nullable();
            }
            if (! Schema::hasColumn('medicines', 'san_nicolas_category')) {
                $table->string('san_nicolas_category', 150)->nullable();
            }
            if (! Schema::hasColumn('medicines', 'laboratory')) {
                $table->string('laboratory', 150)->nullable();
            }
            if (! Schema::hasColumn('medicines', 'indications')) {
                $table->text('indications')->nullable();
            }
            if (! Schema::hasColumn('medicines', 'contraindications')) {
                $table->text('contraindications')->nullable();
            }
            if (! Schema::hasColumn('medicines', 'batch')) {
                $table->string('batch', 80)->nullable();
            }
            if (! Schema::hasColumn('medicines', 'expiry')) {
                $table->string('expiry', 60)->nullable();
            }
            if (! Schema::hasColumn('medicines', 'expiry_status')) {
                $table->string('expiry_status', 60)->default('Vigente');
            }
            if (! Schema::hasColumn('medicines', 'prescription')) {
                $table->string('prescription', 60)->default('Venta Libre');
            }
            if (! Schema::hasColumn('medicines', 'prescription_required')) {
                $table->boolean('prescription_required')->default(false);
            }
            if (! Schema::hasColumn('medicines', 'previous_price')) {
                $table->decimal('previous_price', 10, 2)->nullable();
            }
            if (! Schema::hasColumn('medicines', 'min_stock')) {
                $table->unsignedInteger('min_stock')->default(20);
            }
            if (! Schema::hasColumn('medicines', 'image_path')) {
                $table->string('image_path', 255)->nullable();
            }
            if (! Schema::hasColumn('medicines', 'source_url')) {
                $table->string('source_url', 500)->nullable();
            }
        });

        if (! Schema::hasIndex('medicines', ['category_id', 'is_active'])) {
            Schema::table('medicines', function (Blueprint $table): void {
                $table->index(['category_id', 'is_active']);
            });
        }
    }

    public function down(): void
    {
        $hasCategoryActiveIdx = Schema::hasIndex('medicines', ['category_id', 'is_active']);
        $hasCatalogTypeIdx = Schema::hasIndex('medicines', ['catalog_type']);

        Schema::table('medicines', function (Blueprint $table) use ($hasCategoryActiveIdx, $hasCatalogTypeIdx): void {
            if ($hasCategoryActiveIdx) {
                $table->dropIndex(['category_id', 'is_active']);
            }
            if ($hasCatalogTypeIdx) {
                $table->dropIndex(['catalog_type']);
            }

            $columns = [
                'catalog_type',
                'source_name',
                'active_ingredient',
                'formula',
                'formula_summary',
                'san_nicolas_category',
                'laboratory',
                'indications',
                'contraindications',
                'batch',
                'expiry',
                'expiry_status',
                'prescription',
                'prescription_required',
                'previous_price',
                'min_stock',
                'image_path',
                'source_url',
            ];

            $existing = array_values(array_filter($columns, fn (string $col): bool => Schema::hasColumn('medicines', $col)));
            if ($existing !== []) {
                $table->dropColumn($existing);
            }
        });
    }
};
