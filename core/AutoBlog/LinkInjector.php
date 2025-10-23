<?php
/**
 * LightBlog CMS - Affiliate Link Injector
 * Injects affiliate links into content
 */

class LinkInjector {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Inject affiliate links into content
     */
    public function inject($content, $campaign_id = null) {
        // Find [PRODUCT_LINK] markers
        if (!preg_match_all('/\[PRODUCT_LINK\]/', $content, $matches, PREG_OFFSET_CAPTURE)) {
            return $content;
        }

        // Get relevant products
        $products = $this->getRelevantProducts($campaign_id);

        if (empty($products)) {
            // Remove markers if no products available
            return str_replace('[PRODUCT_LINK]', '', $content);
        }

        // Replace markers with actual links
        $offset = 0;
        foreach ($matches[0] as $i => $match) {
            $position = $match[1] + $offset;
            $context = $this->getContext($content, $position);
            $product = $this->matchProduct($context, $products);

            if ($product) {
                $linkHTML = $this->generateLinkHTML($product);
                $content = substr_replace($content, $linkHTML, $position, strlen('[PRODUCT_LINK]'));
                $offset += strlen($linkHTML) - strlen('[PRODUCT_LINK]');
            } else {
                // Remove marker if no product matched
                $content = substr_replace($content, '', $position, strlen('[PRODUCT_LINK]'));
                $offset -= strlen('[PRODUCT_LINK]');
            }
        }

        // Add disclosure
        $content = $this->addDisclosure($content);

        return $content;
    }

    /**
     * Get relevant products for campaign or all active products
     */
    private function getRelevantProducts($campaign_id = null) {
        if ($campaign_id) {
            return $this->db->query("
                SELECT p.* FROM affiliate_products p
                JOIN campaign_affiliates ca ON p.id = ca.product_id
                WHERE ca.campaign_id = ?
                ORDER BY ca.priority ASC
                LIMIT 10
            ", [$campaign_id]);
        }

        return $this->db->query("
            SELECT * FROM affiliate_products
            ORDER BY clicks DESC
            LIMIT 10
        ");
    }

    /**
     * Get context around marker position
     */
    private function getContext($content, $position, $radius = 200) {
        $start = max(0, $position - $radius);
        $length = min(strlen($content) - $start, $radius * 2);
        return substr($content, $start, $length);
    }

    /**
     * Match best product for context
     */
    private function matchProduct($context, $products) {
        $context = strtolower(strip_tags($context));
        $bestMatch = null;
        $highestScore = 0;

        foreach ($products as $product) {
            $keywords = json_decode($product->keywords ?? '[]', true);
            $score = 0;

            foreach ($keywords as $keyword) {
                if (stripos($context, $keyword) !== false) {
                    $score++;
                }
            }

            // Also check product name
            if (stripos($context, strtolower($product->product_name)) !== false) {
                $score += 3;
            }

            if ($score > $highestScore) {
                $highestScore = $score;
                $bestMatch = $product;
            }
        }

        return $highestScore > 0 ? $bestMatch : $products[0] ?? null;
    }

    /**
     * Generate affiliate link HTML
     */
    private function generateLinkHTML($product) {
        $trackingUrl = $this->generateTrackingUrl($product);

        return "
<div class='affiliate-product-box' style='border: 2px solid #e5e7eb; border-radius: 0.5rem; padding: 1.5rem; margin: 2rem 0; display: flex; gap: 1rem; background: #f9fafb;'>
    " . ($product->image_url ? "<img src='{$product->image_url}' alt='" . htmlspecialchars($product->product_name) . "' style='width: 150px; height: 150px; object-fit: cover; border-radius: 0.5rem;'>" : "") . "
    <div style='flex: 1;'>
        <h3 style='margin: 0 0 0.5rem 0; font-size: 1.25rem;'>" . htmlspecialchars($product->product_name) . "</h3>
        " . ($product->price ? "<p style='font-size: 1.5rem; font-weight: bold; color: #3b82f6; margin: 0.5rem 0;'>$" . number_format($product->price, 2) . "</p>" : "") . "
        <a href='{$trackingUrl}'
           class='affiliate-cta-button'
           rel='nofollow sponsored noopener'
           target='_blank'
           style='display: inline-block; padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 0.5rem; font-weight: 600; margin-top: 0.75rem;'>
            Check Latest Price →
        </a>
    </div>
</div>";
    }

    /**
     * Generate tracking URL
     */
    private function generateTrackingUrl($product) {
        // Add tracking parameters to affiliate URL
        $trackingId = 'lb-' . uniqid();
        $separator = strpos($product->affiliate_url, '?') !== false ? '&' : '?';

        return $product->affiliate_url . $separator . 'ref=' . $trackingId;
    }

    /**
     * Add affiliate disclosure
     */
    private function addDisclosure($content) {
        $disclosure = "
<div class='affiliate-disclosure' style='padding: 1rem; background: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 0.25rem; margin: 2rem 0; font-size: 0.875rem;'>
    <strong>⚠️ Disclosure:</strong> This post contains affiliate links. We may earn a commission if you make a purchase through these links at no additional cost to you. This helps support our content creation.
</div>";

        // Add at the beginning
        return $disclosure . "\n\n" . $content;
    }

    /**
     * Track affiliate click
     */
    public function trackClick($product_id, $post_id = null) {
        $this->db->insert('affiliate_clicks', [
            'post_id' => $post_id,
            'product_id' => $product_id,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'referrer' => $_SERVER['HTTP_REFERER'] ?? '',
            'clicked_at' => date('Y-m-d H:i:s')
        ]);

        // Update product clicks
        $this->db->query("UPDATE affiliate_products SET clicks = clicks + 1 WHERE id = ?", [$product_id]);
    }
}
