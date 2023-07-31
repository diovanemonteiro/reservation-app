<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ActivityRegisterController extends Controller
{
    public function store(Activity $activity)
    {
        if (! auth()->check()) {
            return to_route('register', ['activity' => $activity->id]);
        }

        abort_if(auth()->user()->activities()->where('id', $activity->id)->exists(), response::http_conflict);

        auth()->user()->activities()->attach($activity->id);

        return to_route('my-activity.show')->with('success', 'You have successfully registered.');
    }
}
