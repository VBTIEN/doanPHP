<?php

namespace App\Observers;

use App\Models\Score;
use App\Services\AverageService;

class ScoreObserver
{
    protected $averageService;

    public function __construct(AverageService $averageService)
    {
        $this->averageService = $averageService;
    }

    /**
     * Handle the Score "created" event.
     *
     * @param  \App\Models\Score  $score
     * @return void
     */
    public function created(Score $score)
    {
        $this->averageService->updateAverages($score->student_code, $score->exam_code);
    }

    /**
     * Handle the Score "updated" event.
     *
     * @param  \App\Models\Score  $score
     * @return void
     */
    public function updated(Score $score)
    {
        $this->averageService->updateAverages($score->student_code, $score->exam_code);
    }

    /**
     * Handle the Score "deleted" event.
     *
     * @param  \App\Models\Score  $score
     * @return void
     */
    public function deleted(Score $score)
    {
        $this->averageService->updateAverages($score->student_code, $score->exam_code);
    }
}