<?php

// Include Composer's autoloader to load libraries
require __DIR__ . '/vendor/autoload.php';

// Load environment variables from .env file
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad(); // Use safeLoad to prevent errors if .env doesn't exist

// Use the Twilio SDK classes
use Twilio\TwiML\VoiceResponse;
use Twilio\Security\RequestValidator;

// --- Configuration ---
// WebSocket endpoint to stream call audio to
$websocketUrl = 'wss://devapi.ivoz.ai/llm-campaigns/ws/groq/?bot=ivoz';
// Stream track options: 'inbound_track', 'outbound_track', or 'both_tracks'
$streamTrack = 'inbound_track';
// Get Twilio Auth Token for request validation
$twilioAuthToken = $_ENV['TWILIO_AUTH_TOKEN'];
// Logging path - ensure this directory exists and is writable
$logPath = __DIR__ . '/logs/twilio_webhook.log';

// Set up error handling
ini_set('display_errors', 0); // Don't show errors to the client
ini_set('log_errors', 1);
ini_set('error_log', $logPath);

// Create logging function
function logMessage($message, $level = 'INFO') {
    global $logPath;
    
    // Ensure logs directory exists
    $logsDir = dirname($logPath);
    if (!is_dir($logsDir)) {
        mkdir($logsDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $formattedMessage = "[$timestamp] [$level] $message" . PHP_EOL;
    
    // Log to file
    file_put_contents($logPath, $formattedMessage, FILE_APPEND);
}

// --- Request Validation (Twilio Security Check) ---
try {
    $validator = new RequestValidator($twilioAuthToken);
    $signature = $_SERVER['HTTP_X_TWILIO_SIGNATURE'] ?? '';
    $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . 
           $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    
    // For development/testing only - comment out in production
    if (strpos($url, 'localhost') !== false || strpos($url, '127.0.0.1') !== false) {
        logMessage("Local development detected - skipping signature validation", "WARNING");
    } else {
        // Validate the request signature in production
        if (!$validator->validate($signature, $url, $_POST)) {
            logMessage("Invalid Twilio signature received: $signature for URL: $url", "ERROR");
            header('Content-Type: text/plain');
            http_response_code(403);
            echo 'Access Denied: Invalid signature';
            exit;
        }
        logMessage("Signature validation passed for: $url", "INFO");
    }
} catch (Exception $e) {
    logMessage("Exception during signature validation: " . $e->getMessage(), "ERROR");
    // Continue processing in development, but in production you may want to exit here
}

// Log the incoming call details
$callSid = $_POST['CallSid'] ?? 'Unknown';
$from = $_POST['From'] ?? 'Unknown';
$to = $_POST['To'] ?? 'Unknown';
logMessage("Incoming call received - SID: $callSid, From: $from, To: $to", "INFO");
logMessage("Generating TwiML to stream to WebSocket: $websocketUrl", "INFO");

try {
    // Create a new TwiML response object
    $response = new VoiceResponse();
    
    // You can add a welcome message or instructions if needed
    $response->say('Thank you for calling. Your call is being processed.');
    
    // Create the <Connect> verb
    $connect = $response->connect();
    
    // Create the <Stream> verb pointing to the WebSocket URL
    $stream = $connect->stream([
        'url' => $websocketUrl,
        'track' => $streamTrack,
        'name' => 'incomingCall' // Optional name for the stream
    ]);
    
    // Add custom parameters if needed by the WebSocket server
    $stream->parameter(['name' => 'callSid', 'value' => $callSid]);
    $stream->parameter(['name' => 'fromNumber', 'value' => $from]);
    $stream->parameter(['name' => 'toNumber', 'value' => $to]);
    
    // Set the response content type to XML
    header('Content-Type: application/xml');
    
    // Output the generated TwiML
    echo $response;
    
    // Log successful response
    logMessage("TwiML response generated successfully", "INFO");
    
} catch (Exception $e) {
    // Log the error
    logMessage("Error generating TwiML response: " . $e->getMessage(), "ERROR");
    
    // Send a basic error response
    header('Content-Type: application/xml');
    $errorResponse = new VoiceResponse();
    $errorResponse->say('We apologize, but an error occurred processing your call.');
    echo $errorResponse;
}