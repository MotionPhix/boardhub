<?php

namespace App\Http\Controllers;

use App\Events\BillboardBookingRequested;
use App\Http\Requests\BookingRequest;
use App\Models\Billboard;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Thunk\Verbs\Facades\Verbs;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $bookings = Booking::with(['billboard', 'client'])
            ->when($request->user(), function ($query) use ($request) {
                return $query->where('client_id', $request->user()->id);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('Bookings/Index', [
            'bookings' => $bookings,
        ]);
    }

    public function store(BookingRequest $request)
    {
        $billboard = Billboard::findOrFail($request->billboard_id);

        // Generate unique booking ID
        $bookingId = Str::uuid()->toString();

        try {
            // Fire the booking event using Verbs
            BillboardBookingRequested::commit(
                billboard_id: $billboard->id,
                booking_id: $bookingId,
                client_id: auth()->id(),
                start_date: $request->start_date,
                end_date: $request->end_date,
                requested_price: $request->requested_price ?? $billboard->price,
                campaign_details: $request->campaign_details
            );

            return redirect()->route('bookings.show', $bookingId)
                ->with('success', 'Booking request submitted successfully!');

        } catch (\Exception $e) {
            return back()->withErrors([
                'booking' => $e->getMessage()
            ]);
        }
    }

    public function show(string $id)
    {
        $booking = Booking::with(['billboard', 'client'])->findOrFail($id);

        // Load booking state for real-time information
        $bookingState = $booking->getBookingState();

        return Inertia::render('Bookings/Show', [
            'booking' => $booking,
            'bookingState' => [
                'price_negotiations' => $bookingState->price_negotiations,
                'status_history' => $bookingState->status_history,
                'duration_days' => $bookingState->getDurationInDays(),
                'is_active' => $bookingState->isActive(),
            ],
        ]);
    }

    public function quickBook(Request $request)
    {
        $request->validate([
            'billboard_id' => 'required|exists:billboards,id',
        ]);

        $billboard = Billboard::findOrFail($request->billboard_id);

        return Inertia::render('Bookings/QuickBook', [
            'billboard' => $billboard,
            'suggestedDates' => [
                'start_date' => now()->addWeek()->format('Y-m-d'),
                'end_date' => now()->addWeeks(5)->format('Y-m-d'),
            ],
        ]);
    }
}
