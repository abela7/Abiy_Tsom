<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lectionary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LectionaryController extends Controller
{
    /** @var array<int, string> */
    private const MONTH_NAMES = [
        1 => 'Meskerem / መስከረም', 2 => 'Tikimt / ጥቅምት',   3 => 'Hidar / ኅዳር',
        4 => 'Tahsas / ታኅሣሥ',   5 => 'Tir / ጥር',          6 => 'Yekatit / የካቲት',
        7 => 'Megabit / መጋቢት',  8 => 'Miyazia / ሚያዝያ',    9 => 'Ginbot / ግንቦት',
        10 => 'Sene / ሰኔ',      11 => 'Hamle / ሐምሌ',       12 => 'Nehase / ነሐሴ',
        13 => 'Pagumen / ጳጉሜን',
    ];

    public function index(Request $request): View
    {
        $selectedMonth = max(1, min(13, (int) $request->query('month', 6)));
        $selectedDay   = max(0, min(30, (int) $request->query('day', 0)));

        // Which days in the selected month already have entries
        $filledDays = Lectionary::where('month', $selectedMonth)
            ->pluck('day')
            ->toArray();

        // Total entries across all months
        $totalCount = Lectionary::count();

        // Load the entry for the selected day (if a day is chosen)
        $entry = null;
        if ($selectedDay > 0) {
            $entry = Lectionary::where('month', $selectedMonth)
                ->where('day', $selectedDay)
                ->first();
        }

        $maxDay = $selectedMonth === 13 ? 6 : 30;

        return view('admin.lectionary.index', [
            'selectedMonth' => $selectedMonth,
            'selectedDay'   => $selectedDay,
            'filledDays'    => $filledDays,
            'totalCount'    => $totalCount,
            'entry'         => $entry,
            'monthNames'    => self::MONTH_NAMES,
            'maxDay'        => $maxDay,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        Lectionary::create($data);

        return redirect($this->redirectUrl($data['month'], $data['day']))
            ->with('success', __('app.lectionary_saved'));
    }

    public function update(Request $request, Lectionary $lectionary): RedirectResponse
    {
        $data = $this->validated($request, withDate: false);

        $lectionary->update($data);

        return redirect($this->redirectUrl($lectionary->month, $lectionary->day))
            ->with('success', __('app.lectionary_saved'));
    }

    public function destroy(Lectionary $lectionary): RedirectResponse
    {
        $month = $lectionary->month;
        $day   = $lectionary->day;
        $lectionary->delete();

        return redirect($this->redirectUrl($month, $day))
            ->with('success', __('app.lectionary_deleted'));
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, bool $withDate = true): array
    {
        $rules = [
            // Pauline
            'pauline_book_am'  => ['nullable', 'string', 'max:100'],
            'pauline_book_en'  => ['nullable', 'string', 'max:100'],
            'pauline_chapter'  => ['nullable', 'integer', 'min:1', 'max:150'],
            'pauline_verses'   => ['nullable', 'string', 'max:30'],
            'pauline_text_am'  => ['nullable', 'string'],
            'pauline_text_en'  => ['nullable', 'string'],
            // Catholic
            'catholic_book_am' => ['nullable', 'string', 'max:100'],
            'catholic_book_en' => ['nullable', 'string', 'max:100'],
            'catholic_chapter' => ['nullable', 'integer', 'min:1', 'max:150'],
            'catholic_verses'  => ['nullable', 'string', 'max:30'],
            'catholic_text_am' => ['nullable', 'string'],
            'catholic_text_en' => ['nullable', 'string'],
            // Acts
            'acts_chapter'     => ['nullable', 'integer', 'min:1', 'max:28'],
            'acts_verses'      => ['nullable', 'string', 'max:30'],
            'acts_text_am'     => ['nullable', 'string'],
            'acts_text_en'     => ['nullable', 'string'],
            // Mesbak
            'mesbak_psalm'     => ['nullable', 'integer', 'min:1', 'max:151'],
            'mesbak_verses'    => ['nullable', 'string', 'max:30'],
            'mesbak_text_geez' => ['nullable', 'string'],
            'mesbak_text_am'   => ['nullable', 'string'],
            'mesbak_text_en'   => ['nullable', 'string'],
            // Gospel
            'gospel_book_am'   => ['nullable', 'string', 'max:100'],
            'gospel_book_en'   => ['nullable', 'string', 'max:100'],
            'gospel_chapter'   => ['nullable', 'integer', 'min:1', 'max:28'],
            'gospel_verses'    => ['nullable', 'string', 'max:30'],
            'gospel_text_am'   => ['nullable', 'string'],
            'gospel_text_en'   => ['nullable', 'string'],
            // Qiddase
            'qiddase_am'       => ['nullable', 'string', 'max:300'],
            'qiddase_en'       => ['nullable', 'string', 'max:300'],
        ];

        if ($withDate) {
            $rules['month'] = ['required', 'integer', 'min:1', 'max:13'];
            $rules['day']   = ['required', 'integer', 'min:1', 'max:30'];
        }

        return $request->validate($rules);
    }

    private function redirectUrl(int $month, int $day): string
    {
        return "/admin/lectionary?month={$month}&day={$day}";
    }
}
