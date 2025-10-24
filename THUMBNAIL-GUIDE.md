# Thumbnail Generation Guide - LightBlog CMS

Complete guide to generating featured images for blog posts using AI and free stock photos.

---

## Overview

**3 Image Generation Options:**

1. **Unsplash** (FREE) - High-quality stock photos
2. **OpenAI DALL-E 3** ($0.04/image) - Unique AI-generated images
3. **PHP GD** (FREE) - Gradient backgrounds with text overlay (fallback)

**Recommended:** Start with Unsplash (free) → Upgrade to OpenAI for unique images later

---

## Option 1: Unsplash (FREE - Recommended)

### Overview

- **Cost:** FREE (500 requests/hour)
- **Quality:** Professional stock photography
- **Best For:** Budget-conscious sites, stock photos acceptable
- **SEO:** Excellent (real images, Google recognizes content)

### Setup Unsplash

1. Go to https://unsplash.com/developers
2. Click "Register as a developer"
3. Create a new application:
   - **Application name:** LightBlog CMS
   - **Description:** Blog post thumbnail generation
4. Accept terms and click "Create application"
5. Copy your **Access Key** (starts with `Client-ID ...`)
6. Add to LightBlog:
   - **Method A (Admin UI):** Settings → AI Providers → Unsplash API Key
   - **Method B (config.php):** `define('UNSPLASH_API_KEY', 'your-key-here');`

### How It Works

**Automatic Keyword Extraction:**
```
Post Title: "How to Build a REST API with Node.js"
↓
Extracted Query: "Build REST API Node"
↓
Unsplash Search: High-quality developer/programming photos
↓
Downloaded & Saved: /content/uploads/unsplash-[timestamp].jpg
```

**Attribution:**
- Unsplash requires attribution (handled automatically)
- Stored in database media table
- Example: "Photo by John Doe on Unsplash"

### Pros & Cons

✅ **Pros:**
- Completely FREE
- Professional photography
- Fast generation (no AI processing)
- Excellent SEO value
- No billing required

❌ **Cons:**
- Stock photos (not unique)
- May not perfectly match niche topics
- 500 requests/hour limit

---

## Option 2: OpenAI DALL-E 3 (Premium)

### Overview

- **Cost:** $0.04 per image (standard), $0.08 (HD)
- **Quality:** Unique AI-generated images
- **Best For:** Premium sites, unique visuals needed
- **SEO:** Excellent (unique content, Google recognizes subjects)

### Setup OpenAI

See **SETUP-OPENAI.md** for complete guide.

**Quick Setup:**
1. Create account: https://platform.openai.com/signup
2. Add payment method (minimum $5)
3. Generate API key: https://platform.openai.com/api-keys
4. Add to LightBlog: Settings → AI Providers → OpenAI API Key
5. Select: Image AI Provider → OpenAI

### How It Works

**AI Image Generation:**
```
Post Title: "Mountain Hiking Safety Tips"
↓
AI Prompt: "mountain hiking safety, outdoor adventure, professional photography"
↓
DALL-E 3 Generation: ~15 seconds
↓
Unique Image: Mountain hiking scene with safety elements
```

### Pros & Cons

✅ **Pros:**
- Unique images (100% original)
- Perfect topic match
- Customizable styles
- Premium quality

❌ **Cons:**
- Costs $0.04 per image
- Slower generation (15-20 seconds)
- Requires billing setup
- Rate limits (50 requests/minute)

---

## Option 3: PHP GD (Free Fallback)

### Overview

- **Cost:** FREE
- **Quality:** Basic gradient + text
- **Best For:** Fallback when APIs unavailable
- **SEO:** Limited (text overlay, no subject recognition)

### How It Works

**Gradient Generation:**
```
Post Title: "Machine Learning Basics"
↓
Generate Gradient: Blue → Purple
↓
Add Text Overlay: "Machine Learning Basics"
↓
Save: /content/uploads/thumb-[timestamp].jpg
```

### Pros & Cons

✅ **Pros:**
- Completely FREE
- No API keys needed
- Fast generation
- Always works

❌ **Cons:**
- Basic appearance
- Limited SEO value
- Text overlay (not pure image)
- Less professional

---

## Generating Thumbnails

### Method 1: Individual Posts (Admin UI)

**For Single Post:**

1. Go to **Admin → Posts → Edit Post**
2. Find **Featured Image / Thumbnail** section
3. Click **"Auto Generate Thumbnail"** button
4. Wait 5-15 seconds (depending on provider)
5. Image appears in preview
6. Click **Save Post**

**Provider Selection:**
- System uses provider selected in Settings → Image AI Provider
- Auto-detect: Unsplash → OpenAI → PHP GD

### Method 2: Bulk Operations (Multiple Posts)

**For Multiple Posts:**

