# 🚀 LightBlog CMS - SEO Enhancement Guide

## Tổng quan

LightBlog CMS hiện đã tích hợp hệ thống SEO toàn diện với **23 trường dữ liệu SEO mới** và tự động hóa hoàn toàn cho nội dung AI-generated.

---

## 📦 1. CÀI ĐẶT (BẮT BUỘC)

### Bước 1: Chạy Database Migration

```bash
# MySQL
mysql -u your_username -p your_database < install/migrate-seo.sql

# Hoặc qua phpMyAdmin
# Import file: install/migrate-seo.sql
```

Migration này thêm 23 trường mới vào bảng `posts`:

```sql
✅ focus_keyword          - Từ khóa chính
✅ canonical_url          - URL chuẩn
✅ meta_robots            - index,follow
✅ og_title               - Open Graph title
✅ og_description         - Open Graph description
✅ og_image               - Open Graph image
✅ twitter_title          - Twitter Card title
✅ twitter_description    - Twitter Card description
✅ twitter_image          - Twitter Card image
✅ schema_type            - Article/HowTo/FAQ/Review
✅ faq_data               - FAQ schema JSON
✅ readability_score      - Flesch score 0-100
✅ word_count             - Số từ
✅ reading_time           - Thời gian đọc (phút)
✅ internal_links_count   - Số link nội bộ
✅ external_links_count   - Số link ngoài
✅ images_count           - Số hình ảnh
✅ has_table_of_contents  - Có TOC không (0/1)
✅ seo_score              - Điểm SEO 0-100
✅ last_seo_check         - Thời gian check cuối
```

### Bước 2: Upload Code

Upload các file mới lên server:
```
core/SEO/SEOAnalyzer.php
core/SEO/TOCGenerator.php
core/SEO/FAQExtractor.php
core/AI/ContentGenerator.php (đã update)
```

---

## ✨ 2. TÍNH NĂNG TỰ ĐỘNG

### AI Content Generation

Khi tạo bài viết bằng AI, **TẤT CẢ các trường SEO được điền tự động**:

```php
// Tự động khi generate content
✅ Focus keyword từ campaign seeds
✅ SEO title (55-60 chars)
✅ Meta description (150-160 chars)
✅ Open Graph tags
✅ Twitter Cards
✅ Canonical URL
✅ Table of Contents (nếu >3 headings)
✅ FAQ schema (nếu phát hiện FAQ)
✅ Word count, reading time
✅ Readability score
✅ Link & image analysis
✅ SEO score tổng thể
```

### Table of Contents (TOC)

Tự động tạo mục lục từ H2, H3:

- ✅ Tự động thêm anchor IDs cho headings
- ✅ Chèn TOC sau đoạn văn đầu tiên
- ✅ Smooth scroll navigation
- ✅ Highlight heading khi click
- ✅ Chỉ thêm khi có ≥3 headings

### FAQ Schema Detection

Tự động phát hiện và extract FAQ:

```html
<!-- Pattern 1: H3 + P -->
<h3>What is SEO?</h3>
<p>SEO stands for Search Engine Optimization...</p>

<!-- Pattern 2: Details/Summary -->
<details>
  <summary>What is SEO?</summary>
  SEO stands for...
</details>

<!-- Pattern 3: Definition List -->
<dl>
  <dt>What is SEO?</dt>
  <dd>SEO stands for...</dd>
</dl>
```

Tự động tạo JSON-LD schema:
```json
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [...]
}
```

---

## 📊 3. SEO SCORE SYSTEM

### Điểm tính như thế nào? (Tổng 100 điểm)

| Tiêu chí | Điểm | Yêu cầu |
|----------|------|---------|
| **Focus Keyword** | 10 | Có từ khóa chính |
| **Keyword in Title** | 10 | Từ khóa trong tiêu đề |
| **Keyword in Content** | 10 | Từ khóa trong 100 từ đầu |
| **Meta Description** | 10 | Có meta description |
| **Word Count** | 10 | 1500-2500 từ (ideal) |
| **Internal Links** | 10 | 2-5 links nội bộ |
| **External Links** | 10 | 1-3 links ra ngoài |
| **Images** | 10 | ≥3 hình ảnh |
| **Readability** | 10 | Flesch score >60 |
| **Table of Contents** | 10 | Có TOC cho bài dài |

### Score Labels

```
90-100: 🟢 Excellent (Xuất sắc)
60-89:  🔵 Good (Tốt)
40-59:  🟡 Fair (Khá)
0-39:   🔴 Poor (Kém)
```

---

## 🔧 4. SỬ DỤNG CLASSES

### SEOAnalyzer

