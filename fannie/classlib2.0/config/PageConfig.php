<?php

namespace COREPOS\Fannie\API\config;

/**
 * Interface for building page-specific configuration options
 * @codeCoverageIgnore
 */
interface PageConfig
{
    /**
     * Build editor UI for the configuration option(s)
     * @param $config [FannieConfig] current configuration
     * @return [string] HTML input(s)
     */
    public function render($config);

    /**
     * Save new configuration values
     * @param $form [ValueContainer] values from the render'd form
     * @return null
     */
    public function update($form);
}

