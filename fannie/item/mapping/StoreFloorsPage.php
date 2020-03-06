<?php

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class StoreFloorsPage extends FannieRESTfulPage
{
    protected $header = 'Sales Floor Map';
    protected $title = 'Sales Floor Map';

    public $description = '[Sales Floor Map] has labeled map(s) of the store(s)';

    public function preprocess()
    {
        $this->addRoute('get<upload>');

        return parent::preprocess();
    }

    protected function post_handler()
    {
        $storeID = FormLib::get('store');
        $image = $_FILES['image']['tmp_name'];
        $finalname = 'floor' . $storeID . '.png';
        $finalname = __DIR__ . '/../images/done/' . $finalname;
        if (file_exists($finalname)) {
            unlink($finalname);
        }
        move_uploaded_file($image, $finalname);

        return 'StoreFloorsPage.php?id=' . $storeID;
    }

    protected function get_id_view()
    {
        $imageFile = __DIR__ . '/../images/done/floor' . $this->id . '.png';
        $imageUrl = $this->config->get('URL') . 'item/images/done/floor' . $this->id . '.png';
        if (!file_exists($imageFile)) {
            return '<div class="alert alert-danger">No image found.
                <a href="StoreFloorsPage.php?upload=1">Upload one</a>?</div>';
        }

        $model = new FloorSectionsModel($this->connection);
        $model->storeID($this->id);
        $labels = '';
        $highlight = $this->form->tryGet('section');
        foreach ($model->find() as $section) {
            $labels .= sprintf('<div class="floorlabel"
                style="position: absolute; top: %dpx; left: %dpx; %s
                transform: rotate(%ddeg); transform-origin: left top 0;">%s</div>',
                $section->mapY(),
                $section->mapX(),
                ($section->floorSectionID() == $highlight ? 'color: red; font-weight: bold;' : ''),
                $section->mapRotate(),
                $section->name()
            );
        }

        return <<<HTML
<a href="FloorSectionsPage.php" class="btn btn-default">Manage Label Positions</a>
<div style="padding: 2em;">
<div class="floorplan" style="position: relative;">
    <img src="{$imageUrl}" style="border: solid 1px black;" />
    {$labels}
</div>
</div>
HTML;
    }

    protected function get_upload_view()
    {
        $stores = FormLib::storePicker('store', false);

        return <<<HTML
<form method="post" enctype="multipart/form-data">
<div class="form-group">
    <label>Store</label>
    {$stores['html']}
</div>
<div class="form-group">
    <label>New Image</label>
    <input type="file" name="image" class="form-control" accept="image/png" />
</div>
<div class="form-group">
    <button type="submit" class="btn btn-default btn-core">Upload</button>
</div>
HTML;
    }
}

FannieDispatch::conditionalExec();

