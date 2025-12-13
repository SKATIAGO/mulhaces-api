<?php

namespace App\Services\Pricing;

/**
 * PricingRule - Interfaz para reglas de pricing
 * 
 * Patrón Strategy: Permite agregar nuevas reglas de descuento sin modificar
 * el código existente (Principio Open/Closed de SOLID)
 * 
 * Cada regla implementa su propia lógica de aplicación
 */
interface PricingRule
{
    /**
     * Aplica la regla de pricing al total
     *
     * @param float $currentTotal Total actual antes de aplicar esta regla
     * @param array $items Items originales (para reglas que necesiten contexto)
     * @return float Nuevo total después de aplicar la regla
     */
    public function apply(float $currentTotal, array $items): float;

    /**
     * Obtiene una descripción legible de la regla
     * 
     * Útil para mostrar al usuario qué descuentos se aplicaron
     *
     * @return string
     */
    public function getDescription(): string;
}
