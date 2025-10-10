<?php

namespace App\Models\Digitra;

use App\Models\Digitra\Concerns\Cacheable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Establecimiento extends Model
{
    use Cacheable;
    protected $connection = 'mysql'; // SOLO LECTURA
    protected $table = 'establecimientos';

    // 🔒 SEGURIDAD: Bloquear mass-assignment
    protected $guarded = ['*'];
    protected $fillable = [];

    protected $casts = [
        'email' => 'array',
        'auto_send_tra' => 'boolean',
        'documentacion' => 'boolean',
        'deleted' => 'boolean',
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con el usuario propietario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con reservas
     */
    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class, 'establecimiento_id');
    }

    /**
     * Scope para filtrar solo establecimientos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('deleted', false);
    }

    /**
     * Scope para establecimientos que cumplen aniversario próximamente
     */
    public function scopeProximosAniversarios($query, int $dias = 30)
    {
        return $query->whereRaw(
            'DATE_ADD(created_at, INTERVAL 1 YEAR) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)',
            [$dias]
        );
    }

    /**
     * Scope para establecimientos que ya cumplieron 1 año
     */
    public function scopeYaCumplieronAnio($query)
    {
        return $query->whereRaw('DATEDIFF(CURDATE(), created_at) >= 365');
    }

    /**
     * Accessor para obtener emails formateados
     */
    public function getEmailsAttribute()
    {
        return is_array($this->email) ? implode(', ', $this->email) : $this->email;
    }

    /**
     * Accessor para calcular días hasta el aniversario
     */
    public function getDiasParaAniversarioAttribute()
    {
        if (!$this->created_at) {
            return null;
        }

        $aniversario = $this->created_at->copy()->addYear();
        return now()->diffInDays($aniversario, false);
    }

    /**
     * Accessor para obtener la fecha de aniversario
     */
    public function getFechaAniversarioAttribute()
    {
        if (!$this->created_at) {
            return null;
        }

        return $this->created_at->copy()->addYear();
    }
}
