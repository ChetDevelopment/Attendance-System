<?php

namespace App\Http\Controllers;

use App\Services\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramController extends Controller
{
    public function __construct(
        private readonly TelegramService $telegramService
    ) {
    }

    /**
     * Test Telegram connection
     * POST /api/telegram/test
     */
    public function testConnection(): JsonResponse
    {
        Log::info('Telegram test connection requested');

        // Check if Telegram is configured
        if (!$this->telegramService->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Telegram is not configured',
                'hint' => 'Please set TELEGRAM_BOT_TOKEN and TELEGRAM_CHAT_ID in .env file. Note: chat_id must be your personal Telegram user ID (not the bot username or another bot ID).',
                'configured' => false,
            ], 400);
        }

        // Try to get bot info
        $botInfo = $this->telegramService->getBotInfo();

        if (!$botInfo['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to connect to Telegram',
                'error' => $botInfo['error'] ?? 'Unknown error',
                'configured' => true,
            ], 400);
        }

        // Send test message
        $result = $this->telegramService->sendTestMessage();

        // Provide specific error message for bot-to-bot chat
        if (!$result['success'] && ($result['error'] ?? '') === 'Forbidden: bots can\'t send messages to bots') {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Chat ID - Cannot send to another bot',
                'error' => 'The TELEGRAM_CHAT_ID is set to a bot ID. You must use a personal user chat ID instead. To get your user ID: 1) Start a chat with @userinfobot on Telegram, or 2) Add your bot to a group and use @userinfobot to get the group chat ID.',
                'configured' => true,
            ], 400);
        }

        return response()->json([
            'success' => $result['success'],
            'message' => $result['success'] 
                ? 'Test message sent successfully!' 
                : 'Failed to send test message',
            'error' => $result['error'] ?? null,
            'bot_info' => $botInfo['bot_info'] ?? null,
            'configured' => true,
        ], $result['success'] ? 200 : 400);
    }

    /**
     * Send a custom test message
     * POST /api/telegram/send
     */
    public function sendTestMessage(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:4096',
        ]);

        Log::info('Telegram custom message requested', [
            'message_length' => strlen($request->message)
        ]);

        if (!$this->telegramService->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Telegram is not configured',
                'hint' => 'Please set TELEGRAM_BOT_TOKEN and TELEGRAM_CHAT_ID in .env file',
            ], 400);
        }

        $result = $this->telegramService->sendMessage($request->message);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['success'] 
                ? 'Message sent successfully!' 
                : 'Failed to send message',
            'error' => $result['error'] ?? null,
            'message_id' => $result['message_id'] ?? null,
        ], $result['success'] ? 200 : 400);
    }
}
