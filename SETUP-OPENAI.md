# OpenAI Setup Guide - LightBlog CMS

Complete guide to configuring OpenAI for content generation and image creation.

---

## Overview

**OpenAI Integration Provides:**
- GPT-4 / GPT-3.5-turbo for content generation
- DALL-E 3 for AI-generated images
- High-quality output (premium tier)

**Cost:**
- **Content:** ~$0.03 per 1K tokens (GPT-4), ~$0.001 per 1K tokens (GPT-3.5-turbo)
- **Images:** $0.04 per image (1024x1024, standard quality)

**When to Use OpenAI:**
- Need unique AI-generated images (not stock photos)
- Want highest quality content generation
- Budget allows premium pricing

---

## Step 1: Create OpenAI Account

1. Go to https://platform.openai.com/signup
2. Sign up with email or Google account
3. Verify your email address
4. Complete phone verification (required for API access)

---

## Step 2: Add Payment Method

⚠️ **Important:** API access requires a paid account ($5 minimum)

1. Go to https://platform.openai.com/account/billing
2. Click "Add payment method"
3. Enter credit card details
4. Add credits (recommended: $10 to start)

**Billing:**
- Pay-as-you-go (charged per API call)
- $0.03 per 1K tokens (GPT-4)
- $0.04 per image (DALL-E 3)
- Set usage limits to avoid surprises: Settings → Limits

---

## Step 3: Generate API Key

