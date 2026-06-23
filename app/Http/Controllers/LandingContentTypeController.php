<?php

namespace App\Http\Controllers;

use App\Http\Resources\LandingContentTypeReResource;
use App\Models\LandingContentType;
use Illuminate\Http\Resources\Json\JsonResource;

class LandingContentTypeController extends Controller
{
    public function index(): JsonResource
    {
        $query = LandingContentType::orderBy('id', 'asc');

        if (request()->has('page')) {
            $resp = $query->paginate(10)->onEachSide(1);
        } else {
            $resp = $query->get();
        }

        return LandingContentTypeReResource::collection($resp);

    }
}
