# KKN Candles - Spécification API Laravel

## Table des Matières

1. [Introduction](#1-introduction)
2. [Configuration Technique](#2-configuration-technique)
3. [Architecture du Projet](#3-architecture-du-projet)
4. [Modèles et Migrations](#4-modèles-et-migrations)
5. [Authentification JWT](#5-authentification-jwt)
6. [Endpoints API](#6-endpoints-api)
7. [Intégration CinetPay](#7-intégration-cinetpay)
8. [Stockage AWS S3](#8-stockage-aws-s3)
9. [Validation et Sécurité](#9-validation-et-sécurité)
10. [Seeders et Données Initiales](#10-seeders-et-données-initiales)
11. [Commandes d'Installation](#11-commandes-dinstallation)

---

## 1. Introduction

### Description du Projet
KKN Candles est une plateforme e-commerce de vente de bougies artisanales basée à Ouagadougou, Burkina Faso. Cette API Laravel servira de backend pour l'application Next.js existante.

### Stack Technique
- **Framework**: Laravel 11.x
- **PHP**: 8.2+
- **Base de données**: PostgreSQL
- **Authentification**: JWT (tymon/jwt-auth)
- **Paiement**: CinetPay
- **Stockage média**: AWS S3
- **Cache**: Redis (recommandé)

### Devise et Localisation
- **Devise**: FCFA (Franc CFA)
- **Langue**: Français
- **Pays**: Burkina Faso

---

## 2. Configuration Technique

### Variables d'Environnement (.env)

```env
APP_NAME="KKN Candles API"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

# Base de données PostgreSQL
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=kkn_candles_db
DB_USERNAME=postgres
DB_PASSWORD=

# JWT Configuration
JWT_SECRET=
JWT_TTL=1440
JWT_REFRESH_TTL=20160

# AWS S3 Configuration
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=eu-west-3
AWS_BUCKET=kkn-candles-media
AWS_USE_PATH_STYLE_ENDPOINT=false

# CinetPay Configuration
CINETPAY_API_KEY=
CINETPAY_SITE_ID=
CINETPAY_SECRET_KEY=
CINETPAY_NOTIFY_URL=https://votre-domaine.com/api/payments/cinetpay/notify
CINETPAY_RETURN_URL=https://votre-domaine.com/compte
CINETPAY_CANCEL_URL=https://votre-domaine.com/panier

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=contact@kkncandles.com
MAIL_FROM_NAME="KKN Candles"

# Cache & Queue
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

# CORS Frontend URL
FRONTEND_URL=http://localhost:3000
```

### Fichier config/cors.php

```php
<?php

return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:3000')],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
```

---

## 3. Architecture du Projet

### Structure des Dossiers

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Api/
│   │   │   ├── AuthController.php
│   │   │   ├── ProductController.php
│   │   │   ├── CategoryController.php
│   │   │   ├── CartController.php
│   │   │   ├── OrderController.php
│   │   │   ├── PaymentController.php
│   │   │   ├── ContactController.php
│   │   │   ├── MediaController.php
│   │   │   └── Admin/
│   │   │       ├── DashboardController.php
│   │   │       ├── OrderController.php
│   │   │       ├── ProductController.php
│   │   │       ├── UserController.php
│   │   │       └── MediaController.php
│   │   └── Controller.php
│   ├── Middleware/
│   │   ├── JwtMiddleware.php
│   │   └── AdminMiddleware.php
│   ├── Requests/
│   │   ├── Auth/
│   │   │   ├── RegisterRequest.php
│   │   │   └── LoginRequest.php
│   │   ├── Order/
│   │   │   └── CreateOrderRequest.php
│   │   ├── Product/
│   │   │   └── StoreProductRequest.php
│   │   └── Contact/
│   │       └── ContactMessageRequest.php
│   └── Resources/
│       ├── ProductResource.php
│       ├── ProductCollection.php
│       ├── OrderResource.php
│       ├── OrderCollection.php
│       ├── UserResource.php
│       └── CategoryResource.php
├── Models/
│   ├── User.php
│   ├── Product.php
│   ├── Category.php
│   ├── Order.php
│   ├── OrderItem.php
│   ├── Cart.php
│   ├── CartItem.php
│   ├── Payment.php
│   ├── Media.php
│   └── ContactMessage.php
├── Services/
│   ├── CinetPayService.php
│   ├── S3MediaService.php
│   └── OrderService.php
├── Enums/
│   ├── OrderStatus.php
│   ├── PaymentStatus.php
│   └── UserRole.php
└── Mail/
    ├── OrderConfirmation.php
    ├── OrderStatusUpdate.php
    └── ContactMessage.php

database/
├── migrations/
├── seeders/
│   ├── DatabaseSeeder.php
│   ├── CategorySeeder.php
│   ├── ProductSeeder.php
│   └── AdminSeeder.php
└── factories/

routes/
└── api.php
```

---

## 4. Modèles et Migrations

### 4.1 Migration: users

```php
<?php
// database/migrations/2024_01_01_000001_create_users_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone');
            $table->string('address')->nullable();
            $table->string('city')->default('Ouagadougou');
            $table->enum('role', ['customer', 'admin'])->default('customer');
            $table->boolean('is_active')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
```

### 4.2 Migration: categories

```php
<?php
// database/migrations/2024_01_01_000002_create_categories_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
```

### 4.3 Migration: products

```php
<?php
// database/migrations/2024_01_01_000003_create_products_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('category_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->text('long_description')->nullable();
            $table->integer('price'); // Prix en FCFA (sans décimales)
            $table->integer('compare_price')->nullable(); // Prix barré
            $table->integer('stock_quantity')->default(0);
            $table->boolean('in_stock')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('images')->nullable(); // URLs S3
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category_id', 'is_active']);
            $table->index(['is_featured', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
```

### 4.4 Migration: orders

```php
<?php
// database/migrations/2024_01_01_000004_create_orders_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('order_number')->unique();
            $table->foreignUuid('user_id')->nullable()->constrained()->onDelete('set null');

            // Informations client (copiées au moment de la commande)
            $table->string('customer_first_name');
            $table->string('customer_last_name');
            $table->string('customer_email');
            $table->string('customer_phone');
            $table->string('shipping_address');
            $table->string('shipping_city');
            $table->text('notes')->nullable();

            // Montants
            $table->integer('subtotal'); // Sous-total produits en FCFA
            $table->integer('delivery_fee')->default(2000); // Frais livraison
            $table->integer('total'); // Total en FCFA

            // Statut
            $table->enum('status', [
                'pending',      // En attente de paiement
                'confirmed',    // Paiement confirmé
                'processing',   // En préparation
                'shipped',      // Expédiée
                'delivered',    // Livrée
                'cancelled'     // Annulée
            ])->default('pending');

            $table->string('payment_method')->default('CinetPay');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
```

### 4.5 Migration: order_items

```php
<?php
// database/migrations/2024_01_01_000005_create_order_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('product_id')->nullable()->constrained()->onDelete('set null');

            // Informations produit (copiées au moment de la commande)
            $table->string('product_name');
            $table->integer('product_price'); // Prix unitaire en FCFA
            $table->string('product_image')->nullable();

            $table->integer('quantity');
            $table->integer('total'); // price * quantity
            $table->timestamps();

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
```

### 4.6 Migration: payments

```php
<?php
// database/migrations/2024_01_01_000006_create_payments_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained()->onDelete('cascade');

            $table->string('transaction_id')->unique(); // ID CinetPay
            $table->string('payment_token')->nullable();
            $table->integer('amount'); // Montant en FCFA
            $table->string('currency')->default('XOF');

            $table->enum('status', [
                'pending',
                'processing',
                'completed',
                'failed',
                'refunded'
            ])->default('pending');

            $table->string('payment_method')->nullable(); // mobile_money, card, etc.
            $table->string('operator')->nullable(); // Orange, MTN, etc.
            $table->json('metadata')->nullable(); // Données CinetPay complètes
            $table->text('failure_reason')->nullable();

            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index('transaction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
```

### 4.7 Migration: carts (optionnel, pour panier côté serveur)

```php
<?php
// database/migrations/2024_01_01_000007_create_carts_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('session_id')->nullable(); // Pour les invités
            $table->timestamps();

            $table->index(['user_id']);
            $table->index(['session_id']);
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('cart_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('product_id')->constrained()->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->timestamps();

            $table->unique(['cart_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
    }
};
```

### 4.8 Migration: media

```php
<?php
// database/migrations/2024_01_01_000008_create_media_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('filename');
            $table->string('original_filename');
            $table->string('path'); // Chemin S3
            $table->string('url'); // URL publique S3
            $table->string('disk')->default('s3');
            $table->enum('type', ['image', 'video']);
            $table->string('mime_type');
            $table->integer('size'); // Taille en bytes
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->integer('duration')->nullable(); // Pour vidéos (en secondes)
            $table->string('thumbnail_url')->nullable();
            $table->foreignUuid('uploaded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
```

### 4.9 Migration: contact_messages

```php
<?php
// database/migrations/2024_01_01_000009_create_contact_messages_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('subject');
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['is_read', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
    }
};
```

---

### 4.10 Modèles Eloquent

#### User Model

```php
<?php
// app/Models/User.php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, HasUuids, SoftDeletes;

    protected $fillable = [
        'email',
        'password',
        'first_name',
        'last_name',
        'phone',
        'address',
        'city',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // JWT Methods
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'role' => $this->role,
            'email' => $this->email,
        ];
    }

    // Relationships
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function cart()
    {
        return $this->hasOne(Cart::class);
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    public function scopeCustomers($query)
    {
        return $query->where('role', 'customer');
    }

    // Helpers
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
```

#### Product Model

```php
<?php
// app/Models/Product.php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'long_description',
        'price',
        'compare_price',
        'stock_quantity',
        'in_stock',
        'is_featured',
        'is_active',
        'images',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'compare_price' => 'integer',
            'stock_quantity' => 'integer',
            'in_stock' => 'boolean',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'images' => 'array',
        ];
    }

    // Relationships
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    // Accessors
    public function getMainImageAttribute(): ?string
    {
        return $this->images[0] ?? null;
    }

    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 0, ',', ' ') . ' FCFA';
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('in_stock', true);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'ilike', "%{$term}%")
              ->orWhere('description', 'ilike', "%{$term}%");
        });
    }

    public function scopeByCategory($query, string $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    // Methods
    public function decrementStock(int $quantity): bool
    {
        if ($this->stock_quantity < $quantity) {
            return false;
        }

        $this->decrement('stock_quantity', $quantity);

        if ($this->stock_quantity <= 0) {
            $this->update(['in_stock' => false]);
        }

        return true;
    }
}
```

#### Category Model

```php
<?php
// app/Models/Category.php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'image',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // Relationships
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
```

#### Order Model

```php
<?php
// app/Models/Order.php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'order_number',
        'user_id',
        'customer_first_name',
        'customer_last_name',
        'customer_email',
        'customer_phone',
        'shipping_address',
        'shipping_city',
        'notes',
        'subtotal',
        'delivery_fee',
        'total',
        'status',
        'payment_method',
        'paid_at',
        'shipped_at',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'integer',
            'delivery_fee' => 'integer',
            'total' => 'integer',
            'paid_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // Accessors
    public function getCustomerFullNameAttribute(): string
    {
        return "{$this->customer_first_name} {$this->customer_last_name}";
    }

    public function getFormattedTotalAttribute(): string
    {
        return number_format($this->total, 0, ',', ' ') . ' FCFA';
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'En attente',
            'confirmed' => 'Confirmée',
            'processing' => 'En préparation',
            'shipped' => 'Expédiée',
            'delivered' => 'Livrée',
            'cancelled' => 'Annulée',
            default => $this->status,
        };
    }

    // Scopes
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // Methods
    public static function generateOrderNumber(): string
    {
        $prefix = 'KKN';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -4));
        return "{$prefix}-{$date}-{$random}";
    }

    public function markAsPaid(): void
    {
        $this->update([
            'status' => 'confirmed',
            'paid_at' => now(),
        ]);
    }

    public function markAsShipped(): void
    {
        $this->update([
            'status' => 'shipped',
            'shipped_at' => now(),
        ]);
    }

    public function markAsDelivered(): void
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }
}
```

#### OrderItem Model

```php
<?php
// app/Models/OrderItem.php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'product_price',
        'product_image',
        'quantity',
        'total',
    ];

    protected function casts(): array
    {
        return [
            'product_price' => 'integer',
            'quantity' => 'integer',
            'total' => 'integer',
        ];
    }

    // Relationships
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
```

#### Payment Model

```php
<?php
// app/Models/Payment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'order_id',
        'transaction_id',
        'payment_token',
        'amount',
        'currency',
        'status',
        'payment_method',
        'operator',
        'metadata',
        'failure_reason',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'metadata' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    // Relationships
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Methods
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed(string $reason): void
    {
        $this->update([
            'status' => 'failed',
            'failure_reason' => $reason,
        ]);
    }
}
```

#### Media Model

```php
<?php
// app/Models/Media.php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'filename',
        'original_filename',
        'path',
        'url',
        'disk',
        'type',
        'mime_type',
        'size',
        'width',
        'height',
        'duration',
        'thumbnail_url',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'duration' => 'integer',
        ];
    }

    // Relationships
    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // Scopes
    public function scopeImages($query)
    {
        return $query->where('type', 'image');
    }

    public function scopeVideos($query)
    {
        return $query->where('type', 'video');
    }

    // Accessors
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
```

#### ContactMessage Model

```php
<?php
// app/Models/ContactMessage.php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'is_read',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    // Methods
    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }
}
```

---

## 5. Authentification JWT

### Installation

```bash
composer require php-open-source-saver/jwt-auth
php artisan vendor:publish --provider="PHPOpenSourceSaver\JWTAuth\Providers\LaravelServiceProvider"
php artisan jwt:secret
```

### Configuration config/auth.php

```php
<?php