```php
require_once 'core/SEO/SEOAnalyzer.php';

$analyzer = new SEOAnalyzer();

// Phân tích post
$metrics = $analyzer->analyze($post, $content);
/*
Returns:
[
  'word_count' => 1850,
  'reading_time' => 10,
  'readability_score' => 65.2,
  'internal_links_count' => 3,
  'external_links_count' => 2,
  'images_count' => 5,
  'has_table_of_contents' => 1,
  'seo_score' => 85
]
*/

// Lấy recommendations
$recommendations = $analyzer->getRecommendations($post, $content, $metrics);
/*
[
  '⚠️ Add more internal links (2-5 recommended)',
  '⚠️ Include focus keyword in title',
  ...
]
*/

// Score label
$scoreInfo = $analyzer->getScoreLabel(85);
// ['label' => 'Excellent', 'color' => 'success']
```

### TOCGenerator

```php
require_once 'core/SEO/TOCGenerator.php';

$tocGen = new TOCGenerator();

// Tự động thêm TOC
$contentWithTOC = $tocGen->generate($content);

// Check nếu đã có TOC
if ($tocGen->hasTOC($content)) {
    echo "Already has TOC";
}

// Remove TOC
$contentWithoutTOC = $tocGen->removeTOC($content);
```

### FAQExtractor

```php
require_once 'core/SEO/FAQExtractor.php';

$faqExtractor = new FAQExtractor();

// Extract FAQs
$faqs = $faqExtractor->extract($content);
/*
[
  ['question' => '...', 'answer' => '...'],
  ...
]
*/

// Generate schema
$schema = $faqExtractor->generateSchema($faqs);

// Detect schema type
$type = $faqExtractor->detectSchemaType($content);
// Returns: 'Article', 'HowTo', 'FAQPage', or 'Review'

// Generate Article schema
$articleSchema = $faqExtractor->generateArticleSchema($post);
```

---

## 🎨 5. FRONTEND RENDERING (TODO)

### Trong theme header.php

```php
<?php if (isset($post)): ?>
<!-- SEO Meta -->
<title><?= htmlspecialchars($post->seo_title ?? $post->title) ?></title>
<meta name="description" content="<?= htmlspecialchars($post->meta_description) ?>">
<meta name="robots" content="<?= $post->meta_robots ?? 'index,follow' ?>">
<link rel="canonical" href="<?= $post->canonical_url ?>">

<!-- Open Graph -->
<meta property="og:title" content="<?= htmlspecialchars($post->og_title) ?>">
<meta property="og:description" content="<?= htmlspecialchars($post->og_description) ?>">
<meta property="og:image" content="<?= $post->og_image ?>">
<meta property="og:type" content="article">
<meta property="og:url" content="<?= $post->canonical_url ?>">

<!-- Twitter Cards -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= htmlspecialchars($post->twitter_title) ?>">
<meta name="twitter:description" content="<?= htmlspecialchars($post->twitter_description) ?>">
<meta name="twitter:image" content="<?= $post->twitter_image ?>">

<!-- JSON-LD Schema -->
<?php
require_once SITE_PATH . '/core/SEO/FAQExtractor.php';
$faqExtractor = new FAQExtractor();

// Article Schema
$articleSchema = $faqExtractor->generateArticleSchema($post);
echo '<script type="application/ld+json">';
echo json_encode($articleSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
echo '</script>';

// FAQ Schema (if available)
if (!empty($post->faq_data)) {
    $faqs = json_decode($post->faq_data, true);
    $faqSchema = $faqExtractor->generateSchema($faqs);
    echo '<script type="application/ld+json">';
    echo json_encode($faqSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    echo '</script>';
}
?>
<?php endif; ?>
```

### Trong single post template

```php
<article>
  <header>
    <h1><?= htmlspecialchars($post->title) ?></h1>

    <div class="post-meta">
      <time datetime="<?= $post->published_at ?>">
        📅 <?= date('F j, Y', strtotime($post->published_at)) ?>
      </time>
      <span>⏱️ <?= $post->reading_time ?> min read</span>
      <span>📊 <?= number_format($post->word_count) ?> words</span>
    </div>
  </header>

  <div class="post-content">
    <?= $post->content ?>
    <!-- TOC already included in content -->
  </div>
</article>
```

---

## 📈 6. ADMIN UI (TODO - Phase 3)

Bạn có thể thêm vào `admin/posts.php`:

### SEO Score Dashboard

