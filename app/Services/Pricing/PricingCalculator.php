<?php

namespace App\Services\Pricing;

/**
 * PricingCalculator - Versión moderna del cálculo de precios legacy
 * 
 * Refactorización del código legacy/AppointmentPricing.php con:
 * - Programación orientada a objetos
 * - Type hints y validación robusta
 * - Patrón Strategy para reglas de descuento extensibles
 * - Inyección de dependencias
 * - Manejo de errores explícito
 */
class PricingCalculator
{
    /**
     * @var PricingRule[] Array de reglas de pricing a aplicar
     */
    private array $pricingRules = [];

    /**
     * Constructor - Inicializa con reglas de pricing por defecto
     * 
     * @param array $pricingRules Reglas personalizadas (opcional)
     */
    public function __construct(array $pricingRules = [])
    {
        // Si no se proporcionan reglas, usar las por defecto
        $this->pricingRules = empty($pricingRules) 
            ? $this->getDefaultRules() 
            : $pricingRules;
    }

    /**
     * Calcula el precio total de un conjunto de items con descuentos aplicados
     *
     * @param array $items Array de items con estructura: ['price' => float, 'qty' => int]
     * @return float Precio total calculado
     * @throws \InvalidArgumentException Si los items tienen formato inválido
     */
    public function calculateTotal(array $items): float
    {
        // Validar que $items sea un array
        if (empty($items)) {
            return 0.0;
        }

        // Calcular subtotal (suma de precio * cantidad)
        $subtotal = $this->calculateSubtotal($items);

        // Aplicar todas las reglas de pricing al subtotal
        $total = $subtotal;
        foreach ($this->pricingRules as $rule) {
            $total = $rule->apply($total, $items);
        }

        // Asegurar que el total nunca sea negativo
        return max(0.0, $total);
    }

    /**
     * Calcula el subtotal (suma de precio * cantidad) sin descuentos
     *
     * @param array $items
     * @return float
     * @throws \InvalidArgumentException
     */
    private function calculateSubtotal(array $items): float
    {
        $subtotal = 0.0;

        foreach ($items as $index => $item) {
            // Validar estructura del item
            $this->validateItem($item, $index);

            // Calcular y sumar: price * qty
            $itemTotal = (float) $item['price'] * (int) $item['qty'];
            $subtotal += $itemTotal;
        }

        return $subtotal;
    }

    /**
     * Valida que un item tenga la estructura correcta
     *
     * @param mixed $item
     * @param int $index
     * @throws \InvalidArgumentException
     */
    private function validateItem($item, int $index): void
    {
        // Verificar que sea un array
        if (!is_array($item)) {
            throw new \InvalidArgumentException(
                "Item en posición {$index} debe ser un array"
            );
        }

        // Verificar que tenga 'price'
        if (!isset($item['price'])) {
            throw new \InvalidArgumentException(
                "Item en posición {$index} debe tener 'price'"
            );
        }

        // Verificar que tenga 'qty'
        if (!isset($item['qty'])) {
            throw new \InvalidArgumentException(
                "Item en posición {$index} debe tener 'qty'"
            );
        }

        // Verificar que price sea numérico y positivo
        if (!is_numeric($item['price']) || $item['price'] < 0) {
            throw new \InvalidArgumentException(
                "Item en posición {$index} tiene 'price' inválido"
            );
        }

        // Verificar que qty sea numérico y positivo
        if (!is_numeric($item['qty']) || $item['qty'] < 0) {
            throw new \InvalidArgumentException(
                "Item en posición {$index} tiene 'qty' inválido"
            );
        }
    }

    /**
     * Obtiene las reglas de pricing por defecto
     * 
     * Esto replica la lógica legacy: 5% descuento si total > 500
     *
     * @return PricingRule[]
     */
    private function getDefaultRules(): array
    {
        return [
            new BulkDiscountRule(
                minimumAmount: 500.0,
                discountPercentage: 5.0
            ),
        ];
    }

    /**
     * Agrega una nueva regla de pricing
     *
     * @param PricingRule $rule
     * @return self Para permitir method chaining
     */
    public function addRule(PricingRule $rule): self
    {
        $this->pricingRules[] = $rule;
        return $this;
    }

    /**
     * Obtiene el breakdown detallado del cálculo
     * 
     * Útil para mostrar al usuario cómo se calculó el precio final
     *
     * @param array $items
     * @return array
     */
    public function getBreakdown(array $items): array
    {
        $subtotal = $this->calculateSubtotal($items);
        $discounts = [];
        $total = $subtotal;

        // Aplicar reglas y trackear descuentos
        foreach ($this->pricingRules as $rule) {
            $beforeDiscount = $total;
            $total = $rule->apply($total, $items);
            $discountAmount = $beforeDiscount - $total;

            if ($discountAmount > 0) {
                $discounts[] = [
                    'rule' => get_class($rule),
                    'description' => $rule->getDescription(),
                    'amount' => $discountAmount,
                ];
            }
        }

        return [
            'subtotal' => $subtotal,
            'discounts' => $discounts,
            'total' => $total,
        ];
    }
}
