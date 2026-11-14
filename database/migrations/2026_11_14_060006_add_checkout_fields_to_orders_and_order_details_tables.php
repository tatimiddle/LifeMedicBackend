<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('orders', 'customer_name')) {
                $table->string('customer_name', 150)->nullable();
            }
            if (! Schema::hasColumn('orders', 'customer_email')) {
                $table->string('customer_email', 150)->nullable();
            }
            if (! Schema::hasColumn('orders', 'phone')) {
                $table->string('phone', 40)->nullable();
            }
            if (! Schema::hasColumn('orders', 'delivery_method')) {
                $table->string('delivery_method', 60)->default('Domicilio');
            }
            if (! Schema::hasColumn('orders', 'delivery_address')) {
                $table->string('delivery_address', 255)->nullable();
            }
            if (! Schema::hasColumn('orders', 'free_shipping')) {
                $table->boolean('free_shipping')->default(false);
            }
            if (! Schema::hasColumn('orders', 'shipping_cost')) {
                $table->decimal('shipping_cost', 10, 2)->default(0);
            }
            if (! Schema::hasColumn('orders', 'subtotal')) {
                $table->decimal('subtotal', 10, 2)->default(0);
            }
            if (! Schema::hasColumn('orders', 'admin_note')) {
                $table->text('admin_note')->nullable();
            }
            if (! Schema::hasColumn('orders', 'status_updated_at')) {
                $table->timestamp('status_updated_at')->nullable();
            }

            if (! Schema::hasIndex('orders', ['user_id', 'status'])) {
                $table->index(['user_id', 'status']);
            }
        });

        Schema::table('order_details', function (Blueprint $table): void {
            if (! Schema::hasColumn('order_details', 'codigo')) {
                $table->string('codigo', 50)->nullable();
            }
            if (! Schema::hasColumn('order_details', 'name')) {
                $table->string('name', 200)->nullable();
            }
            if (! Schema::hasColumn('order_details', 'line_total')) {
                $table->decimal('line_total', 10, 2)->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_details', function (Blueprint $table): void {
            $cols = array_values(array_filter(['codigo', 'name', 'line_total'], fn (string $c): bool => Schema::hasColumn('order_details', $c)));
            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });

        $hasOrdersUserStatusIndex = Schema::hasIndex('orders', ['user_id', 'status']);

        Schema::table('orders', function (Blueprint $table) use ($hasOrdersUserStatusIndex): void {
            if ($hasOrdersUserStatusIndex) {
                $table->dropIndex(['user_id', 'status']);
            }
            $cols = array_values(array_filter([
                'customer_name',
                'customer_email',
                'phone',
                'delivery_method',
                'delivery_address',
                'free_shipping',
                'shipping_cost',
                'subtotal',
                'admin_note',
                'status_updated_at',
            ], fn (string $c): bool => Schema::hasColumn('orders', $c)));
            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });
    }
};