return [
    'defaults' => [
        'guard' => 'api',
        'passwords' => 'users',
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
        'api' => [
            'driver' => 'jwt',
            'provider' => 'users',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,
];
```

### Middleware JwtMiddleware

```php
<?php
// app/Http/Middleware/JwtMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use Symfony\Component\HttpFoundation\Response;

class JwtMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé'
                ], 401);
            }

            if (!$user->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Compte désactivé'
                ], 403);
            }

        } catch (TokenExpiredException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token expiré',
                'error' => 'token_expired'
            ], 401);
        } catch (TokenInvalidException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalide',
                'error' => 'token_invalid'
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token non fourni',
                'error' => 'token_absent'
            ], 401);
        }

        return $next($request);
    }
}
```

### Middleware AdminMiddleware

```php
<?php
// app/Http/Middleware/AdminMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user || !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé. Droits administrateur requis.'
            ], 403);
        }

        return $next($request);
    }
}
```

### Enregistrement des Middlewares (bootstrap/app.php)

```php
<?php
// bootstrap/app.php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'jwt.auth' => \App\Http\Middleware\JwtMiddleware::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

---

## 6. Endpoints API

### Routes (routes/api.php)

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\Admin\MediaController as AdminMediaController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

// Authentication
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
});

// Products (Public)
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/featured', [ProductController::class, 'featured']);
Route::get('/products/search', [ProductController::class, 'search']);
Route::get('/products/{product}', [ProductController::class, 'show']);

