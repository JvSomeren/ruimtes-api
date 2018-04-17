<?php

namespace App\Http\Controllers;

use App\Resource;
use Illuminate\Http\Request;

class ResourceController extends Controller
{

    public function getAllResources()
    {
        return response()->json(Resource::all());
    }

    public function getResource($id)
    {
        return response()->json(Resource::find($id));
    }

    public function create(Request $request)
    {
        $this->validate($request, [
            'title' => 'required'
        ]);

        $resource = Resource::create($request->all());

        return response()->json($resource, 201);
    }

    public function update($id, Request $request)
    {
        $resource = Resource::findOrFail($id);
        $resource->update($request->all());

        return response()->json($resource, 200);
    }

    public function delete($id)
    {
        Resource::findOrFail($id)->delete();
        return response('Deleted Successfully', 200);
    }
}