<?php

include(dirname(__FILE__) . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class QuickLookupsImages extends FannieRESTfulPage
{
    protected $header = 'Quick Key/Menu Images Editor';
    protected $title = 'Quick Key/Menu Images Editor';
    public $description = '[Quick Key/Menu Image Editor] manages dynamic menus images for lane QuickMenu and QuickKey plugins.';

    public function preprocess()
    {
        $this->addRoute('get<id><form>');
        return parent::preprocess();
    }

    protected function post_id_handler()
    {
        $error = 'Error uploading file';
        if (isset($_FILES['newImage']) && $_FILES['newImage']['error'] == 0) {
            $imgType = $_FILES['newImage']['type'];
            $error = 'Invalid image';
            if (substr($imgType, 0, 6) == 'image/') {
                $img = file_get_contents($_FILES['newImage']['tmp_name']);
                unlink($_FILES['newImage']['tmp_name']);
                $prep = $this->connection->prepare('UPDATE QuickLookups SET imageType=?, image=? WHERE quickLookupID=?');
                $res = $this->connection->execute($prep, array($imgType, $img, $this->id));
                $error = $res ? '' : 'Error saving image';
            }
        }

        return 'QuickLookupsImages.php?error='.$error.'&start='.$this->id;
    }

    protected function delete_id_handler()
    {
        $prep = $this->connection->prepare("UPDATE QuickLookups SET imageType=NULL, image=NULL WHERE quickLookupID=?");
        $res = $this->connection->execute($prep, array($this->id));
        $error = $res ? '' : 'Error deleting image';

        return 'QuickLookupsImages.php?error='.$error.'&start='.$this->id;
    }

    protected function get_id_form_handler()
    {
        echo <<<HTML
<p>
    <div>Current Image</div>
    <img src="QuickLookupsImages.php?id={$this->id}" />
</p>
<p>
<form method="post" action="QuickLookupsImages.php" enctype="multipart/form-data">
    <input type="hidden" name="MAX_FILE_SIZE" value="65536" />
    <input type="file" name="newImage" accept="image/*" />
    <input type="hidden" name="id" value="{$this->id}" />
    <button type="submit" class="btn btn-default">Upload New Image</button>
    <a href="QuickLookupsImages.php?_method=delete&id={$this->id}" class="btn btn-default btn-danger">Delete this Image</a>
</form>
</p>
HTML;

        return false;
    }

    protected function get_id_handler()
    {
        $prep = $this->connection->prepare('SELECT imageType, image FROM QuickLookups WHERE quickLookupID=?');
        $row = $this->connection->getRow($prep, array($this->id));
        if ($row) {
            header('Content-type: ' . $row['imageType']);
            echo $row['image'];
        }

        return false;
    }

    protected function get_view()
    {
        $opts = '';
        $res = $this->connection->query('SELECT quickLookupID, lookupSet, label FROM QuickLookups ORDER BY lookupSet, sequence');
        while ($row = $this->connection->fetchRow($res)) {
            $opts .= sprintf('<option value="%d">%d %s</option>', $row['quickLookupID'], $row['lookupSet'], $row['label']);
        }
        $this->addScript('images.js');
        $err = FormLib::get('error') ? '<div class="alert alert-danger">' . FormLib::get('error') . '</div>' : '';
        if (FormLib::get('start')) {
            $this->addOnloadCommand(sprintf("\$('#qlSel').val(%d);", FormLib::get('start')));
            $this->addOnloadCommand("\$('#qlSel').trigger('change');");
        }

        return <<<HTML
<div class="row">
    <div class="col-sm-5">
        {$err}
        <p>
        <label>Menu Entry</label>
        <select class="form-control" id="qlSel" onchange="qlImg.showForm(this.value);">
            <option value="">Select one...</option>
            {$opts}
        </select>
        </p>
    </div>
    <div class="col-sm-6" id="entryArea">
    </div>
</div>
HTML;
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertInternalType('string', $this->get_view());
        $this->id = 1;
        ob_start();
        $phpunit->assertEquals(false, $this->get_id_form_handler());
        $phpunit->assertEquals(false, $this->get_id_handler());
        ob_end_clean();
        $phpunit->assertInternalType('string', $this->post_id_handler());
        $phpunit->assertInternalType('string', $this->delete_id_handler());
    }
}

FannieDispatch::conditionalExec();

