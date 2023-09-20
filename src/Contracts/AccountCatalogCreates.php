<?php

namespace Insane\Journal\Contracts;

use App\Models\Team;

interface AccountCatalogCreates
{
    public function createChart(Team $team);
    public function createCatalog(Team $team);
}
