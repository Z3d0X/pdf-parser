<?php

use App\Http\Controllers\GoogleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Spatie\PdfToText\Pdf;

Route::get('/', function (Request $request) {
    $pdfFilePath = resource_path('data/timetable-3.pdf');

    $text = (new Pdf('/opt/homebrew/bin/pdftotext'))
        ->setPdf($pdfFilePath)
        ->setOptions([
            '-layout',
        ])
        ->text();

    $lines = Str::of($text)
        ->explode(PHP_EOL)
        ->filter()
        ->skipUntil(fn ($l) => str_contains($l, 'Updated on'))
        ->skip(1)
        ->values()
        ->chunkWhile(fn ($l) => ! str_contains($l, 'Semester'))
        ->map(function ($semTimetable) {
            $name = trim($semTimetable->shift());
            $code = trim($semTimetable->shift());

            $timeTableHeaders = $semTimetable->shift();

            $headerParts = preg_split('/\s{2,}/', trim($timeTableHeaders));

            $semTimetable = $semTimetable->map(function ($item) use ($headerParts) {
                $parts = preg_split('/\s{2,}/', trim($item));


                if (count($parts) < 6) {
                    $parts = [
                        $parts[0], // Sub Code
                        Str::beforeLast($parts[1], ' '), // Subject Description
                        Str::afterLast($parts[1], ' '), // L/T
                        $parts[2], // Lecturer
                        $parts[3], // Time
                        $parts[4], // Room
                    ];
                }

                $entry = [];

                foreach ($headerParts as $i => $header) {
                    if (isset($parts[$i])) {
                        $entry[$header] = $parts[$i];
                    }
                }

                return $entry;
            });

            return [
                'name' => $name,
                'code' => $code,
                'timetable' => $semTimetable,
            ];
        });


    if ($request->query('sem') === null) {
        dd($lines);
    }
    dd($lines->firstWhere(fn ($sem) => Str::contains($sem['name'], $request->query('sem'), true) || Str::contains($sem['code'], $request->query('sem'), true)));
});

Route::get('/oauth/google-callback', [GoogleController::class, 'handleGoogleCallback'])->name('oauth2callback');
Route::get('/google', [GoogleController::class, 'redirectToGoogle']);
Route::get('/create-event', [GoogleController::class, 'createEvent']);