// Categories (Public)
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);
Route::get('/categories/{category}/products', [CategoryController::class, 'products']);

// Media (Public - lecture seule)
Route::get('/media', [MediaController::class, 'index']);
Route::get('/media/images', [MediaController::class, 'images']);
Route::get('/media/videos', [MediaController::class, 'videos']);

// Contact
Route::post('/contact', [ContactController::class, 'send']);

// Payment Webhooks (Public - vérifié par signature)
Route::post('/payments/cinetpay/notify', [PaymentController::class, 'cinetpayNotify']);
Route::get('/payments/cinetpay/return', [PaymentController::class, 'cinetpayReturn']);

/*
|--------------------------------------------------------------------------
| Protected Routes (JWT Auth Required)
|--------------------------------------------------------------------------
*/

Route::middleware('jwt.auth')->group(function () {

    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::put('/password', [AuthController::class, 'updatePassword']);
    });

    // Cart
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/items', [CartController::class, 'addItem']);
        Route::put('/items/{cartItem}', [CartController::class, 'updateItem']);
        Route::delete('/items/{cartItem}', [CartController::class, 'removeItem']);
        Route::delete('/', [CartController::class, 'clear']);
    });

    // Orders
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::post('/', [OrderController::class, 'store']);
        Route::get('/{order}', [OrderController::class, 'show']);
        Route::get('/{order}/invoice', [OrderController::class, 'invoice']);
    });

    // Payments
    Route::prefix('payments')->group(function () {
        Route::post('/cinetpay/init', [PaymentController::class, 'initCinetpay']);
        Route::get('/{payment}/status', [PaymentController::class, 'status']);
    });
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['jwt.auth', 'admin'])->prefix('admin')->group(function () {

    // Dashboard
    Route::get('/dashboard', [AdminDashboardController::class, 'index']);
    Route::get('/dashboard/stats', [AdminDashboardController::class, 'stats']);

    // Orders Management
    Route::get('/orders', [AdminOrderController::class, 'index']);
    Route::get('/orders/{order}', [AdminOrderController::class, 'show']);
    Route::put('/orders/{order}/status', [AdminOrderController::class, 'updateStatus']);

    // Products Management
    Route::get('/products', [AdminProductController::class, 'index']);
    Route::post('/products', [AdminProductController::class, 'store']);
    Route::get('/products/{product}', [AdminProductController::class, 'show']);
    Route::put('/products/{product}', [AdminProductController::class, 'update']);
    Route::delete('/products/{product}', [AdminProductController::class, 'destroy']);

    // Categories Management
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

    // Users Management
    Route::get('/users', [AdminUserController::class, 'index']);
    Route::get('/users/{user}', [AdminUserController::class, 'show']);
    Route::put('/users/{user}', [AdminUserController::class, 'update']);
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy']);

    // Media Management
    Route::post('/media/upload', [AdminMediaController::class, 'upload']);
    Route::delete('/media/{media}', [AdminMediaController::class, 'destroy']);

    // Contact Messages
    Route::get('/messages', [ContactController::class, 'index']);
    Route::get('/messages/{message}', [ContactController::class, 'show']);
    Route::put('/messages/{message}/read', [ContactController::class, 'markAsRead']);
    Route::delete('/messages/{message}', [ContactController::class, 'destroy']);
});
```

### Contrôleurs

#### AuthController

```php
<?php
// app/Http/Controllers/Api/AuthController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * Inscription d'un nouvel utilisateur
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'email' => $request->email,
            'password' => $request->password,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone' => $request->phone,
            'address' => $request->address,
            'city' => $request->city ?? 'Ouagadougou',
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'success' => true,
            'message' => 'Inscription réussie',
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
            ]
        ], 201);
    }

    /**
     * Connexion utilisateur
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Email ou mot de passe incorrect'
            ], 401);
        }

        $user = auth()->user();

        if (!$user->is_active) {
            JWTAuth::invalidate($token);
            return response()->json([
                'success' => false,
                'message' => 'Votre compte a été désactivé'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie',
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
            ]
        ]);
    }

    /**
     * Déconnexion utilisateur
     */
    public function logout(): JsonResponse
    {
        JWTAuth::invalidate(JWTAuth::getToken());

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie'
        ]);
    }

    /**
     * Rafraîchir le token
     */
    public function refresh(): JsonResponse
    {
        $token = JWTAuth::refresh(JWTAuth::getToken());

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
            ]
        ]);
    }

    /**
     * Obtenir l'utilisateur connecté
     */
    public function me(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new UserResource(auth()->user())
        ]);
    }

    /**
     * Mettre à jour le profil
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'address' => 'sometimes|nullable|string|max:500',
            'city' => 'sometimes|string|max:100',
        ]);

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profil mis à jour',
            'data' => new UserResource($user->fresh())
        ]);
    }

    /**
     * Changer le mot de passe
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = auth()->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Le mot de passe actuel est incorrect'
            ], 400);
        }

        $user->update(['password' => $request->password]);

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe modifié avec succès'
        ]);
    }
}
```

#### ProductController

```php
<?php
// app/Http/Controllers/Api/ProductController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductCollection;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Liste des produits avec filtres et pagination
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::with('category')->active();

        // Filtre par catégorie
        if ($request->filled('category_id')) {
            $query->byCategory($request->category_id);
        }

        // Filtre en stock uniquement
        if ($request->boolean('in_stock')) {
            $query->inStock();
        }

        // Recherche
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Tri
        $sortBy = $request->input('sort_by', 'sort_order');
        $sortOrder = $request->input('sort_order', 'asc');

        $allowedSorts = ['name', 'price', 'created_at', 'sort_order'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder === 'desc' ? 'desc' : 'asc');
        }

        $products = $query->paginate($request->input('per_page', 12));

        return response()->json([
            'success' => true,
            'data' => new ProductCollection($products)
        ]);
    }

    /**
     * Produits en vedette
     */
    public function featured(): JsonResponse
    {
        $products = Product::with('category')
            ->active()
            ->featured()
            ->inStock()
            ->orderBy('sort_order')
            ->limit(8)
            ->get();

        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($products)
        ]);
    }

    /**
     * Recherche de produits
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2'
        ]);

        $products = Product::with('category')
            ->active()
            ->search($request->q)
            ->orderBy('name')
            ->paginate(12);

        return response()->json([
            'success' => true,
            'data' => new ProductCollection($products)
        ]);
    }

    /**
     * Détail d'un produit
     */
    public function show(Product $product): JsonResponse
    {
        if (!$product->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Produit non trouvé'
            ], 404);
        }

        $product->load('category');

        return response()->json([
            'success' => true,
            'data' => new ProductResource($product)
        ]);
    }
}
```

#### OrderController

```php
<?php
// app/Http/Controllers/Api/OrderController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\CreateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderCollection;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService
    ) {}

    /**
     * Liste des commandes de l'utilisateur
     */
    public function index(): JsonResponse
    {
        $orders = auth()->user()
            ->orders()
            ->with('items')
            ->recent()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => new OrderCollection($orders)
        ]);
    }

    /**
     * Créer une nouvelle commande
     */
    public function store(CreateOrderRequest $request): JsonResponse
    {
        try {
            $order = DB::transaction(function () use ($request) {
                return $this->orderService->createOrder(
                    auth()->user(),
                    $request->validated()
                );
            });

            return response()->json([
                'success' => true,
                'message' => 'Commande créée avec succès',
                'data' => new OrderResource($order->load('items'))
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Détail d'une commande
     */
    public function show(Order $order): JsonResponse
    {
        // Vérifier que la commande appartient à l'utilisateur
        if ($order->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Commande non trouvée'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new OrderResource($order->load('items', 'payments'))
        ]);
    }

    /**
     * Télécharger la facture
     */
    public function invoice(Order $order): JsonResponse
    {
        if ($order->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Commande non trouvée'
            ], 404);
        }

        // Génération de facture (à implémenter selon besoin)
        // Peut retourner un PDF ou des données JSON

        return response()->json([
            'success' => true,
            'data' => [
                'order' => new OrderResource($order->load('items')),
                'company' => [
                    'name' => 'KKN Candles',
                    'address' => 'Wemtinga, Secteur 23',
                    'city' => 'Ouagadougou',
                    'country' => 'Burkina Faso',
                    'phone' => '+226 XX XX XX XX',
                    'email' => 'contact@kkncandles.com',
                ]
            ]
        ]);
    }
}
```

#### PaymentController

```php
<?php
// app/Http/Controllers/Api/PaymentController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\CinetPayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        private CinetPayService $cinetpay
    ) {}

    /**
     * Initialiser un paiement CinetPay
     */
    public function initCinetpay(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|uuid|exists:orders,id',
        ]);

        $order = Order::findOrFail($request->order_id);

        // Vérifier que la commande appartient à l'utilisateur
        if ($order->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Commande non trouvée'
            ], 404);
        }

        // Vérifier que la commande est en attente
        if ($order->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Cette commande ne peut pas être payée'
            ], 400);
        }

        try {
            $paymentData = $this->cinetpay->initializePayment($order);

            return response()->json([
                'success' => true,
                'message' => 'Paiement initialisé',
                'data' => $paymentData
            ]);

        } catch (\Exception $e) {
            Log::error('CinetPay init error', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'initialisation du paiement'
            ], 500);
        }
    }

    /**
     * Webhook de notification CinetPay (appelé par CinetPay)
     */
    public function cinetpayNotify(Request $request): JsonResponse
    {
        Log::info('CinetPay Notify', $request->all());

        try {
            $result = $this->cinetpay->handleNotification($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Notification traitée'
            ]);

        } catch (\Exception $e) {
            Log::error('CinetPay notify error', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Page de retour après paiement CinetPay
     */
    public function cinetpayReturn(Request $request): JsonResponse
    {
        $transactionId = $request->input('transaction_id');

        if (!$transactionId) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction ID manquant'
            ], 400);
        }

        $payment = Payment::where('transaction_id', $transactionId)->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Paiement non trouvé'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'payment_status' => $payment->status,
                'order_id' => $payment->order_id,
                'order_status' => $payment->order->status,
            ]
        ]);
    }

    /**
     * Vérifier le statut d'un paiement
     */
    public function status(Payment $payment): JsonResponse
    {
        // Vérifier que le paiement appartient à l'utilisateur
        if ($payment->order->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Paiement non trouvé'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $payment->id,
                'status' => $payment->status,
                'amount' => $payment->amount,
                'payment_method' => $payment->payment_method,
                'completed_at' => $payment->completed_at,
            ]
        ]);
    }
}
```

---

## 7. Intégration CinetPay

### Configuration

```php
<?php
// config/cinetpay.php

