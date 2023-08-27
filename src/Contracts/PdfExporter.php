<?php

namespace Insane\Journal\Contracts;

interface PdfExporter
{
    /**
     * Process the template to set the variables.
     *
     * @param  mixed  $team
     * @return void
     */
    public function process($formData);

    /**
     * preview the generated pdf inline
     * 
     */
    public function previewAs($filename);
}
