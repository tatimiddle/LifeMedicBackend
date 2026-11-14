<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class LayoutShellTest extends TestCase
{
    public function test_layout_renders_spanish_sidebar_with_brand_header_non_collapsible_groups_and_touch_target(): void
    {
        $html = Blade::render('
            <x-layouts.app title="Panel" :hideDesktopHeader="true">
                <div>Contenido de prueba</div>
            </x-layouts.app>
        ');

        $this->assertStringContainsString('data-sidebar-brand="true"', $html);
        $this->assertStringContainsString('LifeMedic', $html);
        $this->assertStringContainsString('Menú', $html);
        $this->assertStringContainsString('Otros', $html);
        $this->assertStringNotContainsString('Preferencias', $html);
        $this->assertStringNotContainsString('Reporte de Ventas', $html);
        $this->assertStringNotContainsString('Ayuda y Soporte', $html);
        $this->assertStringNotContainsString('Configuración', $html);
        $this->assertStringNotContainsString('open = !open', $html);
        $this->assertStringNotContainsString('Actualiza a Pro', $html);
        $this->assertStringNotContainsString('Actualizar ahora', $html);
        $this->assertStringContainsString('min-h-[44px] min-w-[44px]', $html);
        $this->assertStringNotContainsString('data-desktop-header="true"', $html);
    }

    public function test_status_badge_avoids_pastel_background_classes(): void
    {
        $html = Blade::render('<x-status-badge status="Pagado" />');

        $this->assertStringContainsString('Pagado', $html);
        $this->assertStringNotContainsString('bg-emerald-50', $html);
        $this->assertStringNotContainsString('bg-blue-50', $html);
        $this->assertStringNotContainsString('bg-amber-50', $html);
        $this->assertStringNotContainsString('bg-rose-50', $html);
    }

    public function test_layout_can_render_secondary_page_header(): void
    {
        $html = Blade::render('
            <x-layouts.app title="Ventas">
                <x-page-header title="Ventas" subtitle="Control de ventas diarias" actionLabel="Nueva Venta" />
            </x-layouts.app>
        ');

        $this->assertStringContainsString('Ventas', $html);
        $this->assertStringContainsString('Nueva Venta', $html);
        $this->assertStringContainsString('data-page-header="true"', $html);
        $this->assertStringContainsString('keydown.escape.window="sidebarOpen = false"', $html);
    }

    public function test_modal_renders_accessible_dialog_attributes(): void
    {
        $html = Blade::render('<x-modal name="testModal" title="Detalle de Prueba"><p>Contenido</p></x-modal>');

        $this->assertStringContainsString('role="dialog"', $html);
        $this->assertStringContainsString('aria-modal="true"', $html);
        $this->assertStringContainsString('aria-label="Detalle de Prueba"', $html);
    }
}