1. Go to https://platform.openai.com/api-keys
2. Click "Create new secret key"
3. Name it: "LightBlog CMS Production"
4. **Copy the key immediately** (you won't see it again)
5. Store securely (password manager recommended)

**Example Key Format:**
```
sk-proj-abc123def456...
```

⚠️ **Security Warning:**
- Never commit API keys to Git
- Never share keys publicly
- Rotate keys if compromised

---

## Step 4: Configure LightBlog CMS

### Method A: Admin Settings Panel (Recommended)

1. Login to admin panel: `http://yourdomain.com/admin/`
2. Go to **Settings**
3. Scroll to **AI Providers** section
4. Paste your OpenAI API key in the **OpenAI API Key** field
5. Select provider preferences:
   - **Image AI Provider:** Choose "OpenAI" for DALL-E 3 images
   - **Content AI Provider:** Choose "OpenAI" for GPT-4 content
6. Click **Save Settings**

### Method B: Manual Configuration

Edit `config.php`:

```php
define('OPENAI_API_KEY', 'sk-proj-abc123def456...');
```

Save and reload admin panel.

---

## Step 5: Test the Integration

### Test Image Generation

1. Go to **Posts → Add New**
2. Enter a title: "Beautiful Mountain Landscape"
3. Scroll to **Featured Image** section
4. Click **Auto-Generate Thumbnail**
5. Wait 10-15 seconds
6. Image should appear (DALL-E 3 generated)

**Expected Result:**
- Unique AI-generated image
- Saved to `content/uploads/`
- Cost: $0.04

### Test Content Generation

1. Go to **Posts → Add New**
2. Enter a title: "Introduction to Machine Learning"
3. Add keywords: "AI, neural networks, deep learning"
4. Click **Generate Content with AI**
5. Wait 20-30 seconds
6. Post content should populate

**Expected Result:**
- 1500-2500 word article
- Well-structured with headings
- Cost: ~$0.03

---

## Step 6: Bulk Operations

### Bulk Thumbnail Generation

1. Go to **Posts → All Posts**
2. Select multiple posts (checkboxes)
3. Choose **Bulk Actions → Auto Generate Thumbnails**
4. Click **Apply**
5. Wait for completion (progress shown)

**Example:**
- 10 posts selected
- Time: ~2 minutes
- Cost: $0.40 (10 × $0.04)

### Bulk SEO Generation

1. Select posts without SEO data
2. Choose **Bulk Actions → Auto Generate SEO Data**
3. Click **Apply**

**Example:**
- 20 posts selected
- Time: ~3 minutes
- Cost: ~$0.60 (20 × $0.03)

---

## Cost Optimization

### Recommended Strategy

**For Budget-Conscious Users:**
1. **Images:** Use Unsplash (FREE) instead of DALL-E 3
2. **Content:** Use Gemini Flash ($0.0001/post) instead of GPT-4
3. **Result:** ~$0 per post vs. $0.07 with OpenAI

**For Premium Quality:**
1. **Images:** DALL-E 3 for unique visuals ($0.04)
2. **Content:** GPT-4 for best writing ($0.03)
3. **Result:** $0.07 per post

**Hybrid Approach:**
1. **Images:** Unsplash for most posts (FREE)
2. **Content:** GPT-4 for important articles ($0.03)
3. **Result:** $0.03 per post average

### Set Usage Limits

Prevent unexpected bills:

1. Go to https://platform.openai.com/account/limits
2. Set **Hard Limit:** $50/month (example)
3. Set **Soft Limit:** $30/month (email alert)
4. API will stop at hard limit

---

## Model Selection

### For Content Generation

**GPT-4 (Recommended):**
- Best quality and accuracy
- Cost: $0.03 per 1K input tokens, $0.06 per 1K output tokens
- Use for: Important articles, complex topics
- Average post: ~$0.03

**GPT-3.5-turbo (Budget):**
- Good quality, faster
- Cost: $0.0015 per 1K input tokens, $0.002 per 1K output tokens
- Use for: Bulk content, simple topics
- Average post: ~$0.005

**Configure in LightBlog:**
```php
// core/AI/ContentGenerator.php (optional customization)
$result = $provider->generate($prompt, [
    'model' => 'gpt-4', // or 'gpt-3.5-turbo'
    'temperature' => 0.7,
    'max_tokens' => 2000
]);
```

### For Image Generation

**DALL-E 3 (Only option):**
- High-quality, unique images
- Cost: $0.04 per image (1024x1024 standard)
- Cost: $0.08 per image (1024x1024 HD)
- Use for: Hero images, featured content

**Configure quality:**
```php
// core/AI/ImageGenerator.php (optional customization)
$result = $provider->generateImage($prompt, [
    'size' => '1024x1024', // or '1792x1024', '1024x1792'
    'quality' => 'standard' // or 'hd' (2x cost)
]);
```

---

## Troubleshooting

### Error: "Invalid API Key"

**Cause:** Key not configured or incorrect format

**Solution:**
1. Verify key starts with `sk-proj-` or `sk-`
2. Check for extra spaces or line breaks
3. Regenerate key at https://platform.openai.com/api-keys
4. Update in Settings panel

### Error: "Insufficient Quota"

**Cause:** No billing set up or credits exhausted

**Solution:**
1. Go to https://platform.openai.com/account/billing
2. Add payment method if missing
3. Add more credits
4. Check usage limits

### Error: "Rate Limit Exceeded"

**Cause:** Too many API calls in short time

**Solution:**
1. Wait 1 minute and retry
2. Reduce bulk operation batch size
3. Upgrade to higher tier: https://platform.openai.com/account/limits

### Images Not Generating

**Cause:** Wrong provider selected or API key issue

**Solution:**
1. Go to Settings → AI Providers
2. Verify **Image AI Provider** is set to "OpenAI" or "Auto-detect"
3. Verify OpenAI API Key is entered
4. Check browser console for errors
5. Check PHP error logs: `tail -f /var/log/php_errors.log`

### Content Quality Issues

**Cause:** Model or temperature settings

**Solution:**
1. Try GPT-4 instead of GPT-3.5-turbo (Settings panel)
2. Adjust temperature:
   - Lower (0.5): More focused, factual
   - Higher (0.9): More creative, varied
3. Improve prompt quality (add keywords, tone)

---

## Best Practices

### 1. API Key Security

✅ **Do:**
- Store keys in environment variables (production)
- Use separate keys for dev/staging/production
- Rotate keys every 90 days
- Monitor usage dashboard regularly

❌ **Don't:**
- Commit keys to Git repositories
- Share keys in support tickets
- Use same key across multiple sites
- Expose keys in client-side JavaScript

### 2. Cost Management

✅ **Do:**
- Set hard usage limits ($50/month)
- Monitor daily spend
- Use cheaper models for bulk operations
- Cache generated content (don't regenerate)

❌ **Don't:**
- Leave auto-blogging running unmonitored
- Generate images for every post if unnecessary
- Use GPT-4 for simple tasks
- Ignore usage alerts

### 3. Content Quality

✅ **Do:**
- Provide clear, detailed prompts
- Include keywords and tone guidance
- Review and edit AI-generated content
- Add human touch (personal anecdotes, examples)

❌ **Don't:**
- Publish AI content without review
- Use for medical/legal advice
- Rely on AI for fact-checking
- Generate content on prohibited topics

---

## Advanced Configuration

### Custom Prompts

Modify prompts in `core/AI/ContentGenerator.php`:

```php
public function generatePost($options) {
    $prompt = "Write a comprehensive blog post:\n\n";
    $prompt .= "Title: {$options['title']}\n";
    $prompt .= "Keywords: " . implode(', ', $options['keywords']) . "\n";
    $prompt .= "Tone: {$options['tone']}\n"; // professional, casual, technical
    $prompt .= "Word count: {$options['word_count']}\n\n";

    // Add your custom instructions
    $prompt .= "Requirements:\n";
    $prompt .= "- Use H2 and H3 headings\n";
    $prompt .= "- Include actionable tips\n";
    $prompt .= "- Add a conclusion with call-to-action\n";
    $prompt .= "- Write in active voice\n";

    $result = $this->provider->generate($prompt, [
        'model' => 'gpt-4',
        'temperature' => 0.7,
        'max_tokens' => 2000
    ]);

    return $result;
}
```

### Image Style Customization

Modify image prompts in `core/AI/ImageGenerator.php`:

```php
public function generateThumbnail($title, $options = []) {
    $style = $options['style'] ?? 'professional';

    $stylePrompts = [
        'professional' => 'professional, clean, modern, high-quality photography',
        'artistic' => 'artistic, creative, vibrant colors, unique composition',
        'minimalist' => 'minimalist, simple, clean lines, neutral colors',
        'photorealistic' => 'photorealistic, detailed, natural lighting, sharp focus'
    ];

    $prompt = $this->extractSearchQuery($title);
    $prompt .= ', ' . $stylePrompts[$style];

    $result = $this->provider->generateImage($prompt, [
        'size' => '1024x1024',
        'quality' => 'standard'
    ]);

    return $result;
}
```

---

## Alternative: Use Free Options

### Unsplash for Images (Recommended)

**Pros:**
- Completely FREE (500 requests/hour)
- High-quality stock photos
- No billing required

**Cons:**
- Stock photos (not unique)
- Must display attribution

**Setup:**
1. Go to https://unsplash.com/developers
2. Create app (free)
3. Copy Access Key
4. Add to Settings → Unsplash API Key
5. Set Image AI Provider → Unsplash

### Gemini for Content (Recommended)

**Pros:**
- Nearly FREE ($0.0001 per post)
- Fast generation
- Good quality

**Cons:**
- Slightly lower quality than GPT-4

**Setup:**
1. Go to https://aistudio.google.com/app/apikey
2. Create API key (free tier: 60 requests/minute)
3. Add to Settings → Gemini API Key
4. Set Content AI Provider → Gemini

---

## Summary

**OpenAI is best for:**
- Unique AI-generated images (DALL-E 3)
- Highest quality content (GPT-4)
- Premium tier customers

**Cost:** ~$0.07 per post (image + content)

**Free alternative:** Unsplash + Gemini = $0 per post

**Hybrid approach:** Unsplash + GPT-4 = $0.03 per post

Choose based on your budget and quality requirements!

---

**Last Updated:** 2025-10-24
**Version:** 1.0