return [
    'api_key' => env('CINETPAY_API_KEY'),
    'site_id' => env('CINETPAY_SITE_ID'),
    'secret_key' => env('CINETPAY_SECRET_KEY'),

    'notify_url' => env('CINETPAY_NOTIFY_URL'),
    'return_url' => env('CINETPAY_RETURN_URL'),
    'cancel_url' => env('CINETPAY_CANCEL_URL'),

    'currency' => 'XOF', // Franc CFA
    'language' => 'fr',

    // URLs API CinetPay
    'base_url' => 'https://api-checkout.cinetpay.com/v2',
    'payment_url' => 'https://api-checkout.cinetpay.com/v2/payment',
    'check_url' => 'https://api-checkout.cinetpay.com/v2/payment/check',
];
```

### Service CinetPay

```php
<?php
// app/Services/CinetPayService.php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CinetPayService
{
    private string $apiKey;
    private string $siteId;
    private string $secretKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('cinetpay.api_key');
        $this->siteId = config('cinetpay.site_id');
        $this->secretKey = config('cinetpay.secret_key');
        $this->baseUrl = config('cinetpay.base_url');
    }

    /**
     * Initialiser un paiement
     */
    public function initializePayment(Order $order): array
    {
        $transactionId = $this->generateTransactionId();

        // Créer l'enregistrement du paiement
        $payment = Payment::create([
            'order_id' => $order->id,
            'transaction_id' => $transactionId,
            'amount' => $order->total,
            'currency' => 'XOF',
            'status' => 'pending',
        ]);

        // Données pour CinetPay
        $data = [
            'apikey' => $this->apiKey,
            'site_id' => $this->siteId,
            'transaction_id' => $transactionId,
            'amount' => $order->total,
            'currency' => 'XOF',
            'description' => "Commande {$order->order_number}",
            'notify_url' => config('cinetpay.notify_url'),
            'return_url' => config('cinetpay.return_url') . "?transaction_id={$transactionId}",
            'cancel_url' => config('cinetpay.cancel_url'),
            'channels' => 'ALL', // Tous les moyens de paiement
            'lang' => 'fr',

            // Informations client
            'customer_id' => $order->user_id,
            'customer_name' => $order->customer_full_name,
            'customer_surname' => $order->customer_last_name,
            'customer_email' => $order->customer_email,
            'customer_phone_number' => $order->customer_phone,
            'customer_address' => $order->shipping_address,
            'customer_city' => $order->shipping_city,
            'customer_country' => 'BF', // Burkina Faso

            // Métadonnées
            'metadata' => json_encode([
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]),
        ];

        $response = Http::post("{$this->baseUrl}/payment", $data);

        if (!$response->successful()) {
            Log::error('CinetPay API Error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Erreur lors de la communication avec CinetPay');
        }

        $result = $response->json();

        if ($result['code'] !== '201') {
            throw new \Exception($result['message'] ?? 'Erreur CinetPay');
        }

        // Mettre à jour le paiement avec le token
        $payment->update([
            'payment_token' => $result['data']['payment_token'] ?? null,
        ]);

        return [
            'payment_id' => $payment->id,
            'transaction_id' => $transactionId,
            'payment_url' => $result['data']['payment_url'],
            'payment_token' => $result['data']['payment_token'] ?? null,
        ];
    }

    /**
     * Gérer la notification de paiement
     */
    public function handleNotification(array $data): bool
    {
        $transactionId = $data['cpm_trans_id'] ?? null;

        if (!$transactionId) {
            throw new \Exception('Transaction ID manquant');
        }

        // Vérifier le paiement auprès de CinetPay
        $checkData = [
            'apikey' => $this->apiKey,
            'site_id' => $this->siteId,
            'transaction_id' => $transactionId,
        ];

        $response = Http::post(config('cinetpay.check_url'), $checkData);

        if (!$response->successful()) {
            throw new \Exception('Erreur lors de la vérification du paiement');
        }

        $result = $response->json();

        Log::info('CinetPay Check Result', $result);

        $payment = Payment::where('transaction_id', $transactionId)->first();

        if (!$payment) {
            throw new \Exception('Paiement non trouvé');
        }

        $status = $result['data']['status'] ?? 'UNKNOWN';
        $paymentMethod = $result['data']['payment_method'] ?? null;
        $operator = $result['data']['operator_id'] ?? null;

        // Mettre à jour le paiement
        $payment->update([
            'metadata' => $result['data'],
            'payment_method' => $paymentMethod,
            'operator' => $operator,
        ]);

        if ($status === 'ACCEPTED') {
            // Paiement réussi
            $payment->markAsCompleted();
            $payment->order->markAsPaid();

            // TODO: Envoyer email de confirmation

            return true;
        } elseif (in_array($status, ['REFUSED', 'CANCELLED'])) {
            // Paiement échoué
            $payment->markAsFailed($result['data']['description'] ?? 'Paiement refusé');
            return false;
        }

        // Paiement en cours ou autre statut
        return false;
    }

    /**
     * Vérifier le statut d'un paiement
     */
    public function checkPaymentStatus(string $transactionId): array
    {
        $data = [
            'apikey' => $this->apiKey,
            'site_id' => $this->siteId,
            'transaction_id' => $transactionId,
        ];

        $response = Http::post(config('cinetpay.check_url'), $data);

        if (!$response->successful()) {
            throw new \Exception('Erreur lors de la vérification');
        }

        return $response->json();
    }

    /**
     * Générer un ID de transaction unique
     */
    private function generateTransactionId(): string
    {
        return 'KKN_' . Str::upper(Str::random(16)) . '_' . time();
    }
}
```

### Flux de Paiement CinetPay

```
1. Client valide sa commande → POST /api/orders
2. Commande créée avec status "pending"
3. Client initie le paiement → POST /api/payments/cinetpay/init
4. API crée un Payment et appelle CinetPay
5. CinetPay retourne une URL de paiement
6. Client redirigé vers page de paiement CinetPay
7. Client effectue le paiement (Mobile Money, Carte, etc.)
8. CinetPay notifie notre API → POST /api/payments/cinetpay/notify
9. API vérifie le paiement et met à jour Order status → "confirmed"
10. Client redirigé vers return_url avec transaction_id
11. Frontend récupère le statut et affiche confirmation
```

---

## 8. Stockage AWS S3

### Installation

```bash
composer require league/flysystem-aws-s3-v3 "^3.0"
```

### Configuration config/filesystems.php

```php
<?php

