<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['name' => 'Teclado mecanico RGB', 'description' => 'Switches rojos, retroiluminado, cable trenzado.', 'price' => 49.99, 'stock' => 30, 'sku' => 'KB-RGB-001'],
            ['name' => 'Mouse inalambrico ergonomico', 'description' => 'Sensor optico 1600 DPI, bateria de larga duracion.', 'price' => 19.99, 'stock' => 100, 'sku' => 'MS-WL-002'],
            ['name' => 'Monitor 24" Full HD', 'description' => 'Panel IPS, 75Hz, HDMI/DisplayPort.', 'price' => 149.00, 'stock' => 15, 'sku' => 'MN-24-003'],
            ['name' => 'Audifonos Bluetooth', 'description' => 'Cancelacion de ruido activa, 30h de bateria.', 'price' => 59.90, 'stock' => 40, 'sku' => 'HP-BT-004'],
            ['name' => 'Laptop Ultrabook 14"', 'description' => 'Core i5, 16GB RAM, 512GB SSD.', 'price' => 899.00, 'stock' => 8, 'sku' => 'LP-14-005'],
            ['name' => 'Webcam Full HD 1080p', 'description' => 'Enfoque automatico, microfono integrado.', 'price' => 34.50, 'stock' => 50, 'sku' => 'WC-1080-006'],
            ['name' => 'Silla ergonomica de oficina', 'description' => 'Soporte lumbar ajustable, reposabrazos 3D.', 'price' => 189.99, 'stock' => 12, 'sku' => 'CH-ERG-007'],
            ['name' => 'Disco SSD 1TB NVMe', 'description' => 'Velocidad de lectura hasta 3500MB/s.', 'price' => 79.99, 'stock' => 60, 'sku' => 'SSD-1TB-008'],
            ['name' => 'Router WiFi 6', 'description' => 'Doble banda, cobertura hasta 200m2.', 'price' => 99.00, 'stock' => 20, 'sku' => 'RT-WF6-009'],
            ['name' => 'Power Bank 20000mAh', 'description' => 'Carga rapida USB-C 20W.', 'price' => 29.99, 'stock' => 70, 'sku' => 'PB-20K-010'],
        ];

        foreach ($products as $product) {
            Product::firstOrCreate(['sku' => $product['sku']], $product + ['is_active' => true]);
        }
    }
}
