<?php

namespace App\Http\Controllers;

use App\Models\Conversion;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ConversionController extends Controller
{
    public function index()
    {
        $currentEpochTimestamp = now()->timestamp;
        $conversions = Conversion::latest()->get();

        return view('conversions.index', [
            'currentEpochTimestamp' => $currentEpochTimestamp,
            'conversions' => $conversions,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'input_datetime' => 'required|date_format:Y-m-d\TH:i',
        ]);

        $inputDatetime = Carbon::parse($validated['input_datetime']);
        $epochTimestamp = $inputDatetime->timestamp;

        Conversion::create([
            'input_datetime' => $inputDatetime,
            'epoch_timestamp' => $epochTimestamp,
        ]);

        return redirect()->route('conversions.index');
    }
}