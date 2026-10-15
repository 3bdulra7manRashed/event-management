<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Http\Resources\AttendeeResource;
use App\Models\Event;
class AttendeeController extends Controller implements HasMiddleware

{
    public static function middleware(): array
    {
        return [
            new Middleware('auth:sanctum', except: ['index']),
        ];
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Event $event): AnonymousResourceCollection
    {
        $attendees = $event->attendees()->with('user')->paginate();

        return AttendeeResource::collection($attendees);
        
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Event $event): JsonResponse
    {
        abort_if(
            $event->attendees()->where('user_id', $request->user()->id)->exists(),
            409,
            'You are already registered for this event.'
        );

        $attendee = $event->attendees()->create([
            'user_id' => $request->user()->id,
        ]);

        return (new AttendeeResource($attendee->load('user')))
        ->response()
        ->setStatusCode(201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