1. Go to **Admin → Posts → All Posts**
2. Select posts without thumbnails (checkboxes)
3. Choose **Bulk Actions → Auto Generate Thumbnails**
4. Click **Apply**
5. Wait for processing (progress shown)
6. Reload page to see new thumbnails

**Example:**
- 20 posts selected
- Provider: Unsplash (FREE)
- Time: ~1 minute
- Cost: $0.00

**With OpenAI:**
- 20 posts selected
- Provider: OpenAI DALL-E 3
- Time: ~5 minutes
- Cost: $0.80 (20 × $0.04)

### Method 3: Automatic (New Posts)

**When Creating New Post:**

1. Go to **Admin → Posts → Add New**
2. Enter title
3. Check **"Auto-generate thumbnail"** checkbox (if available)
4. Click **Publish**
5. Thumbnail generates automatically

---

## Provider Comparison

| Feature | Unsplash | OpenAI DALL-E 3 | PHP GD |
|---------|----------|-----------------|--------|
| **Cost** | FREE | $0.04/image | FREE |
| **Speed** | Fast (5s) | Medium (15s) | Very Fast (2s) |
| **Quality** | High | Highest | Basic |
| **Uniqueness** | Stock | Unique | Generic |
| **SEO Value** | Excellent | Excellent | Limited |
| **Setup** | API Key | API Key + Billing | None |
| **Rate Limit** | 500/hour | 50/minute | Unlimited |
| **Best For** | General blogs | Premium sites | Fallback |

---

## Provider Selection Strategy

### Budget-Conscious Sites

**Recommended Setup:**
- **Image Provider:** Unsplash (FREE)
- **Content Provider:** Gemini (nearly FREE)
- **Total Cost per Post:** ~$0.00

**Configuration:**
```
Settings → AI Providers:
- Unsplash API Key: [your-key]
- Image AI Provider: Unsplash
- Content AI Provider: Gemini
```

### Premium Sites

**Recommended Setup:**
- **Image Provider:** OpenAI DALL-E 3 ($0.04)
- **Content Provider:** GPT-4 ($0.03)
- **Total Cost per Post:** $0.07

**Configuration:**
```
Settings → AI Providers:
- OpenAI API Key: [your-key]
- Image AI Provider: OpenAI
- Content AI Provider: OpenAI
```

### Hybrid Approach

**Recommended Setup:**
- **Images:** Unsplash (FREE) for most posts
- **Content:** GPT-4 ($0.03) for quality
- **Special Posts:** Manually switch to OpenAI for hero images
- **Average Cost per Post:** $0.03

**Configuration:**
```
Settings → AI Providers:
- Unsplash API Key: [your-key]
- OpenAI API Key: [your-key]
- Image AI Provider: Unsplash (default)
- Content AI Provider: OpenAI
```

When you need unique image:
1. Edit specific post
2. Manually select OpenAI for that image
3. Publish

---

## SEO Optimization

### Why Real Images Matter

**Google Image Recognition:**
- Recognizes subjects (mountains, office, food, etc.)
- Indexes for Google Image Search
- Improves organic traffic
- Better social media sharing

**Example:**

**Post:** "Best Coffee Brewing Methods"

**With Unsplash/OpenAI:**
- Image shows: coffee, espresso machine, barista
- Google recognizes: coffee, brewing, beverage, cafe
- Ranks in: "coffee brewing", "espresso", "barista setup"
- Social CTR: High

**With PHP GD:**
- Image shows: gradient + text
- Google recognizes: text overlay
- Ranks in: Limited image search
- Social CTR: Average

### Best Practices

1. ✅ **Use descriptive post titles**
   - Good: "Mediterranean Diet Meal Planning Guide"
   - Bad: "Meal Planning"

2. ✅ **Select appropriate provider**
   - Unsplash: Great for common topics (travel, food, tech)
   - OpenAI: Better for niche/abstract concepts

3. ✅ **Optimize image size**
   - Recommended: 1200x630px (OG image standard)
   - Works for: Facebook, Twitter, LinkedIn sharing

4. ✅ **Use ALT text**
   - Auto-generated from title
   - Helps SEO and accessibility

5. ✅ **Consistent quality**
   - Stick to one provider for brand consistency
   - Mix only when necessary

---

## Customization

### Image Styles (OpenAI Only)

Modify prompts in `core/AI/ImageGenerator.php`:

```php
public function generateThumbnail($title, $options = []) {
    $style = $options['style'] ?? 'professional';

    $stylePrompts = [
        'professional' => 'professional, clean, modern, high-quality photography',
        'artistic' => 'artistic, creative, vibrant colors, unique composition',
        'minimalist' => 'minimalist, simple, clean lines, neutral colors',
        'photorealistic' => 'photorealistic, detailed, natural lighting, sharp focus',
        'illustrated' => 'digital illustration, vector art, clean design',
        'vintage' => 'vintage photography, retro, film grain, nostalgic'
    ];

    $prompt = $this->extractSearchQuery($title);
    $prompt .= ', ' . $stylePrompts[$style];

    // ... rest of generation logic
}
```

