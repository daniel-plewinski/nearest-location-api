<?php

namespace Tests\Feature;

use App\Location;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LocationTest extends TestCase
{
    use RefreshDatabase;

    private $locations = [
        [
            "name"=> "Lokalizacja 1",
            "lat"=> 21.132312,
            "lng"=> 21.132312
        ],
        [
            "name"=> "Lokalizacja 2",
            "lat"=> 42.03452,
            "lng"=> 31.3128
        ],
        [
            "name"=> "Lokalizacja 3",
            "lat" => 25.23161,
            "lng"=> 11.23161
        ],
    ];

    /**
     * @test
     * @return void
     */
    public function can_add_locations()
    {
        $locations = factory(Location::class, 10)->create()->toArray();

        $this->json('post', '/api/locations/add', $locations)
            ->assertStatus(201)
            ->assertJson([
                'message' => "locations_have_been_added"
            ]);
    }

    /**
     * @test
     * @return void
     */
    public function require_name_lng_lat_on_add()
    {
        $locations = factory(Location::class, 1)->create()->toArray();
        $locations[0]['name'] = '';
        $locations[0]['lng'] = '';
        $locations[0]['lat'] = '';

        $this->json('post', '/api/locations/add', $locations)
            ->assertStatus(400)
            ->assertJson([
              'message'=> [
                  "data.0.name"=> ["The data.0.name field is required."],
                  "data.0.lng"=> ["The data.0.lng field is required."],
                  "data.0.lat"=> ["The data.0.lat field is required."]
              ]
            ]);
    }

    /**
     * @test
     * @return void
     */
    public function get_one_nearest_location()
    {
        $this->json('post', '/api/locations/add', $this->locations);

        $testedLocation = [
            "name"=> "Imię użytkownika",
            "lat"=> 42.2312,
            "lng"=> 30.723
        ];

        $this->json('get', '/api/locations/one-nearest', $testedLocation)
            ->assertStatus(200)
            ->assertJson([
                'data'=> [
                    "name"=> "Lokalizacja 2",
                    "lat"=> 42.03452,
                    "lng"=> 31.3128
                ]
            ]);
    }

    /**
     * @test
     * @return void
     */
    public function require_name_lat_lng_on_get_one_nearest_location()
    {
        $this->json('post', '/api/locations/add', $this->locations);

        $testedLocation = [
            "name"=> "",
            "lat"=> "",
            "lng"=> ""
        ];

        $this->json('get', '/api/locations/one-nearest', $testedLocation)
            ->assertStatus(400)
            ->assertJson([
                'message'=> [
                    "name"=> ["The name field is required."],
                    "lng"=> ["The lng field is required."],
                    "lat"=> ["The lat field is required."]
                ]
            ]);
    }

    /**
     * @test
     * @return void
     */
    public function get_nearest_locations()
    {
        $this->json('post', '/api/locations/add', $this->locations);

        $testedLocation = [
            "name"=> "Imię użytkownika",
            "lat"=> 42.2312,
            "lng"=> 30.723,
            "threshold" => 4000
        ];

        $this->json('get', '/api/locations/nearest', $testedLocation)
            ->assertStatus(200)
            ->assertJson([
                "data" => [
                    [
                        'name' => 'Lokalizacja 1',
                        "lng" => '21.13231200',
                        "lat" => '21.1323120',
                    ],
                    [
                        "name"=> "Lokalizacja 2",
                        "lng"=> "31.31280000",
                        "lat"=>"42.0345200",
                    ]
                ]
            ]);
    }

    /**
     * @test
     * @return void
     */
    public function require_name_lat_lng_surname_on_get_one_nearest_location()
    {
        $this->json('post', '/api/locations/add', $this->locations);

        $testedLocation = [
            'name' => '',
            'lng' => '',
            'lat' => '',
            'threshold' => '',
        ];

        $this->json('get', '/api/locations/nearest', $testedLocation)
            ->assertStatus(400)
            ->assertJson([
                'message'=> [
                    "name"=> ["The name field is required."],
                    "lng"=> ["The lng field is required."],
                    "lat"=> ["The lat field is required."],
                    "threshold"=> ["The threshold field is required."]
                ]
            ]);
    }
}
