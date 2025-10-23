<?php
/**
 * Test AJAX endpoint directly
 */

// Simulate POST request to ajax-autofill-seo.php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [];

// Set POST data
$postData = [
    'title' => 'The Future of Rest: What Advanced Materials and IoT Bring to the Best Mattress Designs',
    'content' => '<p>This is a test article about mattresses. It talks about advanced materials like memory foam and gel-infused layers.</p><p>IoT technology is revolutionizing how we sleep, with smart mattresses that track sleep patterns.</p><p>Modern mattresses combine comfort with technology for better sleep quality.</p>',
    'postUrl' => ''
];

// Save to php://input simulation
file_put_contents('php://stdin', json_encode($postData));

echo "<h1>Testing AJAX Endpoint Directly</h1>";
echo "<h2>Request Data:</h2>";
echo "<pre>" . json_encode($postData, JSON_PRETTY_PRINT) . "</pre>";

echo "<h2>Response from ajax-autofill-seo.php:</h2>";
echo "<div style='border: 2px solid #333; padding: 10px; background: #f0f0f0;'>";

// Capture output
ob_start();

// Simulate the AJAX request
$_SERVER['HTTP_CONTENT_TYPE'] = 'application/json';
$GLOBALS['HTTP_RAW_POST_DATA'] = json_encode($postData);

// Mock php://input
stream_wrapper_unregister("php");
stream_wrapper_register("php", "MockPhpStream");

class MockPhpStream {
    public $context;
    private $data;
    private $position;

    function stream_open($path, $mode, $options, &$opened_path) {
        $this->data = json_encode([
            'title' => 'The Future of Rest: What Advanced Materials and IoT Bring to the Best Mattress Designs',
            'content' => '<p>This is a test article about mattresses. It talks about advanced materials like memory foam and gel-infused layers.</p><p>IoT technology is revolutionizing how we sleep, with smart mattresses that track sleep patterns.</p><p>Modern mattresses combine comfort with technology for better sleep quality.</p>',
            'postUrl' => ''
        ]);
        $this->position = 0;
        return true;
    }

    function stream_read($count) {
        $ret = substr($this->data, $this->position, $count);
        $this->position += strlen($ret);
        return $ret;
    }

    function stream_eof() {
        return $this->position >= strlen($this->data);
    }

    function stream_stat() {
        return [];
    }
}

try {
    include __DIR__ . '/ajax-autofill-seo.php';
    $output = ob_get_clean();

    echo "<pre>RAW OUTPUT:\n" . htmlspecialchars($output) . "</pre>";

    // Try to parse as JSON
    echo "<h3>Parsed JSON:</h3>";
    $json = json_decode($output, true);
    if ($json) {
        echo "<pre>" . json_encode($json, JSON_PRETTY_PRINT) . "</pre>";

        if ($json['success']) {
            echo "<p style='color: green; font-weight: bold;'>✅ SUCCESS! The endpoint is working.</p>";
        } else {
            echo "<p style='color: red; font-weight: bold;'>❌ ERROR: " . htmlspecialchars($json['error']) . "</p>";
        }
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ INVALID JSON!</p>";
        echo "<p>JSON Error: " . json_last_error_msg() . "</p>";
    }

} catch (Exception $e) {
    $output = ob_get_clean();
    echo "<pre>EXCEPTION:\n" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "<pre>RAW OUTPUT:\n" . htmlspecialchars($output) . "</pre>";
}

echo "</div>";

// Restore php stream wrapper
stream_wrapper_restore("php");
