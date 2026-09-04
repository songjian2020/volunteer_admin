<?php

namespace Modules\Volunteer\Services;

/**
 * 地址解析经纬度
 */
class GeocodeService
{
    /**
     * @return array{longitude: float, latitude: float}|null
     */
    public static function geocode(string $address): ?array
    {
        $address = trim($address);
        if ($address === '') {
            return null;
        }

        $amapKey = (string) site_config('map.amap_web_key', '');
        if ($amapKey !== '') {
            $result = self::amapGeocode($address, $amapKey);
            if ($result) {
                return $result;
            }
        }

        return self::nominatimGeocode($address);
    }

    private static function amapGeocode(string $address, string $key): ?array
    {
        $url = 'https://restapi.amap.com/v3/geocode/geo?' . http_build_query([
            'address' => $address,
            'key' => $key,
        ]);
        $json = self::httpGetJson($url);
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
        ]);
        if (!$json || empty($json[0]['lon']) || empty($json[0]['lat'])) {
            return null;
        }
        return [
            'longitude' => round((float) $json[0]['lon'], 7),
            'latitude' => round((float) $json[0]['lat'], 7),
        ];
    }

    private static function httpGetJson(string $url, array $headers = []): ?array
    {
        try {
            $headerLines = array_merge(['Accept: application/json'], $headers);
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 8,
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
