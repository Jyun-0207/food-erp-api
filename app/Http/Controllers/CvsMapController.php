<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CvsMapController extends Controller
{
    private const EMAP_URL = 'https://emap.pcsc.com.tw/EMapSDK.aspx';

    /**
     * Search 7-11 stores (proxy to emap API).
     */
    public function search(Request $request)
    {
        try {
            $formData = [
                'commandid' => $request->input('commandid', 'SearchStore'),
            ];

            foreach (['city', 'town', 'roadname', 'storename', 'storeid'] as $field) {
                if ($value = $request->input($field)) {
                    $formData[$field] = $value;
                }
            }

            $response = Http::asForm()->post(self::EMAP_URL, $formData);
            $xmlText = $response->body();

            $stores = $this->parseStoresXML($xmlText);

            return response()->json(['success' => true, 'stores' => $stores]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => '無法取得門市資料'], 500);
        }
    }

    /**
     * GET endpoint dispatcher for cities, towns, and roads.
     */
    public function query(Request $request)
    {
        $action = $request->input('action');

        return match ($action) {
            'getCities' => $this->getCities($request),
            'getTowns' => $this->getTowns($request),
            'getRoads' => $this->getRoads($request),
            default => response()->json(['success' => false, 'error' => '無效的請求'], 400),
        };
    }

    public function getCities(Request $request)
    {
        $action = $request->input('action');

        try {
            if ($action === 'getCities') {
                $cities = [
                    ['id' => '01', 'name' => '台北市'],
                    ['id' => '02', 'name' => '基隆市'],
                    ['id' => '03', 'name' => '新北市'],
                    ['id' => '04', 'name' => '桃園市'],
                    ['id' => '05', 'name' => '新竹市'],
                    ['id' => '06', 'name' => '新竹縣'],
                    ['id' => '07', 'name' => '苗栗縣'],
                    ['id' => '08', 'name' => '台中市'],
                    ['id' => '10', 'name' => '彰化縣'],
                    ['id' => '11', 'name' => '南投縣'],
                    ['id' => '12', 'name' => '雲林縣'],
                    ['id' => '13', 'name' => '嘉義市'],
                    ['id' => '14', 'name' => '嘉義縣'],
                    ['id' => '15', 'name' => '台南市'],
                    ['id' => '17', 'name' => '高雄市'],
                    ['id' => '19', 'name' => '屏東縣'],
                    ['id' => '20', 'name' => '台東縣'],
                    ['id' => '21', 'name' => '花蓮縣'],
                    ['id' => '22', 'name' => '宜蘭縣'],
                    ['id' => '23', 'name' => '澎湖縣'],
                    ['id' => '24', 'name' => '金門縣'],
                    ['id' => '25', 'name' => '連江縣'],
                ];

                return response()->json(['success' => true, 'cities' => $cities]);
            }

            return response()->json(['success' => false, 'error' => '無效的請求'], 400);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => '無法取得資料'], 500);
        }
    }

    /**
     * GET towns for a given city.
     */
    public function getTowns(Request $request)
    {
        $cityid = $request->input('cityid');

        if (!$cityid) {
            return response()->json(['success' => false, 'error' => '請提供縣市 ID'], 400);
        }

        try {
            $response = Http::asForm()->post(self::EMAP_URL, [
                'commandid' => 'GetTown',
                'cityid' => $cityid,
            ]);

            $xmlText = $response->body();
            $towns = $this->parseTownsXML($xmlText);

            return response()->json(['success' => true, 'towns' => $towns]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => '無法取得資料'], 500);
        }
    }

    /**
     * GET roads for a given city and town.
     */
    public function getRoads(Request $request)
    {
        $city = $request->input('city');
        $town = $request->input('town');

        if (!$city || !$town) {
            return response()->json(['success' => false, 'error' => '請提供縣市和鄉鎮區'], 400);
        }

        try {
            $response = Http::asForm()->post(self::EMAP_URL, [
                'commandid' => 'SearchStore',
                'city' => $city,
                'town' => $town,
            ]);

            $xmlText = $response->body();
            $stores = $this->parseStoresXML($xmlText);

            // Extract road names from store addresses
            $roadSet = [];
            foreach ($stores as $store) {
                if (!empty($store['POIAddress'])) {
                    if (preg_match('/([^區鄉鎮市縣]+(?:路|街|大道|巷|弄))/', $store['POIAddress'], $roadMatch)) {
                        $roadSet[$roadMatch[1]] = true;
                    }
                }
            }

            $roads = array_map(
                fn($name) => ['RoadName' => $name],
                array_keys($roadSet)
            );
            sort($roads);

            return response()->json(['success' => true, 'roads' => $roads]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => '無法取得資料'], 500);
        }
    }

    /**
     * Parse stores XML from 7-11 API response.
     */
    private function parseStoresXML(string $xml): array
    {
        $stores = [];

        if (preg_match_all('/<GeoPosition>[\s\S]*?<\/GeoPosition>/', $xml, $storeMatches)) {
            foreach ($storeMatches[0] as $storeXml) {
                $getValue = function (string $tag) use ($storeXml): string {
                    if (preg_match("/<{$tag}>([^<]*)<\/{$tag}>/", $storeXml, $match)) {
                        return trim($match[1]);
                    }
                    return '';
                };

                $stores[] = [
                    'POIID' => $getValue('POIID'),
                    'POIName' => $getValue('POIName'),
                    'Telno' => $getValue('Telno'),
                    'FaxNo' => $getValue('FaxNo'),
                    'POIAddress' => $getValue('Address'),
                    'Latitude' => $getValue('Y'),
                    'Longitude' => $getValue('X'),
                    'StoreImageTitle' => $getValue('StoreImageTitle'),
                ];
            }
        }

        return $stores;
    }

    /**
     * Parse towns XML from 7-11 API response.
     */
    private function parseTownsXML(string $xml): array
    {
        $towns = [];

        if (preg_match_all('/<TownName>([^<]*)<\/TownName>/', $xml, $matches)) {
            foreach ($matches[1] as $townName) {
                if ($townName) {
                    $towns[] = ['TownName' => $townName];
                }
            }
        }

        return $towns;
    }
}
