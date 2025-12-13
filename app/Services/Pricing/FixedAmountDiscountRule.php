<?php

namespace App\Services\Pricing;

/**
 * FixedAmountDiscountRule - Descuento de cantidad fija
 * 
 * Ejemplo de extensibilidad: Descuenta una cantidad fija en lugar de porcentaje
 * Ej: "Descuenta $50 si compras más de $300"
 */
class FixedAmountDiscountRule implements PricingRule
{
    private float $minimumAmount;
    private float $discountAmount;

    public function __construct(float $minimumAmount, float $discountAmount)
    {
        $this->minimumAmount = $minimumAmount;
        $this->discountAmount = $discountAmount;
    }

    public function apply(float $currentTotal, array $items): float
    {
        if ($currentTotal <= $this->minimumAmount) {
            return $currentTotal;
        }

        return $currentTotal - $this->discountAmount;
    }

    public function getDescription(): string
    {
        return sprintf(
            'Descuento de $%.2f por compras superiores a $%.2f',
            $this->discountAmount,
            $this->minimumAmount
        );
    }
}