return [
    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'visibility' => 'public',
        ],
    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],
];
```

### Service S3 Media

```php
<?php
// app/Services/S3MediaService.php

namespace App\Services;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class S3MediaService
{
    private string $disk = 's3';

    /**
     * Upload une image
     */
    public function uploadImage(UploadedFile $file, ?string $userId = null): Media
    {
        // Générer un nom unique
        $filename = $this->generateFilename($file, 'img');
        $path = "images/{$filename}";

        // Optimiser l'image (optionnel - nécessite intervention/image)
        // $optimized = Image::make($file)->resize(1200, null, function ($constraint) {
        //     $constraint->aspectRatio();
        //     $constraint->upsize();
        // })->encode('jpg', 85);

        // Upload vers S3
        Storage::disk($this->disk)->put($path, file_get_contents($file), 'public');

        // Obtenir les dimensions
        $imageInfo = getimagesize($file->getRealPath());

        // Créer l'enregistrement Media
        return Media::create([
            'filename' => $filename,
            'original_filename' => $file->getClientOriginalName(),
            'path' => $path,
            'url' => Storage::disk($this->disk)->url($path),
            'disk' => $this->disk,
            'type' => 'image',
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'width' => $imageInfo[0] ?? null,
            'height' => $imageInfo[1] ?? null,
            'uploaded_by' => $userId,
        ]);
    }

