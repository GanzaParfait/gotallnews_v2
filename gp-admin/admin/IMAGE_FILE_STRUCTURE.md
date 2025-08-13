# Image File Structure and Storage

## 📁 Where Compressed Images Are Stored

All compressed images are stored in the same directory: **`gp-admin/admin/images/uploaded/`**

## 🏷️ Naming Convention

When you upload an image, the system generates multiple compressed versions with this naming pattern:

```
{timestamp}_gpnews_{original_filename}_{size}.webp
```

### Example:
If you upload `my_article_image.jpg` at timestamp `1736960000`, the system creates:

| Size | Filename | Description |
|------|----------|-------------|
| **Large** | `1736960000_gpnews_my_article_image_large.webp` | 1200×800px, 80% quality |
| **Medium** | `1736960000_gpnews_my_article_image_medium.webp` | 800×600px, 75% quality |
| **Small** | `1736960000_gpnews_my_article_image_small.webp` | 400×300px, 70% quality |
| **Thumbnail** | `1736960000_gpnews_my_article_image_thumbnail.webp` | 150×150px, 65% quality |

## 🗄️ Database Storage

The database stores the filenames in these columns:

| Column | Contains | Example |
|--------|----------|---------|
| `Image` | Main image (usually large size) | `1736960000_gpnews_my_article_image_large.webp` |
| `image_large` | Large size filename | `1736960000_gpnews_my_article_image_large.webp` |
| `image_medium` | Medium size filename | `1736960000_gpnews_my_article_image_medium.webp` |
| `image_small` | Small size filename | `1736960000_gpnews_my_article_image_small.webp` |
| `image_thumbnail` | Thumbnail filename | `1736960000_gpnews_my_article_image_thumbnail.webp` |

## 📊 File Size Comparison

Here's what you can expect in terms of file sizes:

| Original Image | Large | Medium | Small | Thumbnail | Total Savings |
|----------------|-------|--------|-------|-----------|---------------|
| 2.5MB JPG | ~400KB | ~200KB | ~80KB | ~30KB | **~85% smaller** |
| 1.8MB PNG | ~300KB | ~150KB | ~60KB | ~25KB | **~80% smaller** |
| 900KB GIF | ~200KB | ~100KB | ~40KB | ~15KB | **~75% smaller** |

## 🔍 How to Find Your Images

### 1. **In File System:**
Navigate to: `gp-admin/admin/images/uploaded/`
Look for files with your timestamp and the `_large`, `_medium`, `_small`, `_thumbnail` suffixes.

### 2. **In Database:**
```sql
SELECT ArticleID, Title, Image, image_large, image_medium, image_small, image_thumbnail 
FROM article 
WHERE Image LIKE '%your_filename%';
```

### 3. **Using the Test Script:**
Run `test_image_retrieval.php` and enter your Article ID to see all image versions.

## 🚀 Benefits of This Structure

1. **Easy to Find**: All images for one article have the same timestamp prefix
2. **Size Identification**: Clear naming shows which size each file is
3. **WebP Format**: All compressed images are in WebP format for maximum compression
4. **Organized**: No separate folders needed - everything in one place
5. **Backup Friendly**: Easy to backup entire `uploaded` folder

## ⚠️ Important Notes

- **Original files are NOT kept** - only the compressed WebP versions
- **All images are automatically converted** to WebP format
- **File sizes are significantly reduced** (typically 70-90% smaller)
- **Quality is optimized** for each size based on intended use
- **Aspect ratios are preserved** during resizing

## 🧹 Cleanup

If you need to remove images for an article:
1. Delete the files from `images/uploaded/` folder
2. Update the database to clear the image fields
3. The system will automatically clean up if database operations fail

## 📱 Frontend Usage

When displaying images on your website, you can choose the appropriate size:

```php
// For featured articles (use large)
$image = $article['image_large'];

// For article lists (use medium)
$image = $article['image_medium'];

// For sidebars (use small)
$image = $article['image_small'];

// For thumbnails (use thumbnail)
$image = $article['image_thumbnail'];
```

This structure ensures your website loads fast while maintaining image quality appropriate for each context!
