<?php

namespace Modules\Volunteer\Services;

/**
 * 地址解析经纬度
 */
class GeocodeService
{
    /**
     * 本地关键词兜底（无外网地图 Key / 外网不通时仍可导航）
     * 坐标为重庆市沙坪坝区附近示意点，可按实际社区调整
     */
    private static array $localFallback = [
        '万寿山' => ['longitude' => 106.4568, 'latitude' => 29.5632],
        '沙坪坝' => ['longitude' => 106.4542, 'latitude' => 29.5412],
        '重庆' => ['longitude' => 106.5516, 'latitude' => 29.5630],
    ];

    /**
     * @return array{longitude: float, latitude: float}|null
     */
    public static function geocode(string $address): ?array
    {
        $address = trim($address);
        if ($address === '') {
            return null;
        }

        $local = self::localFallback($address);
        if ($local) {
            // 有高德 Key 时仍优先在线解析，失败再回落本地
            $amapKey = (string) site_config('map.amap_web_key', '');
            if ($amapKey !== '') {
                foreach (self::candidates($address) as $candidate) {
                    $result = self::amapGeocode($candidate, $amapKey);
                    if ($result) {
                        return $result;
                    }
                }
            }
            return $local;
        }

        $amapKey = (string) site_config('map.amap_web_key', '');
        foreach (self::candidates($address) as $candidate) {
            if ($amapKey !== '') {
                $result = self::amapGeocode($candidate, $amapKey);
                if ($result) {
                    return $result;
                }
            }
        }

        // 外网 Nominatim 仅尝试一次，短超时，避免拖垮接口
        foreach (self::candidates($address) as $candidate) {
            $result = self::nominatimGeocode($candidate);
            if ($result) {
                return $result;
            }
            break;
        }

        return self::localFallback($address);
    }

    private static function candidates(string $address): array
    {
        $candidates = [$address];
        if (!preg_match('/^(北京|上海|天津|重庆|香港|澳门|新疆|西藏|内蒙古|广西|宁夏|[\x{4e00}-\x{9fa5}]{2,}省)/u', $address)) {
            $candidates[] = '重庆市' . $address;
        }
        return array_values(array_unique($candidates));
    }

    private static function localFallback(string $address): ?array
    {
        foreach (self::$localFallback as $keyword => $point) {
            if (str_contains($address, $keyword)) {
                return [
                    'longitude' => $point['longitude'],
                    'latitude' => $point['latitude'],
                ];
            }
        }
        return null;
    }

    private static function amapGeocode(string $address, string $key): ?array
    {
        $url = 'https://restapi.amap.com/v3/geocode/geo?' . http_build_query([
            'address' => $address,
            'key' => $key,
        ]);
        $json = self::httpGetJson($url, [], 4);
        if (!$json || ($json['status'] ?? '') !== '1') {
            return null;
        }
        $location = $json['geocodes'][0]['location'] ?? '';
        if (!$location || !str_contains($location, ',')) {
            return null;
        }
        [$lng, $lat] = explode(',', $location);
        return [
            'longitude' => round((float) $lng, 7),
            'latitude' => round((float) $lat, 7),
        ];
    }

    private static function nominatimGeocode(string $address): ?array
    {
        $url = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
            'q' => $address,
            'format' => 'json',
            'limit' => 1,
        ]);
        $json = self::httpGetJson($url, [
            'User-Agent: VolunteerAdmin/1.0 (merchant-geocode)',
            'Accept-Language: zh-CN',
        ], 3);
        if (!$json || empty($json[0]['lon']) || empty($json[0]['lat'])) {
            return null;
        }
        return [
            'longitude' => round((float) $json[0]['lon'], 7),
            'latitude' => round((float) $json[0]['lat'], 7),
        ];
    }

    private static function httpGetJson(string $url, array $headers = [], int $timeout = 4): ?array
    {
        try {
            $headerLines = array_merge(['Accept: application/json'], $headers);
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => $timeout,
                    'header' => implode("\r\n", $headerLines) . "\r\n",
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ]);
            $raw = @file_get_contents($url, false, $context);
            if ($raw === false || $raw === '') {
                return null;
            }
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
