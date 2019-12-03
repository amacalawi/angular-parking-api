<?php

namespace App\Api\V1\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Api\V1\Requests\CreateEventRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Auth;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Event\EventDay;
use App\Event\Event;

class EventController extends Controller
{
    private $carbon;

    public function __construct(Carbon $carbon)
    {
        $this->carbon = $carbon;
    }

    public function index() 
    {
        $events = Event::with(['days'])
        ->orderBy('created_at', 'desc')
        ->get();
        $eventDates = array();
        foreach ($events as $event) {
            $period = CarbonPeriod::create($event->start_date, $event->end_date);
            array_push($eventDates, $this->eventPeriod($period, $event->days));
        }

        $eventDates = sizeOf($eventDates) == 0 ? $eventDates : call_user_func_array('array_merge', $eventDates);
        return response()
        ->json([
            'status' => 'ok',
            'eventDates' => $eventDates
        ]);
    }

    private function eventPeriod($period, $days) {
        $dates = array();
        foreach ($period as $date) {
            if($this->getEventDays($days, $date)) {
                array_push($dates, $this->getEventDays($days, $date));
            }
        }
        $dates = sizeOf($dates) == 0 ? $dates : call_user_func_array('array_merge', $dates);
        return $dates;
    }

    private function getEventDays($days, $date) {
        $eventDates = array();
        foreach ($days as $day) {
            if($date->format('l') == $day->name) {
                array_push($eventDates, ['title' => $day->event->name, 'start' => $date->format('m-d-Y')]);
            }
        }
        return sizeOf($eventDates) == 0 ? null : $eventDates;
    }

    public function create(CreateEventRequest $request)
    {
        $userId = Auth::guard()->user()->id;
        if(sizeof($request['days']) == 0) {
            throw new NotFoundHttpException();
        }
        $request['created_by'] = $userId;
        $request['start_date'] = $this->carbon->parse($request['start_date']);
        $request['end_date'] = $this->carbon->parse($request['end_date']);
        $findEvent = Event::where('name', '=', $request['name'])
                    ->where('created_by', '=', $userId)->get();
        if(sizeof($findEvent) == 0) {
            $event = new Event();
            $event->name = $request['name'];
            $event->start_date = $request['start_date'];
            $event->end_date = $request['end_date'];
            $event->created_by = $userId;
            $event->save();
            if(!$event) {
                throw new HttpException(500);
            }
        } else {
            $event = Event::find($findEvent->first()->id);
            if(!$event) {
                throw new HttpException(500);
            }
            $event->start_date = $request['start_date'];
            $event->end_date = $request['end_date'];
            $event->created_by = $userId;
            $event->update();
        }
        $this->saveDays($request['days'], $event->id);
        return response()
        ->json([
            'status' => 'ok',
            'event' => $event
        ]);
    }

    private function saveDays($days, $eventId) {
        $cleanEventDays = EventDay::where('event_id', $eventId)->delete(); 
        foreach ($days as $day) {
            if($day['value']) {
                $newDay = new EventDay();
                $newDay->name = $day['name'];
                $newDay->event_id = $eventId;
                $newDay->save();
            }
        }
    }
}