    /**
     * Upload une vidéo
     */
    public function uploadVideo(UploadedFile $file, ?string $userId = null): Media
    {
        $filename = $this->generateFilename($file, 'vid');
        $path = "videos/{$filename}";

        // Upload vers S3
        Storage::disk($this->disk)->put($path, file_get_contents($file), 'public');

        // TODO: Générer une miniature de la vidéo (nécessite FFmpeg)
        $thumbnailUrl = null;

        return Media::create([
            'filename' => $filename,
            'original_filename' => $file->getClientOriginalName(),
            'path' => $path,
            'url' => Storage::disk($this->disk)->url($path),
            'disk' => $this->disk,
            'type' => 'video',
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'thumbnail_url' => $thumbnailUrl,
            'uploaded_by' => $userId,
        ]);
    }

    /**
     * Supprimer un média
     */
    public function delete(Media $media): bool
    {
        // Supprimer de S3
        if (Storage::disk($this->disk)->exists($media->path)) {
            Storage::disk($this->disk)->delete($media->path);
        }

        // Supprimer la miniature si elle existe
        if ($media->thumbnail_url) {
            $thumbnailPath = str_replace(Storage::disk($this->disk)->url(''), '', $media->thumbnail_url);
            if (Storage::disk($this->disk)->exists($thumbnailPath)) {
                Storage::disk($this->disk)->delete($thumbnailPath);
            }
        }

        // Supprimer l'enregistrement
        return $media->delete();
    }

