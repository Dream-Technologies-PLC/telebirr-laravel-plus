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
        if ($payload === []) {
            $decoded = json_decode((string) $request->getContent(), true);
            $payload = is_array($decoded) ? $decoded : [];
        }

        try {
            $notification = $this->telebirr->verifyNotification($payload);
        } catch (TelebirrException $exception) {
            Log::channel(config('telebirr.log_channel') ?: config('logging.default'))
                ->warning('Telebirr notify callback verification failed', [
                    'code' => $exception->telebirrCode,
                    'message' => $exception->getMessage(),
                ]);

            return $this->notifyResponse(false);
        }

        Log::channel(config('telebirr.log_channel') ?: config('logging.default'))
            ->info('Telebirr notify callback received', [
                'merchant_order_id' => $notification->merchantOrderId,
                'payment_order_id' => $notification->paymentOrderId,
                'transaction_id' => $notification->transactionId,
                'status' => $notification->status,
                'signature_status' => $notification->signatureStatus,
            ]);

        if (! $notification->accepted || $notification->merchantOrderId === null) {
            return $this->notifyResponse(false);
        }

        event(new TelebirrNotificationReceived($payload, $notification));

        return $this->notifyResponse(true);
    }

    private function notifyResponse(bool $success): JsonResponse
    {
        return response()->json([
            'success' => $success,
            'result' => $success ? 'SUCCESS' : 'FAIL',
            'code' => $success ? '0' : '1',
            'msg' => $success ? 'Success' : 'Failed',
        ], 200);
    }
}