**Usage:**
```php
$imageGen = new ImageGenerator('openai');
$result = $imageGen->generateThumbnail("Coffee Brewing Guide", [
    'style' => 'artistic', // or 'minimalist', 'vintage', etc.
    'size' => '1200x630'
]);
```

### Keyword Extraction (Unsplash)

Fine-tune search queries in `core/AI/ImageGenerator.php`:

```php
private function extractSearchQuery($title) {
    // Remove common filler words
    $fillerWords = [
        'how to', 'guide to', 'introduction to',
        'what is', 'why', 'when', 'where',
        'the', 'a', 'an', 'and', 'or', 'but'
    ];

    $cleanTitle = strtolower($title);

    foreach ($fillerWords as $filler) {
        $cleanTitle = preg_replace('/^' . $filler . '\s+/i', '', $cleanTitle);
    }

    // Remove special characters
    $cleanTitle = preg_replace('/[^\w\s]/', '', $cleanTitle);

    // Take first 4 keywords
    $words = explode(' ', $cleanTitle);
    $keywords = array_slice($words, 0, 4);

    return implode(' ', $keywords);
}
```

---

## Troubleshooting

### Issue: Thumbnails Not Showing

**Cause:** Posts missing `featured_image` field

**Solution:**
1. Go to Admin → Posts → All Posts
2. Select posts without images
3. Bulk Actions → Auto Generate Thumbnails
4. Apply and wait

### Issue: Unsplash "No Images Found"

**Cause:** Query too specific or unusual

**Solution:**
1. Simplify post title
2. Use more common keywords
3. Or switch to OpenAI for niche topics

### Issue: OpenAI "Insufficient Quota"

**Cause:** No billing or credits exhausted

**Solution:**
1. Go to https://platform.openai.com/account/billing
2. Add payment method
3. Add credits ($10 recommended)
4. Retry generation

### Issue: Images Not Accessible

**Cause:** Permissions issue on uploads directory

**Solution:**
```bash
chmod 755 /path/to/content/uploads
chown www-data:www-data /path/to/content/uploads
```

### Issue: Rate Limit Exceeded

**Provider:** Unsplash (500/hour) or OpenAI (50/minute)

**Solution:**
1. Wait for rate limit to reset
2. Process in smaller batches
3. Use PHP GD as temporary fallback

---

## Cost Tracking

### Monitor Usage

**View AI Costs:**
```sql
SELECT
    provider,
    COUNT(*) as images_generated,
    SUM(cost) as total_cost,
    AVG(cost) as avg_cost
FROM ai_usage
WHERE provider IN ('openai', 'unsplash', 'php-gd')
GROUP BY provider;
```

**Expected Results:**
```
Provider  | Images | Total Cost | Avg Cost
----------|--------|------------|----------
unsplash  | 100    | $0.00      | $0.00
openai    | 20     | $0.80      | $0.04
php-gd    | 5      | $0.00      | $0.00
```

### Set Monthly Budget

**For OpenAI:**
1. Go to https://platform.openai.com/account/limits
2. Set **Hard Limit:** $50/month
3. Set **Soft Limit:** $30/month (alert)

**Calculate Needs:**
- 10 posts/month × $0.04 = $0.40
- 50 posts/month × $0.04 = $2.00
- 200 posts/month × $0.04 = $8.00

---

## Commercial Platform Features

### For Reselling LightBlog CMS

**1. Multi-Tier Pricing**

- **Free Tier:** PHP GD only
- **Basic Tier:** Unsplash included ($0/month)
- **Pro Tier:** OpenAI DALL-E 3 ($5/month)
- **Enterprise:** Self-hosted Stable Diffusion

**2. White-Label Setup**

```php
// config.php for customer sites
define('DEFAULT_IMAGE_PROVIDER', 'unsplash'); // free tier
define('ALLOW_OPENAI_UPGRADE', true); // show upgrade option
define('MONTHLY_IMAGE_LIMIT', 100); // enforce limits
```

**3. Usage Dashboard**

Create admin panel showing:
- Images generated this month
- Cost breakdown by provider
- Upgrade prompts when limits reached

---

## Summary

**Quick Recommendations:**

1. **Starting out?** → Use Unsplash (FREE)
2. **Need unique images?** → Use OpenAI ($0.04/image)
3. **No budget?** → Use PHP GD (FREE fallback)

**Best Value:**
- **Images:** Unsplash (FREE)
- **Content:** Gemini ($0.0001/post)
- **Total:** ~$0 per post

**Premium Quality:**
- **Images:** OpenAI ($0.04)
- **Content:** GPT-4 ($0.03)
- **Total:** $0.07 per post

Choose based on your needs and budget!

---

**Last Updated:** 2025-10-24
**Version:** 1.0
