<?php

namespace App\Services\Pricing;

/**
 * BulkDiscountRule - Descuento por volumen
 * 
 * Aplica un descuento porcentual si el total supera un mínimo.
 * Esta regla replica la lógica del código legacy:
 *   "if ($total > 500) { apply 5% discount }"
 */
class BulkDiscountRule implements PricingRule
{
    /**
     * @var float Monto mínimo para aplicar el descuento
     */
    private float $minimumAmount;

    /**
     * @var float Porcentaje de descuento (ej: 5.0 para 5%)
     */
    private float $discountPercentage;

    /**
     * Constructor
     *
     * @param float $minimumAmount Monto mínimo para activar el descuento
     * @param float $discountPercentage Porcentaje de descuento a aplicar
     */
    public function __construct(float $minimumAmount, float $discountPercentage)
    {
        $this->minimumAmount = $minimumAmount;
        $this->discountPercentage = $discountPercentage;
    }

    /**
     * Aplica descuento por volumen si se cumple la condición
     *
     * @param float $currentTotal
     * @param array $items No usado en esta regla, pero disponible para otras
     * @return float
     */
    public function apply(float $currentTotal, array $items): float
    {
        // Solo aplicar si el total supera el mínimo
        if ($currentTotal <= $this->minimumAmount) {
            return $currentTotal;
        }

        // Calcular descuento: total - (total * porcentaje / 100)
        $discountAmount = $currentTotal * ($this->discountPercentage / 100);
        
        return $currentTotal - $discountAmount;
    }

    /**
     * Descripción de la regla
     *
     * @return string
     */
    public function getDescription(): string
    {
        return sprintf(
            'Descuento de %.0f%% por compras superiores a %.2f',
            $this->discountPercentage,
            $this->minimumAmount
        );
    }
}