    /**
     * Générer un nom de fichier unique
     */
    private function generateFilename(UploadedFile $file, string $prefix): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $timestamp = now()->format('Ymd_His');
        $random = Str::random(8);

        return "{$prefix}_{$timestamp}_{$random}.{$extension}";
    }
}
```

### Contrôleur Admin Media

```php
<?php
// app/Http/Controllers/Api/Admin/MediaController.php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\S3MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    public function __construct(
        private S3MediaService $mediaService
    ) {}

    /**
     * Upload un fichier média
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:51200', // 50MB max
            'type' => 'required|in:image,video',
        ]);

        $file = $request->file('file');
        $type = $request->input('type');

        // Validation selon le type
        if ($type === 'image') {
            $request->validate([
                'file' => 'mimes:jpg,jpeg,png,gif,webp|max:10240', // 10MB pour images
            ]);
            $media = $this->mediaService->uploadImage($file, auth()->id());
        } else {
            $request->validate([
                'file' => 'mimes:mp4,mov,avi,webm|max:51200', // 50MB pour vidéos
            ]);
            $media = $this->mediaService->uploadVideo($file, auth()->id());
        }

        return response()->json([
            'success' => true,
            'message' => 'Fichier uploadé avec succès',
            'data' => [
                'id' => $media->id,
                'url' => $media->url,
                'type' => $media->type,
                'size' => $media->formatted_size,
            ]
        ], 201);
    }

    /**
     * Supprimer un média
     */
    public function destroy(Media $media): JsonResponse
    {
        $this->mediaService->delete($media);

        return response()->json([
            'success' => true,
            'message' => 'Fichier supprimé'
        ]);
    }
}
```

### Structure des Dossiers S3

```
kkn-candles-media/
├── images/
│   ├── img_20240115_143022_abc12345.jpg
│   ├── img_20240115_143155_def67890.png
│   └── ...
├── videos/
│   ├── vid_20240115_150000_xyz11111.mp4
│   └── ...
└── thumbnails/
    └── vid_20240115_150000_xyz11111_thumb.jpg
```

---

## 9. Validation et Sécurité

### Form Requests

#### RegisterRequest

```php
<?php
// app/Http/Requests/Auth/RegisterRequest.php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email|unique:users,email|max:255',
            'password' => 'required|string|min:6|confirmed',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'L\'email est requis',
            'email.email' => 'L\'email doit être valide',
            'email.unique' => 'Cet email est déjà utilisé',
            'password.required' => 'Le mot de passe est requis',
            'password.min' => 'Le mot de passe doit contenir au moins 6 caractères',
            'password.confirmed' => 'Les mots de passe ne correspondent pas',
            'first_name.required' => 'Le prénom est requis',
            'last_name.required' => 'Le nom est requis',
            'phone.required' => 'Le téléphone est requis',
        ];
    }
}
```

#### LoginRequest

```php
<?php
// app/Http/Requests/Auth/LoginRequest.php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'L\'email est requis',
            'email.email' => 'L\'email doit être valide',
            'password.required' => 'Le mot de passe est requis',
        ];
    }
}
```

#### CreateOrderRequest

```php
<?php
// app/Http/Requests/Order/CreateOrderRequest.php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Informations client
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'notes' => 'nullable|string|max:1000',

            // Items
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|uuid|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'Le prénom est requis',
            'last_name.required' => 'Le nom est requis',
            'email.required' => 'L\'email est requis',
            'email.email' => 'L\'email doit être valide',
            'phone.required' => 'Le téléphone est requis',
            'address.required' => 'L\'adresse est requise',
            'city.required' => 'La ville est requise',
            'items.required' => 'La commande doit contenir au moins un article',
            'items.*.product_id.exists' => 'Un des produits n\'existe pas',
            'items.*.quantity.min' => 'La quantité minimum est de 1',
        ];
    }
}
```

### Politique de Sécurité

```php
<?php
// app/Http/Middleware/SecurityHeaders.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        return $response;
    }
}
```

### Rate Limiting

```php
<?php
// bootstrap/app.php - Ajout de rate limiting

->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'jwt.auth' => \App\Http\Middleware\JwtMiddleware::class,
        'admin' => \App\Http\Middleware\AdminMiddleware::class,
    ]);

    // Rate limiting global pour l'API
    $middleware->throttleApi('60:1'); // 60 requêtes par minute
})
```

```php
<?php
// routes/api.php - Rate limiting spécifique

