<?php

namespace App\Http\Controllers;

use App\Event;
use App\Resource;
use App\Http\Resources\EventResource;
use Illuminate\Http\Request;

class EventController extends Controller
{

    public function getAllEvents()
    {
        // return response()->json(Event::all());
        return EventResource::collection(Event::all());
    }

    public function getEvent($id)
    {
        return new EventResource(Event::find($id));
    }

    public function getAllEventsInPeriod($start, $end)
    {
        $events = Event::whereBetween('start', [$start, $end])
                    ->orWhereBetween('end', [$start, $end])
                    ->get();
        
        return EventResource::collection($events);
    }

    public function create(Request $request)
    {
        $this->validate($request, [
            'resource_id'    => 'required',
            'title'         => 'required',
            'start'         => 'required'
        ]);

        $resource = Resource::find($request->resource_id);

        $event = $resource->events()->create($request->all());

        // $event = Event::create($request->all());

        return response()->json($event, 201);
    }

    public function update($id, Request $request)
    {
        $event = Event::findOrFail($id);
        $event->update($request->all());

        return response()->json($event, 200);
    }

    public function delete($id)
    {
        Event::findOrFail($id)->delete();
        return response('Deleted Successfully', 200);
    }
}