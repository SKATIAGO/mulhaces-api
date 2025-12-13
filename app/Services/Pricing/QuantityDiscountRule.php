<?php

namespace App\Services\Pricing;

/**
 * QuantityDiscountRule - Descuento por cantidad de items
 * 
 * Ejemplo: "Si compras 3 o más tratamientos, obtén 10% de descuento"
 */
class QuantityDiscountRule implements PricingRule
{
    private int $minimumQuantity;
    private float $discountPercentage;

    public function __construct(int $minimumQuantity, float $discountPercentage)
    {
        $this->minimumQuantity = $minimumQuantity;
        $this->discountPercentage = $discountPercentage;
    }

    public function apply(float $currentTotal, array $items): float
    {
        // Contar cantidad total de items
        $totalQuantity = array_sum(array_column($items, 'qty'));

        if ($totalQuantity < $this->minimumQuantity) {
            return $currentTotal;
        }

        $discountAmount = $currentTotal * ($this->discountPercentage / 100);
        return $currentTotal - $discountAmount;
    }

    public function getDescription(): string
    {
        return sprintf(
            'Descuento de %.0f%% por comprar %d o más items',
            $this->discountPercentage,
            $this->minimumQuantity
        );
    }
}
