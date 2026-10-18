<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('evaluations:remind')->dailyAt('08:00');
