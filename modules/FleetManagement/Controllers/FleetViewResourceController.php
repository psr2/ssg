<?php

namespace Modules\FleetManagement\Controllers;

class FleetViewResourceController{

    public function index(){
        return view("fleet_management::fleet_dash");
    }

    public function routes(){
        return view("fleet_management::fleet_routes");
    }

    public function vehicles(){
        return view("fleet_management::fleet_vehicles");
    }
}

