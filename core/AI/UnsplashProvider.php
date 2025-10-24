<?php
/**
 * LightBlog CMS - Unsplash Provider
 * Integration with Unsplash API for free stock photos
 */

class UnsplashProvider {
    private $api_key;
    private $endpoint = 'https://api.unsplash.com';
    private $db;

    /**
     * Constructor
     * @param string $api_key Unsplash API key (Access Key)
     */
    public function __construct($api_key) {
        $this->api_key = $api_key;
        $this->db = Database::getInstance();
    }

    /**
     * Search and download image based on query
     * @param string $query Search query
     * @param array $options Options (orientation, size)
     * @return array ['image_url' => string, 'cost' => 0.0, 'provider' => 'unsplash']
     */
    public function searchAndDownload($query, $options = []) {
        if (empty($this->api_key)) {
            throw new Exception('Unsplash API key not configured');
        }

        $orientation = $options['orientation'] ?? 'landscape';
        $perPage = 1;

        // Search for images
        $searchUrl = $this->endpoint . '/search/photos?' . http_build_query([
            'query' => $query,
            'orientation' => $orientation,
            'per_page' => $perPage,
            'content_filter' => 'high' // Family-friendly content
        ]);

        $ch = curl_init($searchUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Client-ID ' . $this->api_key,
            'Accept-Version: v1'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception('Unsplash API error: HTTP ' . $httpCode);
        }

        $result = json_decode($response, true);

        if (empty($result['results'])) {
            throw new Exception('No images found for query: ' . $query);
        }

        $photo = $result['results'][0];

        // Get regular size URL (1080px width)
        $imageUrl = $photo['urls']['regular'];
        $photographerName = $photo['user']['name'];
        $photographerUsername = $photo['user']['username'];
        $downloadLocation = $photo['links']['download_location'];

        // Trigger download endpoint (required by Unsplash API guidelines)
        $this->triggerDownload($downloadLocation);

        // Download image and save locally
        $localUrl = $this->downloadAndSaveImage($imageUrl, $query);

        if (!$localUrl) {
            throw new Exception('Failed to download image from Unsplash');
        }

        // Log attribution in database (optional)
        $this->logAttribution($localUrl, $photographerName, $photographerUsername, $photo['id']);

        return [
            'image_url' => $localUrl,
            'cost' => 0.0,
            'provider' => 'unsplash',
            'attribution' => "Photo by {$photographerName} on Unsplash"
        ];
    }

    /**
     * Trigger download endpoint (required by Unsplash API)
     * @param string $downloadLocation Download location URL
     */
    private function triggerDownload($downloadLocation) {
        $ch = curl_init($downloadLocation);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Client-ID ' . $this->api_key
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_exec($ch);
        curl_close($ch);
    }

    /**
     * Download and save image locally
     * @param string $imageUrl Unsplash image URL
     * @param string $query Search query for filename
     * @return string|null Local URL or null on failure
     */
    private function downloadAndSaveImage($imageUrl, $query) {
        try {
            // Download image
            $imageData = file_get_contents($imageUrl);
            if ($imageData === false) {
                return null;
            }

            // Create uploads directory
            $uploadsDir = CONTENT_PATH . '/uploads';
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
            }

            // Generate filename
            $filename = 'unsplash-' . time() . '-' . substr(md5($query), 0, 8) . '.jpg';
            $filepath = $uploadsDir . '/' . $filename;

            // Save image
            file_put_contents($filepath, $imageData);

            // Return URL
            return SITE_URL . '/content/uploads/' . $filename;

        } catch (Exception $e) {
            error_log('Unsplash: Failed to download image: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Log attribution for compliance
     * @param string $localUrl Local image URL
     * @param string $photographerName Photographer name
     * @param string $photographerUsername Photographer username
     * @param string $photoId Unsplash photo ID
     */
    private function logAttribution($localUrl, $photographerName, $photographerUsername, $photoId) {
        try {
            // Store in database for compliance tracking
            $this->db->query(
                "INSERT INTO media (filename, original_filename, file_path, mime_type, uploaded_at, uploaded_by)
                VALUES (?, ?, ?, ?, NOW(), 0)
                ON DUPLICATE KEY UPDATE uploaded_at = NOW()",
                [
                    basename($localUrl),
                    "unsplash_{$photoId}",
                    $localUrl,
                    'image/jpeg'
                ]
            );
        } catch (Exception $e) {
            error_log('Failed to log Unsplash attribution: ' . $e->getMessage());
        }
    }

    /**
     * Validate API key
     * @return bool
     */
    public function validateApiKey() {
        return !empty($this->api_key);
    }

    /**
     * Get provider name
     * @return string
     */
    public function getName() {
        return 'unsplash';
    }
}