// Auth - limitation plus stricte
Route::prefix('auth')->middleware('throttle:10,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Paiements - limitation stricte
Route::middleware(['jwt.auth', 'throttle:5,1'])->prefix('payments')->group(function () {
    Route::post('/cinetpay/init', [PaymentController::class, 'initCinetpay']);
});
```

---

## 10. Seeders et Données Initiales

### DatabaseSeeder

```php
<?php
// database/seeders/DatabaseSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
        ]);
    }
}
```

### AdminSeeder

```php
<?php
// database/seeders/AdminSeeder.php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'email' => 'admin@kkncandles.com',
            'password' => 'admin123', // Sera hashé automatiquement
            'first_name' => 'Admin',
            'last_name' => 'KKN',
            'phone' => '+226 70 00 00 00',
            'role' => 'admin',
            'city' => 'Ouagadougou',
            'email_verified_at' => now(),
        ]);
    }
}
```

### CategorySeeder

```php
<?php
// database/seeders/CategorySeeder.php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Bougies Parfumées',
                'slug' => 'bougies-parfumees',
                'description' => 'Nos bougies parfumées artisanales aux senteurs uniques',
                'sort_order' => 1,
            ],
            [
                'name' => 'Bougies Décoratives',
                'slug' => 'bougies-decoratives',
                'description' => 'Bougies décoratives pour embellir votre intérieur',
                'sort_order' => 2,
            ],
            [
                'name' => 'Éditions Limitées',
                'slug' => 'editions-limitees',
                'description' => 'Collections exclusives en édition limitée',
                'sort_order' => 3,
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
```

### ProductSeeder

```php
<?php
// database/seeders/ProductSeeder.php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

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
                'images' => ['/pictures/1.jpg'],
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
                'images' => ['/pictures/2.jpg'],
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
                'images' => ['/pictures/3.jpg'],
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
                'images' => ['/pictures/4.jpg'],
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
                'images' => ['/pictures/5.jpg'],
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
                'images' => ['/pictures/6.jpg'],
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
                'images' => ['/pictures/7.jpg'],
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
                'images' => ['/pictures/8.jpg'],
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
                'images' => ['/pictures/9.jpg'],
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
                'images' => ['/pictures/10.jpg'],
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
                'images' => ['/pictures/11.jpg'],
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
                'images' => ['/pictures/12.jpg'],
                'sort_order' => 12,
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
```

---

## 11. Commandes d'Installation

### Installation Complète

```bash
# 1. Créer le projet Laravel
composer create-project laravel/laravel kkn-candles-api
cd kkn-candles-api

# 2. Installer les dépendances
composer require php-open-source-saver/jwt-auth
composer require league/flysystem-aws-s3-v3 "^3.0"
composer require intervention/image # Optionnel pour optimisation images

# 3. Publier les configurations
php artisan vendor:publish --provider="PHPOpenSourceSaver\JWTAuth\Providers\LaravelServiceProvider"

# 4. Générer les clés
php artisan key:generate
php artisan jwt:secret

# 5. Configurer PostgreSQL dans .env (voir section 2)

# 6. Créer la base de données PostgreSQL
# psql -U postgres
# CREATE DATABASE kkn_candles_db;
# \q

# 7. Exécuter les migrations
php artisan migrate

# 8. Exécuter les seeders
php artisan db:seed

# 9. Créer le lien de stockage
php artisan storage:link

# 10. Lancer le serveur
php artisan serve --host=0.0.0.0 --port=8000
```

### Commandes Utiles

```bash
# Vider les caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Lister les routes
php artisan route:list --path=api

# Créer un contrôleur
php artisan make:controller Api/NomController --api

# Créer un modèle avec migration
php artisan make:model NomModele -m

# Créer une request
php artisan make:request Nom/NomRequest

# Créer une resource
php artisan make:resource NomResource

# Rafraîchir la base de données
php artisan migrate:fresh --seed

# Tests
php artisan test
```

### Structure de Réponse API Standard

```json
// Succès
{
    "success": true,
    "message": "Message descriptif",
    "data": { ... }
}

// Succès avec pagination
{
    "success": true,
    "data": {
        "items": [...],
        "meta": {
            "current_page": 1,
            "last_page": 5,
            "per_page": 12,
            "total": 60
        }
    }
}

// Erreur
{
    "success": false,
    "message": "Description de l'erreur",
    "errors": {
        "field": ["Message d'erreur"]
    }
}
```

---

## Résumé des Endpoints

| Méthode | Endpoint | Description | Auth |
|---------|----------|-------------|------|
| POST | `/api/auth/register` | Inscription | Non |
| POST | `/api/auth/login` | Connexion | Non |
| POST | `/api/auth/logout` | Déconnexion | JWT |
| POST | `/api/auth/refresh` | Rafraîchir token | Non |
| GET | `/api/auth/me` | Profil utilisateur | JWT |
| PUT | `/api/auth/profile` | Modifier profil | JWT |
| PUT | `/api/auth/password` | Changer mot de passe | JWT |
| GET | `/api/products` | Liste produits | Non |
| GET | `/api/products/featured` | Produits vedettes | Non |
| GET | `/api/products/search` | Recherche | Non |
| GET | `/api/products/{id}` | Détail produit | Non |
| GET | `/api/categories` | Liste catégories | Non |
| GET | `/api/categories/{id}/products` | Produits par catégorie | Non |
| GET | `/api/cart` | Voir panier | JWT |
| POST | `/api/cart/items` | Ajouter au panier | JWT |
| PUT | `/api/cart/items/{id}` | Modifier quantité | JWT |
| DELETE | `/api/cart/items/{id}` | Supprimer item | JWT |
| DELETE | `/api/cart` | Vider panier | JWT |
| GET | `/api/orders` | Mes commandes | JWT |
| POST | `/api/orders` | Créer commande | JWT |
| GET | `/api/orders/{id}` | Détail commande | JWT |
| POST | `/api/payments/cinetpay/init` | Initier paiement | JWT |
| POST | `/api/payments/cinetpay/notify` | Webhook CinetPay | Non |
| POST | `/api/contact` | Envoyer message | Non |
| GET | `/api/admin/dashboard` | Stats dashboard | Admin |
| GET | `/api/admin/orders` | Toutes commandes | Admin |
| PUT | `/api/admin/orders/{id}/status` | Changer statut | Admin |
| POST | `/api/admin/products` | Créer produit | Admin |
| PUT | `/api/admin/products/{id}` | Modifier produit | Admin |
| DELETE | `/api/admin/products/{id}` | Supprimer produit | Admin |
| POST | `/api/admin/media/upload` | Upload média | Admin |
| DELETE | `/api/admin/media/{id}` | Supprimer média | Admin |

---

## Notes Importantes

1. **Sécurité**: Toujours valider les données côté serveur, même si le frontend valide déjà.

2. **Frais de livraison**: Actuellement fixés à 2000 FCFA. Peut être rendu dynamique selon la ville/zone.

3. **Gestion des stocks**: Implémenter un système de réservation de stock lors de la création de commande pour éviter les surventes.

4. **Emails**: Configurer un service d'email (Mailgun, SendGrid, etc.) pour les confirmations de commande.

5. **Logs**: Toujours logger les transactions de paiement pour le débogage.

6. **Tests**: Écrire des tests pour les endpoints critiques (auth, orders, payments).

7. **CORS**: S'assurer que le frontend Next.js peut communiquer avec l'API.

8. **Images produits**: Migrer les images existantes du frontend vers S3 et mettre à jour les URLs dans la base de données.