```php
<?php if ($post->seo_score): ?>
<div class="seo-dashboard">
  <h3>SEO Analysis</h3>

  <div class="seo-score">
    <div class="score-circle">
      <?= $post->seo_score ?>
    </div>
    <span class="badge badge-<?= $scoreInfo['color'] ?>">
      <?= $scoreInfo['label'] ?>
    </span>
  </div>

  <div class="seo-metrics">
    <div>📝 <?= number_format($post->word_count) ?> words</div>
    <div>⏱️ <?= $post->reading_time ?> min read</div>
    <div>📖 Readability: <?= $post->readability_score ?></div>
    <div>🔗 Internal: <?= $post->internal_links_count ?></div>
    <div>🔗 External: <?= $post->external_links_count ?></div>
    <div>🖼️ Images: <?= $post->images_count ?></div>
  </div>

  <?php if (!empty($recommendations)): ?>
  <div class="seo-recommendations">
    <h4>Recommendations:</h4>
    <ul>
      <?php foreach ($recommendations as $rec): ?>
        <li><?= $rec ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>
```

### SEO Fields Form

```php
<h3>SEO Settings</h3>

<div class="form-group">
  <label>Focus Keyword</label>
  <input type="text" name="focus_keyword" value="<?= $post->focus_keyword ?>">
</div>

<div class="form-group">
  <label>SEO Title (55-60 chars)</label>
  <input type="text" name="seo_title" value="<?= $post->seo_title ?>" maxlength="60">
  <small>Length: <span id="seo-title-length">0</span>/60</small>
</div>

<div class="form-group">
  <label>Meta Description (150-160 chars)</label>
  <textarea name="meta_description" maxlength="160"><?= $post->meta_description ?></textarea>
  <small>Length: <span id="meta-desc-length">0</span>/160</small>
</div>
```

---

## ✅ 7. TESTING

### Test SEO Features

Tạo 1 test file:

```php
<?php
require_once 'config.php';
require_once 'core/Database.php';
require_once 'core/SEO/SEOAnalyzer.php';
require_once 'core/SEO/TOCGenerator.php';
require_once 'core/SEO/FAQExtractor.php';

// Get a post
$db = Database::getInstance();
$post = $db->queryOne("SELECT * FROM posts WHERE id = 1");

// Test SEO Analysis
$analyzer = new SEOAnalyzer();
$metrics = $analyzer->analyze($post, $post->content);
print_r($metrics);

// Test TOC
$tocGen = new TOCGenerator();
$contentWithTOC = $tocGen->generate($post->content);
echo $contentWithTOC;

// Test FAQ
$faqExtractor = new FAQExtractor();
$faqs = $faqExtractor->extract($post->content);
print_r($faqs);

$schema = $faqExtractor->generateSchema($faqs);
echo json_encode($schema, JSON_PRETTY_PRINT);
```

---

## 🎯 8. BENEFITS

### Trước khi có SEO features:
❌ Không có meta tags đầy đủ
❌ Không có structured data
❌ Không biết chất lượng content
❌ Thiếu Open Graph, Twitter Cards
❌ Không có TOC cho bài dài
❌ Không có canonical URL

### Sau khi có SEO features:
✅ **23 trường SEO** tự động
✅ **Structured data** (Article, FAQ, HowTo)
✅ **SEO score** 0-100 với recommendations
✅ **Rich snippets** ready
✅ **TOC** tự động cho UX tốt hơn
✅ **Open Graph + Twitter Cards** đầy đủ
✅ **Content quality metrics** chi tiết
✅ **Zero manual work** cho AI content

---

## 📚 9. NEXT STEPS

**Phase 3 - UI (Tùy chọn):**

1. [ ] Thêm SEO tab trong post editor
2. [ ] Hiển thị SEO score dashboard
3. [ ] Real-time SEO analysis khi nhập content
4. [ ] SEO recommendations panel
5. [ ] Meta preview (Google/Facebook/Twitter)

**Phase 4 - Advanced (Tùy chọn):**

1. [ ] HowTo schema cho tutorials
2. [ ] Review schema cho product reviews
3. [ ] Breadcrumb schema
4. [ ] Author/Publisher schema nâng cao
5. [ ] Related posts suggestions dựa trên keywords

---

## 🆘 10. TROUBLESHOOTING

### Migration lỗi?

```bash
# Check nếu columns đã tồn tại
SHOW COLUMNS FROM posts LIKE '%seo%';

# Nếu đã tồn tại, skip migration
```

### Không tự động generate SEO fields?

Check `core/AI/ContentGenerator.php` đã được update chưa:
```php
// Phải return đầy đủ các fields:
return [
  'focus_keyword' => ...,
  'og_title' => ...,
  'seo_score' => ...,
  ...
];
```

### TOC không hiện?

- Cần ít nhất 3 headings (H2/H3)
- Check content có HTML tags đúng format

---

## 📞 SUPPORT

Nếu cần hỗ trợ:
1. Check file `SEO-FEATURES.md`
2. Xem code examples trong các class
3. Test với file test đơn giản

---

**Happy Optimizing! 🚀**
