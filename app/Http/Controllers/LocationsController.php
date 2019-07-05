<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Location;
use Illuminate\Support\Facades\Validator;

class LocationsController extends Controller
{
    /**
     * @param Request $request
     * @return array
     * @return Response
     */
    public function addLocations(Request $request)
    {
        $data = [ 'data' => $request->all() ];
        $validator = Validator::make($data, [
            'data.*.name' => 'required|string',
            'data.*.lng' => 'required|numeric',
            'data.*.lat' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return ['status' => -1, 'msg' => $validator->errors()];
        }

        $allLocations = [];
        $now = Carbon::now('utc')->toDateTimeString();

        foreach($data['data'] as $item) {
            $location = new Location;
            $location->name = $item['name'];
            $location->lng = $item['lng'];
            $location->lat = $item['lat'];
            $location->created_at = $now;
            $location->updated_at = $now;
            $allLocations[] = $location->attributesToArray();
        }
        Location::insert($allLocations);

        return response()->json([
            'message' => "locations_have_been_added"
        ], 201);


    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOneNearestLocation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'lng' => 'required|numeric',
            'lat' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()
            ], 400);
        }

        $items = Location::all()->toArray();
        $checkedDistance = [$request['lat'], $request['lng']];

        $distances = array_map(function($item) use($checkedDistance) {
            $a = [$item['lat'], $item['lng']];
            return $this->countDistance($a, $checkedDistance);
        }, $items);
        asort($distances);
        $result = $items[key($distances)];

        if ($result) {
            return response()->json([
                'data' => $result
            ], 200);
        } else {
            return response()->json([
                'message' => 'no_locations_found'
            ], 204);
        }

    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNearestLocations(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'lng' => 'required|numeric',
            'lat' => 'required|numeric',
            'threshold' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()
            ], 400);
        }

        $items = Location::all()->toArray();
        $checkedDistance = [$request['lat'], $request['lng']];
        $kmDistance = $request['threshold'];
        $resultArr = [];

        $itemsWithDistances = array_map(function($item) use($checkedDistance) {
            $a = [$item['lat'], $item['lng']];
            $item['kmDistance'] = $this->countDistance($a, $checkedDistance);
            return $item;
        }, $items);
        asort($itemsWithDistances);

        foreach ($itemsWithDistances as $item) {
            if ($item['kmDistance'] <=$kmDistance) {
                unset($item['kmDistance']);
                $resultArr[] = $item;
            }
        }

        if ($resultArr) {
            return response()->json([
                'data' => $resultArr
            ], 200);
        } else {
            return response()->json([
                'message' => 'no_locations_meeting_specified_criteria'
            ], 204);
        }
    }

    /**
     * @param $a
     * @param $b
     * @return float
     */
    private function countDistance($a, $b)
    {
        list($lat1, $lng1) = $a;
        list($lat2, $lng2) = $b;
        $theta = $lng1 - $lng2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $km = $dist * 60 * 1.1515 * 1.609344;

        return $km;
    }
}
