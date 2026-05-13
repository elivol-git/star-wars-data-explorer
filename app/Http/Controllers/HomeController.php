<?php

namespace App\Http\Controllers;

use App\Models\Planet;
use Carbon\Carbon;

class HomeController extends Controller
{
    public function index()
    {
        $planets = Planet::with([
            'image',
            'films.image',
            'films.vehicles.image',
            'films.species.image',
            'films.starships.image',
            'people.image',
            'people.vehicles.image',
            'people.species.image',
            'people.starships.image',
        ])->paginate(12);

        $lastUpdated = Planet::max('updated_at');
        $lastUpdated = $lastUpdated ? Carbon::parse($lastUpdated) : null;

        return view('home', compact('planets', 'lastUpdated'));
    }

}
