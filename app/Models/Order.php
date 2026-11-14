<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PREPARING = 'preparing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * @var array<string, string>
     */
    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'Pendiente',
        self::STATUS_PREPARING => 'En preparación',
        self::STATUS_COMPLETED => 'Completado',
        self::STATUS_CANCELLED => 'Cancelado',
    ];

    /**
     * @var list<string>
     */
    protected $appends = [
        'status_label',
    ];

    protected $fillable = [
        'user_id',
        'order_number',
        'customer_name',
        'customer_email',
        'phone',
        'delivery_method',
        'delivery_address',
        'free_shipping',
        'shipping_cost',
        'subtotal',
        'total',
        'status',
        'admin_note',
        'placed_at',
        'status_updated_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'free_shipping' => 'boolean',
            'shipping_cost' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
            'placed_at' => 'datetime',
            'status_updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderDetail::class);
    }

    /**
     * Etiqueta legible en español del estado del pedido.
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? 'Pendiente';
    }

    /**
     * Catálogo de estados válidos con su etiqueta en español.
     *
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return self::STATUS_LABELS;
    }

    /**
     * Normaliza un estado recibido en español o clave canónica al valor de BD.
     */
    public static function normalizeStatus(?string $status): ?string
    {
        if ($status === null) {
            return null;
        }

        $normalized = mb_strtolower(trim($status));

        return match ($normalized) {
            'pending', 'pendiente' => self::STATUS_PENDING,
            'preparing', 'en preparacion', 'en preparación', 'en_preparacion', 'preparacion', 'preparación' => self::STATUS_PREPARING,
            'completed', 'completado', 'completada', 'entregado' => self::STATUS_COMPLETED,
            'cancelled', 'canceled', 'cancelado', 'cancelada' => self::STATUS_CANCELLED,
            default => null,
        };
    }

    /**
     * Etiqueta amigable del método de entrega.
     */
    public function deliveryMethodLabel(): string
    {
        return mb_strtolower((string) $this->delivery_method) === 'sucursal'
            ? 'Recolección en sucursal'
            : 'Envío a domicilio';
    }
}
