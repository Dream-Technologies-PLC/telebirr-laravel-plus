<?php

namespace DreamTechnologies\TelebirrLaravelPlus\Http\Controllers;

use DreamTechnologies\TelebirrLaravelPlus\Contracts\TelebirrClient;
use DreamTechnologies\TelebirrLaravelPlus\DTO\CreateOrderData;
use DreamTechnologies\TelebirrLaravelPlus\Events\TelebirrNotificationReceived;
use DreamTechnologies\TelebirrLaravelPlus\Exceptions\TelebirrException;
use DreamTechnologies\TelebirrLaravelPlus\Http\Requests\CreateTelebirrOrderRequest;
use DreamTechnologies\TelebirrLaravelPlus\Http\Requests\QueryTelebirrOrderRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class TelebirrController extends Controller
{
    public function __construct(private readonly TelebirrClient $telebirr)
    {
    }

    public function createOrder(CreateTelebirrOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->telebirr->createOrder(new CreateOrderData(
                title: (string) $request->validated('title'),
                amount: $request->validated('amount'),
                merchantOrderId: $request->validated('merchantOrderId'),
                notifyUrl: $request->validated('notifyUrl'),
                redirectUrl: $request->validated('redirectUrl'),
                callbackInfo: $request->validated('callbackInfo'),
            ));

            return response()->json($order->toArray(), $order->success ? 200 : 502);
        } catch (TelebirrException $exception) {
            Log::channel(config('telebirr.log_channel') ?: config('logging.default'))
                ->error('Telebirr create-order failed', [
                    'code' => $exception->telebirrCode,
                    'message' => $exception->getMessage(),
                    'context' => $exception->context,
                ]);

            return response()->json([
                'success' => false,
                'code' => $exception->telebirrCode,
                'message' => $exception->getMessage(),
            ], 502);
        }
    }

    public function queryOrder(QueryTelebirrOrderRequest $request): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'raw' => $this->telebirr->queryOrder((string) $request->validated('merchantOrderId')),
            ]);
        } catch (TelebirrException $exception) {
            return response()->json([
                'success' => false,
                'code' => $exception->telebirrCode,
                'message' => $exception->getMessage(),
            ], 502);
        }
    }

    public function notify(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::channel(config('telebirr.log_channel') ?: config('logging.default'))
            ->info('Telebirr notify callback received', [
                'payload' => $payload,
            ]);

        event(new TelebirrNotificationReceived($payload));

        return response()->json([
            'success' => true,
            'message' => 'processed',
        ]);
    }
}
