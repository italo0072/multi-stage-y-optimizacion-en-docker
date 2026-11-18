<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class ImportProductImages extends Command
{
    protected $signature = 'productos:importar-imagenes {archivo : CSV UTF-8 de la tabla products}';
    protected $description = 'Importa las URL de imágenes por SKU y agrega los productos que falten.';

    private const COLUMNS = ['sku', 'name', 'description', 'price', 'stock', 'image_url', 'is_active'];

    public function handle(): int
    {
        $path = $this->argument('archivo');
        if (!is_file($path) || !is_readable($path) || !($file = fopen($path, 'rb'))) {
            $this->error('No se pudo abrir el CSV. Comprueba la ruta dentro del contenedor.');
            return self::FAILURE;
        }

        try {
            $first = preg_replace('/^\xEF\xBB\xBF/', '', (string) fgets($file));
            $delimiter = str_contains($first, ',') ? ',' : ';';
            $header = array_map('trim', str_getcsv($first, $delimiter, '"', ''));
            if (array_diff(self::COLUMNS, $header) || count($header) !== count(array_unique($header))) {
                throw new RuntimeException('Exporta el CSV con nombres de columna: '.implode(', ', self::COLUMNS));
            }

            [$saved, $empty] = DB::transaction(function () use ($file, $header, $delimiter) {
                $saved = $empty = 0;
                $number = 1;
                while (($fields = fgetcsv($file, 0, $delimiter, '"', '')) !== false) {
                    ++$number;
                    if ($fields === [null]) {
                        continue;
                    }
                    if (count($fields) !== count($header)) {
                        throw new RuntimeException("El registro {$number} tiene una cantidad incorrecta de columnas.");
                    }
                    $row = array_intersect_key(array_combine($header, $fields), array_flip(self::COLUMNS));
                    $row['image_url'] = trim($row['image_url']);
                    if ($row['image_url'] === '' || strtoupper($row['image_url']) === 'NULL') {
                        ++$empty;
                        continue;
                    }
                    $row['sku'] = trim($row['sku']);
                    $validation = Validator::make($row, [
                        'sku' => 'required|string|max:100',
                        'name' => 'required|string|max:255',
                        'description' => 'nullable|string',
                        'price' => 'required|numeric|min:0|max:99999999.99|decimal:0,2',
                        'stock' => 'required|integer|between:0,4294967295',
                        'is_active' => 'required|boolean',
                        'image_url' => 'required|url:http,https|max:2048',
                    ]);
                    if ($validation->fails()) {
                        throw new RuntimeException("Registro {$number}: ".$validation->errors()->first());
                    }
                    if (str_ends_with($row['image_url'], '...') || str_ends_with($row['image_url'], '…')) {
                        throw new RuntimeException("Registro {$number}: exporta la URL completa desde phpMyAdmin.");
                    }
                    $row['created_at'] = $row['updated_at'] = now();
                    // La clave única es el SKU. Una coincidencia cambia únicamente la URL.
                    DB::table('products')->upsert([$row], ['sku'], ['image_url']);
                    ++$saved;
                }
                return [$saved, $empty];
            });
            $this->info("Productos con URL procesados: {$saved}. Filas sin URL omitidas: {$empty}.");
        } catch (RuntimeException $error) {
            $this->error($error->getMessage());
            return self::FAILURE;
        } finally {
            fclose($file);
        }
        return self::SUCCESS;
    }
}
