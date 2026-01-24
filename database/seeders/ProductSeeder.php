<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Media;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $parfumees = Category::where('slug', 'bougies-parfumees')->first();
        $decoratives = Category::where('slug', 'bougies-decoratives')->first();
        $limitees = Category::where('slug', 'editions-limitees')->first();

        $products = [
            // Bougies Parfumées
            [
                'category_id' => $parfumees->id,
                'name' => 'Bougie Vanille Bourbon',
                'slug' => 'bougie-vanille-bourbon',
                'description' => 'Notes chaudes de vanille de Madagascar avec une touche de caramel',
                'long_description' => 'Notre bougie Vanille Bourbon est fabriquée à partir de cire de soja 100% naturelle et parfumée avec de l\'essence de vanille de Madagascar. Sa combustion lente vous offre jusqu\'à 45 heures de parfum envoûtant.',
                'price' => 8500,
                'stock_quantity' => 50,
                'in_stock' => true,
                'is_featured' => true,
                'images' => [$this->getMediaUrl('1.jpg')],
                'sort_order' => 1,
            ],
            [
                'category_id' => $parfumees->id,
                'name' => 'Bougie Fleur de Coton',
                'slug' => 'bougie-fleur-de-coton',
                'description' => 'Fraîcheur du linge propre avec des notes de musc blanc',
                'price' => 7500,
                'stock_quantity' => 30,
                'in_stock' => true,
                'is_featured' => true,
                'images' => [$this->getMediaUrl('2.jpg')],
                'sort_order' => 2,
            ],
            [
                'category_id' => $parfumees->id,
                'name' => 'Bougie Bois de Santal',
                'slug' => 'bougie-bois-de-santal',
                'description' => 'Parfum boisé et apaisant pour une ambiance zen',
                'price' => 9000,
                'stock_quantity' => 25,
                'in_stock' => true,
                'is_featured' => false,
                'images' => [$this->getMediaUrl('3.jpg')],
                'sort_order' => 3,
            ],
            [
                'category_id' => $parfumees->id,
                'name' => 'Bougie Rose & Pivoine',
                'slug' => 'bougie-rose-pivoine',
                'description' => 'Bouquet floral délicat aux notes de rose et pivoine',
                'price' => 8000,
                'stock_quantity' => 40,
                'in_stock' => true,
                'is_featured' => true,
                'images' => [$this->getMediaUrl('4.jpg')],
                'sort_order' => 4,
            ],

            // Bougies Décoratives
            [
                'category_id' => $decoratives->id,
                'name' => 'Bougie Sculpture Vague',
                'slug' => 'bougie-sculpture-vague',
                'description' => 'Bougie sculptée en forme de vague, pièce décorative unique',
                'price' => 12000,
                'stock_quantity' => 15,
                'in_stock' => true,
                'is_featured' => true,
                'images' => [$this->getMediaUrl('5.jpg')],
                'sort_order' => 5,
            ],
            [
                'category_id' => $decoratives->id,
                'name' => 'Ensemble Bougies Pilier',
                'slug' => 'ensemble-bougies-pilier',
                'description' => 'Set de 3 bougies piliers de tailles variées',
                'price' => 15000,
                'stock_quantity' => 20,
                'in_stock' => true,
                'is_featured' => false,
                'images' => [$this->getMediaUrl('6.jpg')],
                'sort_order' => 6,
            ],
            [
                'category_id' => $decoratives->id,
                'name' => 'Bougie Géométrique',
                'slug' => 'bougie-geometrique',
                'description' => 'Design moderne aux formes géométriques',
                'price' => 10000,
                'stock_quantity' => 30,
                'in_stock' => true,
                'is_featured' => false,
                'images' => [$this->getMediaUrl('7.jpg')],
                'sort_order' => 7,
            ],
            [
                'category_id' => $decoratives->id,
                'name' => 'Bougie Terrazzo',
                'slug' => 'bougie-terrazzo',
                'description' => 'Effet terrazzo coloré dans un pot en béton',
                'price' => 11000,
                'stock_quantity' => 25,
                'in_stock' => true,
                'is_featured' => false,
                'images' => [$this->getMediaUrl('8.jpg')],
                'sort_order' => 8,
            ],

            // Éditions Limitées
            [
                'category_id' => $limitees->id,
                'name' => 'Collection Sahel - Coucher de Soleil',
                'slug' => 'collection-sahel-coucher-soleil',
                'description' => 'Édition limitée inspirée des couleurs du Sahel au crépuscule',
                'price' => 18000,
                'stock_quantity' => 10,
                'in_stock' => true,
                'is_featured' => true,
                'images' => [$this->getMediaUrl('9.jpg')],
                'sort_order' => 9,
            ],
            [
                'category_id' => $limitees->id,
                'name' => 'Collection Sahel - Nuit Étoilée',
                'slug' => 'collection-sahel-nuit-etoilee',
                'description' => 'Notes de jasmin et encens sous le ciel nocturne du désert',
                'price' => 18000,
                'stock_quantity' => 10,
                'in_stock' => true,
                'is_featured' => false,
                'images' => [$this->getMediaUrl('10.jpg')],
                'sort_order' => 10,
            ],
            [
                'category_id' => $limitees->id,
                'name' => 'Coffret Découverte',
                'slug' => 'coffret-decouverte',
                'description' => 'Coffret de 4 mini bougies pour découvrir nos parfums',
                'price' => 22000,
                'stock_quantity' => 20,
                'in_stock' => true,
                'is_featured' => true,
                'images' => [$this->getMediaUrl('11.jpg')],
                'sort_order' => 11,
            ],
            [
                'category_id' => $limitees->id,
                'name' => 'Bougie XL Prestige',
                'slug' => 'bougie-xl-prestige',
                'description' => 'Grande bougie 3 mèches pour les grands espaces',
                'price' => 25000,
                'stock_quantity' => 8,
                'in_stock' => true,
                'is_featured' => false,
                'images' => [$this->getMediaUrl('12.jpg')],
                'sort_order' => 12,
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }

    private function getMediaUrl(string $filename): string
    {
        $media = Media::where('original_filename', $filename)->first();

        if ($media) {
            return $media->url;
        }

        return "/pictures/{$filename}";
    }
}
