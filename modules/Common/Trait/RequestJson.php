<?php
namespace Modules\Common\Trait;

use App\Exceptions\HttpResponseException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\JsonResponse;
use Modules\Common\Enum\ShowType as ShopTypeEnum;

/**
 * 响应 trait
 * 支持 throw 响应
 */
trait RequestJson
{
    /**
     * 将 Model/Collection 等转为数组，避免被当成 msg 字符串
     */
    protected function normalizeResponseData(mixed $data): array|string
    {
        if ($data instanceof Arrayable) {
            return $data->toArray();
        }
        if ($data instanceof \JsonSerializable) {
            $serialized = $data->jsonSerialize();
            return is_array($serialized) ? $serialized : ['value' => $serialized];
        }
        if (is_array($data)) {
            return $data;
        }
        if (is_object($data)) {
            $encoded = json_decode(json_encode($data, JSON_UNESCAPED_UNICODE), true);
            return is_array($encoded) ? $encoded : [];
        }
        return (string) $data;
    }

    /**
     *  成功响应
     *
     * @param  mixed  $data  响应数据（支持 array/Model/Collection/string）
     * @param  string  $message  响应内容
     */
    protected function success(mixed $data = [], string $message = 'ok'): JsonResponse
    {
        $payload = $this->normalizeResponseData($data);
        if (is_array($payload)) {
            return self::renderJson(true, $payload, $message);
        }

        return self::renderJson(true, [], $payload);
    }

    /**
     * 抛出成功响应，中断程序运行
     *
     * @param  mixed  $data  响应数据
     * @param  string  $message  响应内容
     */
    protected function throwSuccess(mixed $data = [], string $message = 'ok'): void
    {
        $payload = $this->normalizeResponseData($data);
        if (is_array($payload)) {
            self::renderThrow(true, $payload, $message);
        }
        self::renderThrow(true, [], is_string($payload) ? $payload : 'ok');
    }

    /**
     *  返回失败响应
     *
     * @param  mixed  $data  响应数据
     * @param  string  $message  响应内容
     */
    protected function error(mixed $data = [], string $message = ''): JsonResponse
    {
        $payload = $this->normalizeResponseData($data);
        if (is_array($payload)) {
            return self::renderJson(false, $payload, $message, ShopTypeEnum::ERROR_MESSAGE);
        }

        return self::renderJson(false, [], $payload, ShopTypeEnum::ERROR_MESSAGE);
    }

    /**
     * 抛出失败响应，中断程序运行
     *
     * @param  mixed  $data  响应数据
     * @param  string  $message  响应内容
     */
    protected function throwError(mixed $data = [], string $message = ''): void
    {
        $payload = $this->normalizeResponseData($data);
        if (is_array($payload)) {
            self::renderThrow(false, $payload, $message, ShopTypeEnum::ERROR_MESSAGE);
        }
        self::renderThrow(false, [], is_string($payload) ? $payload : '', ShopTypeEnum::ERROR_MESSAGE);
    }

    /**
     *  返回警告响应
     *
     * @param  mixed  $data  响应数据
     * @param  string  $message  响应内容
     */
    protected function warn(mixed $data = [], string $message = ''): JsonResponse
    {
        $payload = $this->normalizeResponseData($data);
        if (is_array($payload)) {
            return self::renderJson(false, $payload, $message, ShopTypeEnum::WARN_MESSAGE);
        }

        return self::renderJson(false, [], $payload, ShopTypeEnum::WARN_MESSAGE);
    }

    /**
     * 抛出失败警告，中断程序运行
     *
     * @param  mixed  $data  响应数据
     * @param  string  $message  响应内容
     */
    protected function throwWarn(mixed $data = [], string $message = ''): void
    {
        $payload = $this->normalizeResponseData($data);
        if (is_array($payload)) {
            self::renderThrow(false, $payload, $message, ShopTypeEnum::WARN_MESSAGE);
        }
        self::renderThrow(false, [], is_string($payload) ? $payload : '', ShopTypeEnum::WARN_MESSAGE);
    }

    /**
     * 通知响应
     *
     * @param  string  $msg  通知标题
     * @param  string  $description  通知描述
     * @param  string  $placement  通知位置 top topLeft topRight bottom bottomLeft bottomRight
     * @param  ShopTypeEnum  $showTypeEnum  通知类型
     */
    protected function notification(
        string $msg,
        string $description,
        ShopTypeEnum $showTypeEnum = ShopTypeEnum::SUCCESS_NOTIFICATION,
        string $placement = 'topRight'
    ): JsonResponse {
        $showType = $showTypeEnum->value;
        $success = false;
        return response()->json(compact('description', 'success', 'msg', 'showType', 'placement'));
    }

    /**
     *  返回 Json 响应
     *
     * @param  bool  $success  响应状态
     * @param  array  $data  响应数据
     * @param  string  $msg  响应内容
     */
    protected static function renderJson(
        bool $success = true,
        array $data = [],
        string $msg = '',
        ShopTypeEnum $showTypeEnum = ShopTypeEnum::SUCCESS_MESSAGE
    ): JsonResponse {
        $showType = $showTypeEnum->value;

        return response()->json(compact('data', 'success', 'msg', 'showType'));
    }

    /**
     *  抛出 API 数据
     *
     * @param  bool  $success  响应状态
     * @param  mixed  $data  返回数据
     * @param  string  $msg  响应内容
     * @param  ShopTypeEnum $showTypeEnum
     */
    public static function renderThrow(
        bool $success = true,
        array $data = [],
        string $msg = '',
        ShopTypeEnum $showTypeEnum = ShopTypeEnum::SUCCESS_MESSAGE
    ) {
        $showType = $showTypeEnum->value;
        throw new HttpResponseException(compact('data', 'success', 'msg', 'showType'));
    }
}
