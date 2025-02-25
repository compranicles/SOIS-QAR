<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Event;
use App\Models\EventImage;
use App\Models\EventCategory;
use App\Models\EventRole;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use Intervention\Image\Facades\Image;

use Carbon\Carbon;

class EventsController extends Controller
{
    public function index()
    {
        $allEventYears = Event::selectRaw('YEAR(`start_date`) as year')
            ->where('organization_id', Auth::user()->course->organization_id,)
            ->groupBy('year')
            ->orderBy('year', 'DESC')
            ->get();
        $orgAcronym = Auth::user()->course->organization->organization_acronym;
        $events = array();
        foreach($allEventYears as $year) 
        {
            $yearEvents = Event::whereRaw('YEAR(`start_date`) = ?', $year->year)
                ->where('organization_id', Auth::user()->course->organization_id,)
                ->orderByRaw('MONTH(`start_date`) ASC, `start_date` ASC')
                ->get();
            $events[$year->year] = $yearEvents; 
        }

        return view('events.index', compact('events', 'orgAcronym'));
    }
    public function show($event_slug)
    {
        /*
         * Shows the Specific Event Details
         */
        if($event = Event::where('slug', $event_slug)->first())
        {
            $event->event_category = EventCategory::where('event_category_id', $event->event_category_id)->value('category');
            $event->event_role = EventRole::where('event_role_id', $event->event_role_id)->value('event_role');
            // some colors
            if ($event->event_category == 'Academic')
                $event->category_color = 'primary';
            elseif ($event->event_category == 'Non-academic') 
                $event->category_color = 'danger';
            elseif ($event->event_category == 'Cultural') 
                $event->category_color = 'warning';
            elseif ($event->event_category == 'Sports') 
                $event->category_color = 'success';

            if ($event->event_role == 'Organizer')
                $event->role_color = 'primary';
            elseif ($event->event_role == 'Sponsor') 
                $event->role_color = 'success';
            elseif ($event->event_role == 'Participant') 
                $event->role_color = 'secondary';

            $eventImages = EventImage::where('event_id', $event->event_id)->get();
            $eventDocuments = DB::table('event_documents as documents')
            ->join('event_document_types as types','documents.event_document_type_id','=','types.event_document_type_id')
            ->where('documents.event_id', $event->event_id)
            ->whereNull('deleted_at')
            ->orderBy('documents.event_document_type_id', 'ASC')
            ->select('types.document_type as document_type', 'documents.title as title')
            ->get();
            return view('events.show',compact('event', 'eventImages', 'eventDocuments'));
        }
        else
            abort(404);
    }
    public function edit($event_slug)
    {
        /*
         * Open up Edit Page for an Event
         */
        if($event = Event::where('slug', $event_slug)->first())
        {
            $event_categories = EventCategory::all();
            $event_roles = EventRole::all();
            return view('events.edit',compact('event', 'event_categories', 'event_roles'));
        }
        else
            abort(404);
    }
    public function update($event_slug)
    {
        /*
         * Recieve POST request from Edit Page
         */
        $data = request()->validate([
            'title' => 'required|string|min:2|max:250',
            'description' => 'required|string',
            'objective' => 'required|string',
            'start_date' => 'required|date|date_format:Y-m-d|before_or_equal:now|after:1992-01-01',
            'end_date' => 'required|date|date_format:Y-m-d|after_or_equal:start_date|before_or_equal:now|after:1992-01-01',
            'start_time' => 'date_format:H:i',
            'end_time' => 'date_format:H:i|after_or_equal:start_time',
            'venue' => 'required|string|min:2|max:250',
            'activity_type' => 'required|string|min:2|max:250',
            'beneficiaries' => 'required|string|min:2|max:250',
            'sponsors' => 'required|string|min:2|max:250',
            'budget' => 'nullable|numeric',
            'event_role' => 'required|exists:event_roles,event_role_id',
            'event_category' => 'required|exists:event_categories,event_category_id',
        ]);
        $new_event_slug = Str::slug($data['title'], '-') . '-' . Carbon::parse($data['start_date'])->format('Y') . '-' . Str::uuid();
        $event_data = [
            'event_role_id' => $data['event_role'],
            'event_category_id' => $data['event_category'],
            'title' => $data['title'],
            'description' => $data['description'],
            'objective' => $data['objective'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'venue' => $data['venue'],
            'activity_type' => $data['activity_type'],
            'beneficiaries' => $data['beneficiaries'],
            'sponsors' => $data['sponsors'],
            'budget' => $data['budget'],
            'slug' => $new_event_slug,
        ];

        if ($event = Event::where('slug', $event_slug)->update($event_data))
        {
            return redirect()->route('event.show',['event_slug' => $new_event_slug,]);
        }
        // todo: db error handling
        else
            abort(404);
    }
    public function destroy($event_slug)
    {
        if ($event = Event::where('slug', $event_slug)->first())
        {
            if ($event->delete())
                return redirect()->route('event.index');
            else
               abort(404); 
        }
        else
            abort(404);
    }
    public function create()
    {
        $event_categories = EventCategory::all();
        $event_roles = EventRole::all();
    	return view('events.create', compact('event_categories', 'event_roles'));
    }
    public function store()
    {
    	$data = request()->validate([
    		'title' => 'required|string|min:2|max:250',
    		'description' => 'required|string',
    		'objective' => 'required|string',
    		'start_date' => 'required|date|date_format:Y-m-d|before_or_equal:now|after:1992-01-01',
            'end_date' => 'required|date|date_format:Y-m-d|after_or_equal:start_date|before_or_equal:now|after:1992-01-01',
    		'start_time' => 'date_format:H:i',
    		'end_time' => 'date_format:H:i|after_or_equal:start_time',
    		'venue' => 'required|string|min:2|max:250',
    		'activity_type' => 'required|string|min:2|max:250',
    		'beneficiaries' => 'required|string|min:2|max:250',
    		'sponsors' => 'required|string|min:2|max:250',
    		'budget' => 'nullable|numeric',
            'event_role' => 'required|exists:event_roles,event_role_id',
            'event_category' => 'required|exists:event_categories,event_category_id',
    	]);
        $organization_id = Auth::user()->positionTitles->whereIn('position_title', ['Vice President for Research and Documentation', 'Assistant Vice President for Research and Documentation'])->pluck('organization_id')->first();
    	$event_slug = Event::create([
            'organization_id' => $organization_id,
            'event_role_id' => $data['event_role'],
            'event_category_id' => $data['event_category'],
    		'title' => $data['title'],
    		'description' => $data['description'],
    		'objective' => $data['objective'],
    		'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
    		'start_time' => $data['start_time'],
    		'end_time' => $data['end_time'],
    		'venue' => $data['venue'],
    		'activity_type' => $data['activity_type'],
    		'beneficiaries' => $data['beneficiaries'],
    		'sponsors' => $data['sponsors'],
    		'budget' => $data['budget'],
            'slug' => Str::slug($data['title'], '-') . '-' . Carbon::parse($data['start_date'])->format('Y') . '-' . Str::uuid(),
    	])->slug;
        return redirect()->route('event.show',['event_slug' => $event_slug,]);
    }
}
