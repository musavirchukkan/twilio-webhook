# Twilio Voice Call Stream Webhook Setup

This document provides comprehensive instructions for setting up the Twilio Voice Call Stream webhook integration that forwards incoming call audio to a specified WebSocket endpoint.

## Project Structure

```
twilio-webhook/
├── composer.json       # Dependency configuration
├── .env                # Environment variables (create from .env.sample)
├── .env.sample         # Template for environment variables
├── webhook.php         # Main webhook handler script
├── logs/               # Directory for logs (created automatically)
└── vendor/             # Dependencies installed by Composer
```

## Setup Instructions

### 1. Initial Setup & Dependencies

1. Create a new project directory:

   ```bash
   mkdir twilio-webhook
   cd twilio-webhook
   ```

2. Copy all provided files to this directory.

3. Install dependencies using Composer:

   ```bash
   composer install
   ```

4. Create your environment file:

   ```bash
   cp .env.sample .env
   ```

   The default configuration in `.env.sample` includes the provided credentials, but you should review it to ensure all values are correct.

### 2. Local Development Testing

For local testing, you'll need to expose your local server to the internet using a tool like ngrok.

1. Start the PHP development server:

   ```bash
   php -S 0.0.0.0:8000
   ```

2. In a separate terminal, start ngrok to create a tunnel to your local server:

   ```bash
   ngrok http 8000
   ```

3. Copy the HTTPS URL provided by ngrok (e.g., `https://abc123.ngrok.io`).

### 3. Configure Twilio

1. Log into your Twilio account at [https://www.twilio.com/console](https://www.twilio.com/console)

2. Navigate to "Phone Numbers" → "Manage" → "Active Numbers"

3. Select the number `+1 (934) 253-0570`

4. In the "Voice & Fax" section, under "A CALL COMES IN":
   - Select "Webhook" from the dropdown
   - Enter your webhook URL: `[YOUR_NGROK_URL]/webhook.php` (for testing) or your production URL
   - Make sure "HTTP POST" is selected
   - Save the configuration

### 4. Testing

1. Make sure your PHP server and ngrok are both running.

2. Call the configured Twilio phone number `+1 (934) 253-0570`.

3. Check the logs in the `logs/` directory to verify the call was received and TwiML was generated.

4. Verify that your WebSocket server at `wss://devapi.ivoz.ai/llm-campaigns/ws/groq/?bot=ivoz` receives a connection and the audio stream from Twilio.

### 5. Production Deployment

For production deployment:

1. Deploy the code to a secure web server with HTTPS enabled.

2. Set your environment variables via your web server configuration rather than using the `.env` file.

3. Enable signature validation in the `webhook.php` file by removing the localhost validation bypass.

4. Configure proper server logging.

5. Update the Twilio webhook URL in your Twilio console to point to your production URL.

## Security Considerations

1. **Never commit your `.env` file to version control.**

2. Ensure request validation is enabled in production to verify requests come from Twilio.

3. Use HTTPS for your webhook endpoint, as Twilio will not send requests to insecure endpoints in production.

4. Regularly update the Twilio SDK and all dependencies.

## Troubleshooting

1. **Signature Validation Failures**:

   - Ensure your Auth Token is correct
   - Check that the full URL (including protocol and querystring) matches what Twilio is calling
   - Request validation is disabled for localhost/development environments in the provided code

2. **WebSocket Connection Issues**:

   - Verify the WebSocket URL is correct
   - Check logs for any connection errors
   - Test the WebSocket endpoint independently

3. **No Audio Streaming**:
   - Verify the TwiML being generated is correct
   - Check the Twilio logs in the Twilio console for potential issues
   - Ensure your WebSocket server supports the Twilio WebSocket protocol

For any further issues, check the application logs in the `logs/` directory and Twilio's request logs in the Twilio console.
