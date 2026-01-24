# Guide d'intégration des médias - Frontend

Ce document décrit comment le frontend doit consommer et afficher les médias (images et vidéos) stockés sur Amazon S3.

## Table des matières

1. [Structure des données](#structure-des-données)
2. [Endpoints API](#endpoints-api)
3. [Règles d'affichage](#règles-daffichage)
4. [Bonnes pratiques](#bonnes-pratiques)
5. [Exemples de composants](#exemples-de-composants)
6. [Gestion des erreurs](#gestion-des-erreurs)

---

## Structure des données

### Schéma JSON d'un média

```json
{
  "id": "550e8400-e29b-41d4-a716-446655440000",
  "filename": "img_20240115_143022_a1b2c3_XyZ12345.jpg",
  "original_filename": "produit-bougie.jpg",
  "path": "images/img_20240115_143022_a1b2c3_XyZ12345.jpg",
  "url": "https://kkn-candles-media.s3.eu-north-1.amazonaws.com/images/img_20240115_143022_a1b2c3_XyZ12345.jpg",
  "disk": "s3",
  "type": "image",
  "mime_type": "image/jpeg",
  "size": 245678,
  "width": 1920,
  "height": 1080,
  "thumbnail_url": null,
  "formatted_size": "239.92 KB",
  "created_at": "2024-01-15T14:30:22.000000Z",
  "updated_at": "2024-01-15T14:30:22.000000Z"
}
```

### Description des champs

| Champ | Type | Description |
|-------|------|-------------|
| `id` | UUID | Identifiant unique du média |
| `filename` | string | Nom du fichier généré (unique) |
| `original_filename` | string | Nom original du fichier uploadé |
| `path` | string | Chemin relatif sur S3 |
| `url` | string | **URL complète S3** (utilisez ce champ pour l'affichage) |
| `disk` | string | Disque de stockage (`s3`) |
| `type` | enum | Type de média: `image` ou `video` |
| `mime_type` | string | Type MIME (ex: `image/jpeg`, `video/mp4`) |
| `size` | integer | Taille en octets |
| `width` | integer\|null | Largeur en pixels (images uniquement) |
| `height` | integer\|null | Hauteur en pixels (images uniquement) |
| `thumbnail_url` | string\|null | URL de la miniature (si disponible) |
| `formatted_size` | string | Taille formatée (ex: "2.5 MB") |

### Types MIME supportés

**Images:**
- `image/jpeg` (.jpg, .jpeg)
- `image/png` (.png)
- `image/gif` (.gif)
- `image/webp` (.webp)
- `image/bmp` (.bmp)
- `image/svg+xml` (.svg)

**Vidéos:**
- `video/mp4` (.mp4)
- `video/quicktime` (.mov)
- `video/x-msvideo` (.avi)
- `video/webm` (.webm)
- `video/x-matroska` (.mkv)
- `video/x-flv` (.flv)
- `video/x-ms-wmv` (.wmv)

---

## Endpoints API

### Lister les médias

```http
GET /api/admin/media
```

**Query Parameters:**
| Paramètre | Type | Description |
|-----------|------|-------------|
| `type` | string | Filtrer par type: `image` ou `video` |
| `page` | integer | Numéro de page |
| `per_page` | integer | Nombre d'éléments par page (défaut: 20) |

**Réponse:**
```json
{
  "data": [
    {
      "id": "...",
      "url": "https://...",
      "type": "image",
      ...
    }
  ],
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": "..."
  },
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 100
  }
}
```

### Upload d'un média

```http
POST /api/admin/media/upload
Content-Type: multipart/form-data
```

**Body:**
| Champ | Type | Requis | Contraintes |
|-------|------|--------|-------------|
| `file` | file | Oui | Images: max 10 MB, Vidéos: max 50 MB |
| `type` | string | Oui | `image` ou `video` |

### Supprimer un média

```http
DELETE /api/admin/media/{id}
```

---

## Règles d'affichage

### Distinguer image et vidéo

```typescript
interface Media {
  id: string;
  url: string;
  type: 'image' | 'video';
  mime_type: string;
  width?: number;
  height?: number;
}

function isImage(media: Media): boolean {
  return media.type === 'image';
}

function isVideo(media: Media): boolean {
  return media.type === 'video';
}
```

### Balises HTML recommandées

**Pour les images:**
```html
<img
  src="{media.url}"
  alt="{media.original_filename}"
  width="{media.width}"
  height="{media.height}"
  loading="lazy"
  decoding="async"
/>
```

**Pour les vidéos:**
```html
<video
  src="{media.url}"
  controls
  preload="metadata"
  playsinline
>
  <source src="{media.url}" type="{media.mime_type}" />
  Votre navigateur ne supporte pas la lecture vidéo.
</video>
```

### Attributs essentiels

| Attribut | Usage | Description |
|----------|-------|-------------|
| `loading="lazy"` | Images | Chargement différé hors viewport |
| `decoding="async"` | Images | Décodage asynchrone |
| `width` / `height` | Images | Évite le layout shift (CLS) |
| `controls` | Vidéos | Affiche les contrôles de lecture |
| `preload="metadata"` | Vidéos | Charge uniquement les métadonnées |
| `playsinline` | Vidéos | Lecture inline sur mobile |

---

## Bonnes pratiques

### 1. Cache navigateur / CDN

Les URLs S3 sont servies avec les headers de cache appropriés. Pour optimiser:

```typescript
// Ajoutez un query param de version si vous gérez le cache-busting
const imageUrl = `${media.url}?v=${media.updated_at}`;
```

**Configuration recommandée côté CDN (CloudFront):**
- TTL images: 1 an (les URLs sont uniques)
- TTL vidéos: 1 an
- Compression: gzip/brotli pour les métadonnées

### 2. Sécurité

- **Ne jamais stocker localement** les fichiers médias côté frontend
- **Toujours utiliser HTTPS** (les URLs S3 sont déjà en HTTPS)
- **Valider les types MIME** avant affichage
- **Sanitizer les noms de fichiers** dans l'UI

```typescript
// Validation du type MIME
const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
const ALLOWED_VIDEO_TYPES = ['video/mp4', 'video/webm', 'video/quicktime'];

function isValidMedia(media: Media): boolean {
  if (media.type === 'image') {
    return ALLOWED_IMAGE_TYPES.includes(media.mime_type);
  }
  return ALLOWED_VIDEO_TYPES.includes(media.mime_type);
}
```

### 3. Responsive design

```css
/* Images responsives */
.media-image {
  max-width: 100%;
  height: auto;
  object-fit: cover;
}

/* Vidéos responsives */
.media-video {
  width: 100%;
  max-width: 100%;
  height: auto;
  aspect-ratio: 16 / 9;
}

/* Container avec ratio préservé */
.media-container {
  position: relative;
  width: 100%;
  padding-bottom: 56.25%; /* 16:9 */
  overflow: hidden;
}

.media-container > img,
.media-container > video {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}
```

### 4. Lazy loading avancé

```typescript
// Intersection Observer pour lazy loading personnalisé
const lazyLoadMedia = () => {
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        const media = entry.target as HTMLImageElement | HTMLVideoElement;
        media.src = media.dataset.src!;
        observer.unobserve(media);
      }
    });
  }, {
    rootMargin: '100px', // Préchargement 100px avant le viewport
  });

  document.querySelectorAll('[data-src]').forEach((el) => {
    observer.observe(el);
  });
};
```

---

## Exemples de composants

### React - Composant MediaRenderer

```tsx
import React, { useState } from 'react';

interface Media {
  id: string;
  url: string;
  type: 'image' | 'video';
  mime_type: string;
  original_filename: string;
  width?: number;
  height?: number;
}

interface MediaRendererProps {
  media: Media;
  className?: string;
  onError?: () => void;
}

const MediaRenderer: React.FC<MediaRendererProps> = ({
  media,
  className = '',
  onError
}) => {
  const [hasError, setHasError] = useState(false);
  const [isLoading, setIsLoading] = useState(true);

  const handleError = () => {
    setHasError(true);
    setIsLoading(false);
    onError?.();
  };

  const handleLoad = () => {
    setIsLoading(false);
  };

  if (hasError) {
    return (
      <div className={`media-fallback ${className}`}>
        <span>Média non disponible</span>
      </div>
    );
  }

  if (media.type === 'image') {
    return (
      <div className={`media-wrapper ${className}`}>
        {isLoading && <div className="media-skeleton" />}
        <img
          src={media.url}
          alt={media.original_filename}
          width={media.width}
          height={media.height}
          loading="lazy"
          decoding="async"
          onError={handleError}
          onLoad={handleLoad}
          style={{ display: isLoading ? 'none' : 'block' }}
        />
      </div>
    );
  }

  return (
    <div className={`media-wrapper ${className}`}>
      <video
        src={media.url}
        controls
        preload="metadata"
        playsInline
        onError={handleError}
        onLoadedMetadata={handleLoad}
      >
        <source src={media.url} type={media.mime_type} />
        Votre navigateur ne supporte pas la lecture vidéo.
      </video>
    </div>
  );
};

export default MediaRenderer;
```

### React - Composant Galerie

```tsx
import React, { useState } from 'react';
import MediaRenderer from './MediaRenderer';

interface GalleryProps {
  media: Media[];
}

const MediaGallery: React.FC<GalleryProps> = ({ media }) => {
  const [selectedIndex, setSelectedIndex] = useState(0);
  const [isModalOpen, setIsModalOpen] = useState(false);

  const images = media.filter((m) => m.type === 'image');
  const videos = media.filter((m) => m.type === 'video');

  return (
    <div className="gallery">
      {/* Média principal */}
      <div className="gallery-main">
        <MediaRenderer
          media={media[selectedIndex]}
          className="gallery-featured"
        />
      </div>

      {/* Thumbnails */}
      <div className="gallery-thumbnails">
        {media.map((item, index) => (
          <button
            key={item.id}
            className={`thumbnail ${index === selectedIndex ? 'active' : ''}`}
            onClick={() => setSelectedIndex(index)}
          >
            {item.type === 'image' ? (
              <img src={item.url} alt="" loading="lazy" />
            ) : (
              <div className="video-thumbnail">
                <span className="play-icon">▶</span>
              </div>
            )}
          </button>
        ))}
      </div>

      {/* Modal plein écran */}
      {isModalOpen && (
        <div className="gallery-modal" onClick={() => setIsModalOpen(false)}>
          <MediaRenderer media={media[selectedIndex]} className="modal-media" />
        </div>
      )}
    </div>
  );
};

export default MediaGallery;
```

### Vue 3 - Composant MediaRenderer

```vue
<template>
  <div :class="['media-wrapper', className]">
    <!-- Loading state -->
    <div v-if="isLoading" class="media-skeleton" />

    <!-- Error state -->
    <div v-else-if="hasError" class="media-fallback">
      <span>Média non disponible</span>
    </div>

    <!-- Image -->
    <img
      v-else-if="media.type === 'image'"
      :src="media.url"
      :alt="media.original_filename"
      :width="media.width"
      :height="media.height"
      loading="lazy"
      decoding="async"
      @error="handleError"
      @load="handleLoad"
    />

    <!-- Video -->
    <video
      v-else
      :src="media.url"
      controls
      preload="metadata"
      playsinline
      @error="handleError"
      @loadedmetadata="handleLoad"
    >
      <source :src="media.url" :type="media.mime_type" />
      Votre navigateur ne supporte pas la lecture vidéo.
    </video>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';

interface Media {
  id: string;
  url: string;
  type: 'image' | 'video';
  mime_type: string;
  original_filename: string;
  width?: number;
  height?: number;
}

interface Props {
  media: Media;
  className?: string;
}

const props = withDefaults(defineProps<Props>(), {
  className: '',
});

const emit = defineEmits<{
  error: [];
}>();

const isLoading = ref(true);
const hasError = ref(false);

const handleError = () => {
  hasError.value = true;
  isLoading.value = false;
  emit('error');
};

const handleLoad = () => {
  isLoading.value = false;
};
</script>

<style scoped>
.media-wrapper {
  position: relative;
  width: 100%;
}

.media-skeleton {
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  width: 100%;
  aspect-ratio: 16 / 9;
}

@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

.media-fallback {
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f5f5f5;
  color: #999;
  padding: 2rem;
  border-radius: 8px;
}

img, video {
  max-width: 100%;
  height: auto;
}
</style>
```

---

## Gestion des erreurs

### Codes d'erreur API

| Code | Description | Action recommandée |
|------|-------------|-------------------|
| 400 | Fichier invalide ou type non supporté | Afficher message d'erreur |
| 401 | Non authentifié | Rediriger vers login |
| 403 | Non autorisé | Afficher message d'accès refusé |
| 404 | Média non trouvé | Afficher image placeholder |
| 413 | Fichier trop volumineux | Afficher limite de taille |
| 422 | Validation échouée | Afficher erreurs de validation |
| 500 | Erreur serveur | Retry ou contacter support |

### Fallback pour images cassées

```typescript
const FALLBACK_IMAGE = '/images/placeholder.png';

function handleImageError(event: Event) {
  const img = event.target as HTMLImageElement;
  if (img.src !== FALLBACK_IMAGE) {
    img.src = FALLBACK_IMAGE;
  }
}
```

### Retry automatique

```typescript
async function fetchMediaWithRetry(
  url: string,
  maxRetries = 3,
  delay = 1000
): Promise<Response> {
  for (let i = 0; i < maxRetries; i++) {
    try {
      const response = await fetch(url);
      if (response.ok) return response;
    } catch (error) {
      if (i === maxRetries - 1) throw error;
      await new Promise((resolve) => setTimeout(resolve, delay * (i + 1)));
    }
  }
  throw new Error('Max retries reached');
}
```

---

## Cas d'usage courants

### 1. Page produit avec galerie

```tsx
// Récupération des données produit
const product = await api.get(`/products/${slug}`);

// Les images sont dans product.images (tableau d'URLs S3)
<ProductGallery images={product.images} />
```

### 2. Upload de média (admin)

```typescript
async function uploadMedia(file: File, type: 'image' | 'video') {
  const formData = new FormData();
  formData.append('file', file);
  formData.append('type', type);

  const response = await fetch('/api/admin/media/upload', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
    },
    body: formData,
  });

  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message);
  }

  return response.json();
}
```

### 3. Preview avant upload

```typescript
function previewFile(file: File): Promise<string> {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => resolve(reader.result as string);
    reader.onerror = reject;
    reader.readAsDataURL(file);
  });
}
```

---

## Support

Pour toute question concernant l'intégration des médias, contactez l'équipe backend ou consultez la documentation API Swagger disponible à `/api/documentation`.
