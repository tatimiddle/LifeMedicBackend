<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            if (! Schema::hasColumn('categories', 'slug')) {
                $table->string('slug', 120)->nullable();
                $table->unique('slug');
            }
        });

        if (Schema::hasColumn('categories', 'slug')) {
            DB::table('categories')->whereNull('slug')->get(['id', 'name'])->each(function (object $category): void {
                $base = Str::slug((string) $category->name) ?: ('category-'.$category->id);
                $slug = DB::table('categories')->where('slug', $base)->exists()
                    ? $base.'-'.$category->id
                    : $base;

                DB::table('categories')->where('id', $category->id)->update(['slug' => $slug]);
            });
        }
    }

    public function down(): void
    {
        $hasSlugUnique = Schema::hasIndex('categories', ['slug'], 'unique');

        Schema::table('categories', function (Blueprint $table) use ($hasSlugUnique): void {
            if (Schema::hasColumn('categories', 'slug')) {
                if ($hasSlugUnique) {
                    $table->dropUnique(['slug']);
                }
                $table->dropColumn('slug');
            }
        });
    }
};
