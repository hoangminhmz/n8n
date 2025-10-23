<?php
/**
 * LightBlog CMS - Table of Contents Generator
 * Automatically generates TOC from H2 and H3 headings
 */

class TOCGenerator {

    /**
     * Generate Table of Contents and insert into content
     */
    public function generate($content) {
        // Extract headings
        $headings = $this->extractHeadings($content);

        if (count($headings) < 3) {
            return $content; // Don't add TOC for short articles
        }

        // Add IDs to headings
        $content = $this->addHeadingIDs($content, $headings);

        // Generate TOC HTML
        $tocHTML = $this->generateTOCHTML($headings);

        // Insert TOC after first paragraph
        $content = $this->insertTOC($content, $tocHTML);

        return $content;
    }

    /**
     * Extract H2 and H3 headings from content
     */
    private function extractHeadings($content) {
        $headings = [];

        // Match H2 and H3 tags
        preg_match_all('/<h([23])[^>]*>(.*?)<\/h\1>/i', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $index => $match) {
            $level = (int)$match[1];
            $text = strip_tags($match[2]);
            $id = $this->generateID($text, $index);

            $headings[] = [
                'level' => $level,
                'text' => $text,
                'id' => $id,
                'full_tag' => $match[0]
            ];
        }

        return $headings;
    }

    /**
     * Generate URL-friendly ID from heading text
     */
    private function generateID($text, $index) {
        $id = strtolower(trim($text));
        $id = preg_replace('/[^a-z0-9]+/', '-', $id);
        $id = trim($id, '-');
        return $id ?: 'heading-' . $index;
    }

    /**
     * Add IDs to heading tags
     */
    private function addHeadingIDs($content, $headings) {
        foreach ($headings as $heading) {
            $originalTag = $heading['full_tag'];

            // Add ID to the heading tag
            $newTag = preg_replace(
                '/<h([23])([^>]*)>/',
                '<h$1$2 id="' . $heading['id'] . '">',
                $originalTag
            );

            $content = str_replace($originalTag, $newTag, $content);
        }

        return $content;
    }

    /**
     * Generate TOC HTML
     */
    private function generateTOCHTML($headings) {
        if (empty($headings)) {
            return '';
        }

        $html = '<div class="table-of-contents" style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.5rem; margin: 2rem 0;">';
        $html .= '<h2 style="margin-top: 0; margin-bottom: 1rem; font-size: 1.25rem;">📑 Table of Contents</h2>';
        $html .= '<ul style="list-style: none; padding-left: 0; margin: 0;">';

        $currentLevel = 2;

        foreach ($headings as $index => $heading) {
            $level = $heading['level'];

            // Handle nesting
            if ($level > $currentLevel) {
                $html .= '<ul style="list-style: none; padding-left: 1.5rem; margin-top: 0.5rem;">';
            } elseif ($level < $currentLevel && $index > 0) {
                $html .= '</ul>';
            }

            $indent = $level === 3 ? 'margin-left: 1.5rem;' : '';
            $html .= '<li style="margin-bottom: 0.5rem; ' . $indent . '">';
            $html .= '<a href="#' . $heading['id'] . '" style="color: #3b82f6; text-decoration: none; transition: color 0.2s;">';
            $html .= htmlspecialchars($heading['text']);
            $html .= '</a>';
            $html .= '</li>';

            $currentLevel = $level;
        }

        // Close any open nested lists
        if ($currentLevel === 3) {
            $html .= '</ul>';
        }

        $html .= '</ul>';
        $html .= '</div>';

        // Add smooth scroll JavaScript
        $html .= '<script>
            document.querySelectorAll(".table-of-contents a").forEach(anchor => {
                anchor.addEventListener("click", function(e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute("href"));
                    if (target) {
                        target.scrollIntoView({ behavior: "smooth", block: "start" });
                        target.style.backgroundColor = "#fef3c7";
                        setTimeout(() => target.style.backgroundColor = "", 2000);
                    }
                });
            });
        </script>';

        return $html;
    }

    /**
     * Insert TOC after first paragraph
     */
    private function insertTOC($content, $tocHTML) {
        // Find first closing </p> tag
        $firstParagraphEnd = strpos($content, '</p>');

        if ($firstParagraphEnd !== false) {
            $insertPosition = $firstParagraphEnd + 4; // After </p>
            $content = substr_replace($content, "\n\n" . $tocHTML . "\n\n", $insertPosition, 0);
        } else {
            // If no paragraph found, insert at beginning
            $content = $tocHTML . "\n\n" . $content;
        }

        return $content;
    }

    /**
     * Check if content already has TOC
     */
    public function hasTOC($content) {
        return strpos($content, 'class="table-of-contents"') !== false;
    }

    /**
     * Remove existing TOC from content
     */
    public function removeTOC($content) {
        // Remove TOC div and associated script
        $content = preg_replace('/<div class="table-of-contents"[^>]*>.*?<\/div>/s', '', $content);
        $content = preg_replace('/<script>.*?table-of-contents.*?<\/script>/s', '', $content);

        return $content;
    }
}
