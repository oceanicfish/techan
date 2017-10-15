<?php

namespace App\Http\Controllers;

use App\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use phpDocumentor\Reflection\Types\Object_;

class RoutesController extends Controller
{
    //

    /**
     * @param Request $request
     * @return string
     */
    public function route(Request $request) {

        $GOOGLE_MAP_API_KEY = 'AIzaSyANIB-aGfcYsvNjLrVFrq_PzYctaeItr_k';

        $waypoints = (Array) str_replace("\"","'", $request->json()->all());

        $waypoints_str = str_replace("\"","", json_encode($waypoints, JSON_UNESCAPED_UNICODE));

        /**
         * if there's no origin or destinations
         */
        if (count($waypoints) < 1 ) {
            dd(json_decode("{ \"error\": \"NO ORIGIN\" }", true));
        }
        if (count($waypoints) < 2 ) {
            dd(json_decode("{ \"error\": \"NO DESTINATIONS\" }", true));
        }

        $token = Password::getRepository()->createNewToken();

        $origin = '';
        $destinations = '';

        for ($i = 0; $i < count($waypoints); $i++) {

            if ($i == 0) {
                $origin = $waypoints[$i][0] . ',' . $waypoints[$i][1];
            }else {
                $destinations .= $waypoints[$i][0] . ',' . $waypoints[$i][1] . '|';
            }

        }

        $client = new Client();
        $url = 'https://maps.googleapis.com/maps/api/distancematrix/json?origins=' . $origin .
            '&destinations=' . $destinations .
            '&departure_time='.
            '&traffic_model='.
            '&key=' . $GOOGLE_MAP_API_KEY;
        $res = $client->get($url);

        /**
         * if some errors come from google map api
         */
        if ($res->getStatusCode() != 200) {
            dd(json_decode("{ \"error\": " . $res->getStatusCode() . " - " . $res->getBody() . "}", true));
        }

        $result = (Object) json_decode($res->getBody(), true);
        $elements = $result->rows[0];
        $steps = $elements["elements"];

        $total_distance = 0;
        $total_duration = 0;

        foreach ($steps as $step) {
            $total_distance += $step["distance"]["value"];
            $total_duration += $step["duration"]["value"];
        }

        Route::create([
            'path' => $waypoints_str,
            'total_distance' => $total_distance."",
            'total_time' => $total_duration."",
            'token' => $token
        ]);

        return json_encode("{'token':'". $token ."''}", JSON_UNESCAPED_UNICODE);
    }


    public function getRoute($token) {

        $route = Route::all()->where('token', $token);
        return json_encode($route, JSON_UNESCAPED_UNICODE);
    }

}
