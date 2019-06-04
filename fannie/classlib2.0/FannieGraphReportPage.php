<?php

namespace COREPOS\Fannie\API;

/**
 * @FannieGraphReportPage
 *
 * Extends a standard report with some setup to
 * add graph(s) below the table
 *
 * Auto-includes graphing JS libs in HTML format
 * Includes provided HTML and/or JS in HTML format
 */
class FannieGraphReportPage extends \FannieReportPage
{
    public function preprocess()
    {
        parent::preprocess();
        // custom: needs graphing JS/CSS
        if ($this->content_function == 'report_content' && $this->report_format == 'html') {
            $url = $this->config->get('URL');
            $this->addScript($url . 'src/javascript/Chart.min.js');
            $this->addScript($url . 'src/javascript/CoreChart.js');
        }

        return true;
    }

    public function report_content() {
        $default = parent::report_content();
        if ($this->report_format == 'html') {
            $default .=
                '<div class="row">' . $this->graphHTML() . '</div>
                <script type="text/javascript">' . $this->graphJS() . '</script>'; 
        }

        return $default;
    }

    /**
     * HTML content for the graph(s). Will be
     * below the report table
     * @return [string]
     */
    public function graphHTML()
    {
        return '';
    }

    /**
     * JS content for creating the graph(s). Can add a .js script
     * instead of course.
     * @return [string]
     */
    public function graphJS()
    {
        '';
    }
}

