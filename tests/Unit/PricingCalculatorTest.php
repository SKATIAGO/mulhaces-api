<?php

namespace Tests\Unit;

use App\Services\Pricing\BulkDiscountRule;
use App\Services\Pricing\FixedAmountDiscountRule;
use App\Services\Pricing\PricingCalculator;
use App\Services\Pricing\QuantityDiscountRule;
use PHPUnit\Framework\TestCase;

/**
 * PricingCalculatorTest - Tests para la refactorización del código legacy
 * 
 * Verifica que la lógica nueva replique el comportamiento del código legacy
 * y que las extensiones funcionen correctamente
 */
class PricingCalculatorTest extends TestCase
{
    /**
     * Test: Calcula subtotal correctamente
     */
    public function test_calculates_subtotal_correctly(): void
    {
        $calculator = new PricingCalculator([]);

        $items = [
            ['price' => 100, 'qty' => 2],
            ['price' => 50, 'qty' => 1],
        ];

        $total = $calculator->calculateTotal($items);

        $this->assertEquals(250.0, $total, 'Subtotal debe ser 100*2 + 50*1 = 250');
    }

    /**
     * Test: Aplica descuento del 5% cuando total > 500 (lógica legacy)
     */
    public function test_applies_bulk_discount_when_total_exceeds_minimum(): void
    {
        $calculator = new PricingCalculator([
            new BulkDiscountRule(500, 5),
        ]);

        $items = [
            ['price' => 300, 'qty' => 2], // 600
        ];

        $total = $calculator->calculateTotal($items);

        // 600 - (600 * 0.05) = 600 - 30 = 570
        $this->assertEquals(570.0, $total, 'Debe aplicar 5% de descuento: 600 - 30 = 570');
    }

    /**
     * Test: NO aplica descuento cuando total <= 500
     */
    public function test_does_not_apply_discount_when_below_minimum(): void
    {
        $calculator = new PricingCalculator([
            new BulkDiscountRule(500, 5),
        ]);

        $items = [
            ['price' => 200, 'qty' => 2], // 400
        ];

        $total = $calculator->calculateTotal($items);

        $this->assertEquals(400.0, $total, 'No debe aplicar descuento cuando total es 400 < 500');
    }

    /**
     * Test: Replica exactamente el código legacy
     */
    public function test_replicates_legacy_code_behavior(): void
    {
        // Simular función legacy
        $legacyResult = $this->calculatePriceLegacy([
            ['price' => 100, 'qty' => 3],
            ['price' => 250, 'qty' => 1],
        ]);

        // Calcular con código moderno (con reglas por defecto)
        $calculator = new PricingCalculator();
        $modernResult = $calculator->calculateTotal([
            ['price' => 100, 'qty' => 3],
            ['price' => 250, 'qty' => 1],
        ]);

        $this->assertEquals($legacyResult, $modernResult, 'Código moderno debe replicar resultado legacy');
    }

    /**
     * Test: Valida estructura de items
     */
    public function test_throws_exception_for_invalid_item_structure(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $calculator = new PricingCalculator();
        
        $items = [
            ['price' => 100], // Falta 'qty'
        ];

        $calculator->calculateTotal($items);
    }

    /**
     * Test: Valida price negativo
     */
    public function test_throws_exception_for_negative_price(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $calculator = new PricingCalculator();
        
        $items = [
            ['price' => -100, 'qty' => 1],
        ];

        $calculator->calculateTotal($items);
    }

    /**
     * Test: Maneja items vacíos
     */
    public function test_returns_zero_for_empty_items(): void
    {
        $calculator = new PricingCalculator();
        
        $total = $calculator->calculateTotal([]);

        $this->assertEquals(0.0, $total, 'Total debe ser 0 para items vacíos');
    }

    /**
     * Test: Aplica múltiples reglas en secuencia
     */
    public function test_applies_multiple_rules_sequentially(): void
    {
        $calculator = new PricingCalculator([
            new BulkDiscountRule(500, 10), // 10% si > 500
            new FixedAmountDiscountRule(0, 50), // -$50 siempre
        ]);

        $items = [
            ['price' => 300, 'qty' => 2], // 600
        ];

        // Primera regla: 600 - 60 = 540
        // Segunda regla: 540 - 50 = 490
        $total = $calculator->calculateTotal($items);

        $this->assertEquals(490.0, $total);
    }

    /**
     * Test: QuantityDiscountRule funciona correctamente
     */
    public function test_quantity_discount_rule_works(): void
    {
        $calculator = new PricingCalculator([
            new QuantityDiscountRule(3, 15), // 15% si qty >= 3
        ]);

        $items = [
            ['price' => 100, 'qty' => 2],
            ['price' => 100, 'qty' => 2], // Total qty = 4
        ];

        // Subtotal: 400
        // Descuento 15%: 400 - 60 = 340
        $total = $calculator->calculateTotal($items);

        $this->assertEquals(340.0, $total);
    }

    /**
     * Test: getBreakdown devuelve información detallada
     */
    public function test_get_breakdown_returns_detailed_information(): void
    {
        $calculator = new PricingCalculator([
            new BulkDiscountRule(500, 5),
        ]);

        $items = [
            ['price' => 300, 'qty' => 2], // 600
        ];

        $breakdown = $calculator->getBreakdown($items);

        $this->assertArrayHasKey('subtotal', $breakdown);
        $this->assertArrayHasKey('discounts', $breakdown);
        $this->assertArrayHasKey('total', $breakdown);

        $this->assertEquals(600.0, $breakdown['subtotal']);
        $this->assertEquals(570.0, $breakdown['total']);
        $this->assertCount(1, $breakdown['discounts']);
        $this->assertEquals(30.0, $breakdown['discounts'][0]['amount']);
    }

    /**
     * Test: Total nunca es negativo
     */
    public function test_total_never_negative(): void
    {
        $calculator = new PricingCalculator([
            new FixedAmountDiscountRule(0, 1000), // Descuenta más de lo que vale
        ]);

        $items = [
            ['price' => 50, 'qty' => 1],
        ];

        $total = $calculator->calculateTotal($items);

        $this->assertEquals(0.0, $total, 'Total nunca debe ser negativo');
    }

    /**
     * Función helper que replica el código legacy
     */
    private function calculatePriceLegacy(array $items): float
    {
        $total = 0;
        foreach ($items as $i) {
            if (!isset($i['price']) || !isset($i['qty'])) {
                continue;
            }
            $total += $i['price'] * $i['qty'];
        }

        if ($total > 500) {
            $total = $total - ($total * 0.05);
        }

        return $total;
    }
}
