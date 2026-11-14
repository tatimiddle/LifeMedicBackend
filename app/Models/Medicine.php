<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medicine extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'codigo',
        'catalog_type',
        'name',
        'source_name',
        'active_ingredient',
        'formula',
        'formula_summary',
        'san_nicolas_category',
        'brand',
        'laboratory',
        'indications',
        'contraindications',
        'description',
        'batch',
        'expiry',
        'expiry_status',
        'prescription',
        'prescription_required',
        'price',
        'previous_price',
        'stock',
        'min_stock',
        'is_active',
        'image_path',
        'image_url',
        'source_url',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'formula' => 'array',
            'prescription_required' => 'boolean',
            'price' => 'decimal:2',
            'previous_price' => 'decimal:2',
            'stock' => 'integer',
            'min_stock' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderDetails(): HasMany
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderDetail::class);
    }
}
