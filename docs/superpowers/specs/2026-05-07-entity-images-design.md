# Entity Images Feature Design

**Date:** 2026-05-07  
**Feature:** Display entity images (persons, planets, films, starships, vehicles, species) on entity cards with clickable modal  
**Approach:** Pexels API for image search, hybrid URL caching (DB + lazy-load)

---

## Overview

Add image thumbnails (60x60px) to entity cards. Click thumbnail to open full image in modal. Images sourced via Pexels API. Image URLs cached in database for performance. No image found = thumbnail hidden.

---

## Architecture

### Data Flow

1. **Entity Created/Updated** → Laravel event triggers
2. **FetchEntityImages Job** dispatched (async)
3. **ImageFetcher Service** queries Pexels API with entity name
4. **Result stored** in `entity_images` table (URL + metadata)
5. **Frontend loads** entity with image URL
6. **Vue component** lazy-loads thumbnail on card
7. **Click** opens modal with full image

### Database Schema

New table: `entity_images`

```sql
CREATE TABLE entity_images (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    entity_type VARCHAR(50) NOT NULL,      -- 'Person', 'Planet', 'Film', 'Starship', 'Vehicle', 'Species'
    entity_id BIGINT UNSIGNED NOT NULL,
    image_url TEXT NULLABLE,               -- Full Pexels image URL or null if not found
    source VARCHAR(50),                    -- 'pexels'
    fetched_at TIMESTAMP NULLABLE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_entity_image (entity_type, entity_id),
    FOREIGN KEY (entity_id) REFERENCES entities(id) ON DELETE CASCADE
);
```

Index: `entity_type`, `entity_id` for fast lookups.

---

## Backend Implementation

### ImageFetcher Service

**Location:** `app/Services/ImageFetcher.php`

```php
class ImageFetcher {
    public function fetchForEntity(string $entityType, int $entityId, string $name): ?string
    public function searchPexels(string $query): ?string
}
```

**Methods:**

- `fetchForEntity($type, $id, $name)` — Main entry point. Searches Pexels with entity name. Returns image URL or null.
- `searchPexels($query)` — Calls Pexels API free tier. Returns first result's image URL or null.

**Pexels Integration:**
- API Key stored in `.env` as `PEXELS_API_KEY`
- Endpoint: `https://api.pexels.com/v1/search?query={query}&per_page=1`
- Free tier: 200 requests/hour, no image attribution required in UI
- Retry logic: 3 attempts with exponential backoff on network errors

### FetchEntityImages Job

**Location:** `app/Jobs/FetchEntityImages.php`

Dispatched when entity is created or updated:
- Input: `$entityType`, `$entityId`, `$entityData` (name, etc.)
- Calls `ImageFetcher::fetchForEntity()`
- Creates/updates `entity_images` record with result
- Logs failures without throwing (non-blocking)

### Event Listener

**Location:** `app/Listeners/DispatchImageFetch.php`

Listen to entity creation/update events:
- Models: Person, Planet, Film, Starship, Vehicle, Species
- Dispatch `FetchEntityImages` job

---

## Frontend Implementation

### Card Component Changes

Update all entity card components:
- `PersonCard.vue`
- `PlanetCard.vue`
- `FilmCard.vue`
- `StarshipCard.vue`
- `VehicleCard.vue`
- `SpeciesCard.vue`

**Changes:**
1. Add image thumbnail container in card header (top-right corner)
2. Bind image URL to `entity.image_url` from API response
3. Show thumbnail only if URL exists (v-if)
4. Bind click to emit event or call modal opener
5. Style: 60x60px, rounded corners, subtle border, hover effect

**Example snippet:**
```vue
<div v-if="item.image_url" class="entity-image-thumbnail" @click="openImageModal">
  <img :src="item.image_url" :alt="item.name" />
</div>
```

**Styling:**
```css
.entity-image-thumbnail {
  position: absolute;
  top: 12px;
  right: 12px;
  width: 60px;
  height: 60px;
  border-radius: 6px;
  border: 1px solid rgba(255, 215, 0, 0.3);
  overflow: hidden;
  cursor: pointer;
  transition: opacity 0.2s, transform 0.2s;
}

.entity-image-thumbnail:hover {
  opacity: 0.8;
  transform: scale(1.05);
}

.entity-image-thumbnail img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
```

### ImageModal Component

**Location:** `resources/js/components/ImageModal.vue`

Props:
- `imageUrl` — Full image URL
- `entityName` — Name of entity (for alt text and header)
- `source` — Image source attribution

Features:
- Centered modal overlay with semi-transparent backdrop
- Full image displayed (responsive, max-width: 90vw, max-height: 90vh)
- Close button (X in top-right)
- Click outside to close
- Attribution text (small, bottom-left: "From Pexels")

**Styling:**
- Dark overlay (rgba(0, 0, 0, 0.8))
- Image container with padding
- Mobile responsive (full-width on small screens)

### Lazy Loading

Images lazy-loaded on demand (click thumbnail → fetch full URL if not cached):
- Vue component checks if `entity.image_url` exists
- If missing, fetch via `/api/entities/{type}/{id}/image` endpoint
- Cache result in component state for session

---

## API Endpoint

### GET `/api/entities/{type}/{id}/image`

**Response:**
```json
{
  "image_url": "https://images.pexels.com/...",
  "source": "pexels",
  "entity_name": "Luke Skywalker",
  "cached": true
}
```

**Logic:**
- Query `entity_images` table
- If found, return cached URL
- If not found or missing URL, trigger `FetchEntityImages` job and return null (frontend shows nothing)
- Cache-Control header: `max-age=3600` (1 hour)

---

## Error Handling

- **Pexels API failure:** Log error, store null in `image_url`, thumbnail hidden (graceful degradation)
- **Network timeout:** Retry up to 3 times with exponential backoff
- **Broken image URL:** Frontend shows nothing (no broken image icon)
- **Rate limit (200/hour):** Queue jobs with delays to respect limit
- **Missing entity:** Job skips gracefully

---

## Directory Structure

No new physical directories. URLs only:

```
public/images/
├── entities/          (logical: no physical storage, just URL references)
├── placeholders/      (optional: future default entity type icons)
└── icons/             (existing)

app/
├── Services/
│   └── ImageFetcher.php         (new)
├── Jobs/
│   └── FetchEntityImages.php    (new)
└── Listeners/
    └── DispatchImageFetch.php   (new)

resources/js/
├── components/
│   └── ImageModal.vue           (new)
└── services/
    └── ImageService.js          (optional helper)
```

---

## Configuration

**.env additions:**
```
PEXELS_API_KEY=your_key_here
```

Obtain free key from https://www.pexels.com/api/

---

## Testing

- Unit: ImageFetcher methods (mock Pexels API)
- Feature: Entity image stored/retrieved correctly
- UI: Thumbnail displays, modal opens/closes, hidden if no URL

---

## Success Criteria

- ✓ Pexels API integrated
- ✓ Image URLs cached in DB
- ✓ Thumbnails display on all entity card types
- ✓ Modal opens on click, shows full image
- ✓ No image = thumbnail hidden (blank)
- ✓ Error handling graceful (no broken images, no exceptions)
- ✓ Performance: thumbnails lazy-load, no blocking

---

## Future Enhancements

- Multiple images per entity (carousel in modal)
- User-provided custom images
- Image search refinement (entity type + name for better results)
- Batch image seeding from SWAPI or other sources
