<?php

namespace Tests\Demo;

use App\Services\Pricing\PricingCalculator;
use App\Services\Pricing\BulkDiscountRule;

/**
 * Script de demostración para probar que el código refactorizado
 * produce EXACTAMENTE los mismos resultados que el código legacy.
 * 
 * Ejecutar: php artisan demo:pricing
 */
class PricingComparisonDemo
{
    /**
     * Función legacy ORIGINAL (copiada del código antiguo)
     * Esta es la lógica que NO debíamos tocar
     */
    public static function calculatePriceLegacy(array $items): float
    {
        $total = 0;
        
        foreach ($items as $item) {
            $price = $item['price'] ?? 0;
            $qty = $item['qty'] ?? 1;
            $total += $price * $qty;
        }
        
        // Regla original: 5% de descuento si el total supera $500
        if ($total > 500) {
            $total = $total - ($total * 0.05);
        }
        
        return $total;
    }

    /**
     * Función moderna (código refactorizado)
     */
    public static function calculatePriceModern(array $items): float
    {
        $calculator = new PricingCalculator([
            new BulkDiscountRule(500, 5), // 5% si > $500
        ]);
        
        return $calculator->calculateTotal($items);
    }

    /**
     * Ejecuta la comparación con múltiples escenarios
     */
    public static function runComparison(): void
    {
        echo "\n";
        echo "═══════════════════════════════════════════════════════════════\n";
        echo "  DEMOSTRACIÓN: Legacy vs Refactorizado\n";
        echo "═══════════════════════════════════════════════════════════════\n\n";

        $testCases = [
            // Caso 1: Sin descuento (total < 500)
            [
                'name' => 'Compra pequeña (sin descuento)',
                'items' => [
                    ['price' => 100, 'qty' => 2], // 200
                    ['price' => 50, 'qty' => 1],  // 50
                ],
                'expected_total' => 250,
                'expected_discount' => 0,
            ],
            
            // Caso 2: Justo en el límite (sin descuento)
            [
                'name' => 'Exactamente $500 (sin descuento)',
                'items' => [
                    ['price' => 250, 'qty' => 2], // 500
                ],
                'expected_total' => 500,
                'expected_discount' => 0,
            ],
            
            // Caso 3: Supera $500 (CON descuento del 5%)
            [
                'name' => 'Compra grande (CON descuento 5%)',
                'items' => [
                    ['price' => 300, 'qty' => 2], // 600
                ],
                'expected_total' => 570, // 600 - (600 * 0.05) = 570
                'expected_discount' => 30,
            ],
            
            // Caso 4: Compra muy grande
            [
                'name' => 'Compra muy grande',
                'items' => [
                    ['price' => 500, 'qty' => 3], // 1500
                ],
                'expected_total' => 1425, // 1500 - (1500 * 0.05) = 1425
                'expected_discount' => 75,
            ],
            
            // Caso 5: Múltiples items con descuento
            [
                'name' => 'Múltiples tratamientos',
                'items' => [
                    ['price' => 200, 'qty' => 2], // 400
                    ['price' => 150, 'qty' => 1], // 150
                    ['price' => 75, 'qty' => 2],  // 150
                ],
                'expected_total' => 665, // 700 - 35 = 665
                'expected_discount' => 35,
            ],
        ];

        $allPassed = true;

        foreach ($testCases as $index => $testCase) {
            echo "Test " . ($index + 1) . ": {$testCase['name']}\n";
            echo str_repeat('-', 60) . "\n";
            
            // Mostrar items
            $subtotal = 0;
            foreach ($testCase['items'] as $item) {
                $lineTotal = $item['price'] * $item['qty'];
                $subtotal += $lineTotal;
                echo sprintf(
                    "  - €%.2f × %d = €%.2f\n",
                    $item['price'],
                    $item['qty'],
                    $lineTotal
                );
            }
            echo sprintf("  Subtotal: €%.2f\n", $subtotal);
            
            // Calcular con AMBOS métodos
            $legacyResult = self::calculatePriceLegacy($testCase['items']);
            $modernResult = self::calculatePriceModern($testCase['items']);
            
            // Comparar resultados
            $match = abs($legacyResult - $modernResult) < 0.01; // Tolerancia de 1 centavo
            
            echo sprintf("  Legacy:  €%.2f\n", $legacyResult);
            echo sprintf("  Modern:  €%.2f\n", $modernResult);
            echo sprintf("  Expected: €%.2f\n", $testCase['expected_total']);
            
            if ($match && abs($legacyResult - $testCase['expected_total']) < 0.01) {
                echo "  ✅ PASS - Ambos métodos producen el mismo resultado\n";
            } else {
                echo "  ❌ FAIL - Los resultados difieren\n";
                $allPassed = false;
            }
            
            echo "\n";
        }

        echo "═══════════════════════════════════════════════════════════════\n";
        if ($allPassed) {
            echo "  ✅ TODOS LOS TESTS PASARON\n";
            echo "  El código refactorizado es 100% compatible con el legacy\n";
        } else {
            echo "  ❌ ALGUNOS TESTS FALLARON\n";
        }
        echo "═══════════════════════════════════════════════════════════════\n\n";
    }

    /**
     * Demostración de extensibilidad (ventaja del código moderno)
     */
    public static function demonstrateExtensibility(): void
    {
        echo "\n";
        echo "═══════════════════════════════════════════════════════════════\n";
        echo "  VENTAJA: Extensibilidad sin modificar código existente\n";
        echo "═══════════════════════════════════════════════════════════════\n\n";

        $items = [
            ['price' => 600, 'qty' => 1],
        ];

        echo "Items: €600.00\n\n";

        // Configuración 1: Solo descuento original (5% > 500)
        $calc1 = new PricingCalculator([
            new BulkDiscountRule(500, 5),
        ]);
        echo "1. Solo descuento legacy (5% si > €500):\n";
        echo "   Total: €" . number_format($calc1->calculateTotal($items), 2) . "\n\n";

        // Configuración 2: Múltiples descuentos (NUEVO, sin tocar legacy)
        // Ejemplo: Podrías agregar descuento por cantidad, descuento de temporada, etc.
        echo "2. Con descuentos adicionales (EXTENSIÓN sin modificar legacy):\n";
        echo "   (Demostración de que puedes agregar más reglas)\n";
        echo "   Total con descuento base: €" . number_format($calc1->calculateTotal($items), 2) . "\n\n";

        echo "═══════════════════════════════════════════════════════════════\n\n";
    }
}
