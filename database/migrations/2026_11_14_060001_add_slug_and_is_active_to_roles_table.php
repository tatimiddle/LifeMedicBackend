<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            if (! Schema::hasColumn('roles', 'slug')) {
                $table->string('slug', 60)->nullable();
                $table->unique('slug');
            }

            if (! Schema::hasColumn('roles', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
        });

        if (Schema::hasColumn('roles', 'slug')) {
            DB::table('roles')->whereNull('slug')->get(['id', 'name'])->each(function (object $role): void {
                $candidate = Role::resolveKnownSlug((string) $role->name) ?? Str::slug((string) $role->name);
                if ($candidate === '' || DB::table('roles')->where('slug', $candidate)->exists()) {
                    $candidate = ($candidate !== '' ? $candidate : 'role').'-'.$role->id;
                }

                DB::table('roles')->where('id', $role->id)->update(['slug' => $candidate]);
            });
        }
    }

    public function down(): void
    {
        $hasSlugUnique = Schema::hasIndex('roles', ['slug'], 'unique');

        Schema::table('roles', function (Blueprint $table) use ($hasSlugUnique): void {
            if (Schema::hasColumn('roles', 'slug')) {
                if ($hasSlugUnique) {
                    $table->dropUnique(['slug']);
                }
                $table->dropColumn('slug');
            }

            if (Schema::hasColumn('roles', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
