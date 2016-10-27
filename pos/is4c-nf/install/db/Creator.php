<?php

namespace COREPOS\pos\install\db;
use COREPOS\pos\lib\CoreState;

class Creator
{
    private static $op_models = array(
        '\\COREPOS\\pos\\lib\\models\\op\\AutoCouponsModel',
        '\\COREPOS\\pos\\lib\\models\\op\\CouponCodesModel',
        '\\COREPOS\\pos\\lib\\models\\op\\CustdataModel',
        '\\COREPOS\\pos\\lib\\models\\op\\CustomerNotificationsModel',
        '\\COREPOS\\pos\\lib\\models\\op\\CustPreferencesModel',
        '\\COREPOS\\pos\\lib\\models\\op\\CustReceiptMessageModel',
        '\\COREPOS\\pos\\lib\\models\\op\\CustomReceiptModel',
        '\\COREPOS\\pos\\lib\\models\\op\\DateRestrictModel',
        '\\COREPOS\\pos\\lib\\models\\op\\DepartmentsModel',
        '\\COREPOS\\pos\\lib\\models\\op\\DisableCouponModel',
        '\\COREPOS\\pos\\lib\\models\\op\\DrawerOwnerModel',
        '\\COREPOS\\pos\\lib\\models\\op\\EmployeesModel',
        '\\COREPOS\\pos\\lib\\models\\op\\GlobalValuesModel',
        '\\COREPOS\\pos\\lib\\models\\op\\HouseCouponsModel',
        '\\COREPOS\\pos\\lib\\models\\op\\HouseCouponItemsModel',
        '\\COREPOS\\pos\\lib\\models\\op\\HouseVirtualCouponsModel',
        '\\COREPOS\\pos\\lib\\models\\op\\IgnoredBarcodesModel',
        '\\COREPOS\\pos\\lib\\models\\op\\MasterSuperDeptsModel',
        '\\COREPOS\\pos\\lib\\models\\op\\MemberCardsModel',
        '\\COREPOS\\pos\\lib\\models\\op\\MemtypeModel',
        '\\COREPOS\\pos\\lib\\models\\op\\ParametersModel',
        '\\COREPOS\\pos\\lib\\models\\op\\ProductsModel',
        '\\COREPOS\\pos\\lib\\models\\op\\ShrinkReasonsModel',
        '\\COREPOS\\pos\\lib\\models\\op\\SpecialDeptMapModel',
        '\\COREPOS\\pos\\lib\\models\\op\\SubDeptsModel',
        '\\COREPOS\\pos\\lib\\models\\op\\TendersModel',
        '\\COREPOS\\pos\\lib\\models\\op\\UnpaidArTodayModel',
        // depends on custdata
        '\\COREPOS\\pos\\lib\\models\\op\\MemberCardsViewModel',
    );

    /**
      Create opdata tables and views
      @param $db [SQLManager] database connection
      @param $name [string] database name
      @return [array] of error messages
    */
    public static function createOpDBs($db, $name)
    {
        $errors = array();
        if (\CoreLocal::get('laneno') == 0) {
            $errors[] = array(
                'struct' => _('No structures created for lane #0'),
                'query' => _('None'),
                'details' => _('Zero is reserved for server'),
            );

            return $errors;
        }

        foreach (self::$op_models as $class) {
            $obj = new $class($db);
            $errors[] = $obj->createIfNeeded($name);
        }
        
        $sample_data = array(
            'couponcodes',
            'customReceipt',
            'globalvalues',
            'parameters',
            'tenders',
        );

        foreach ($sample_data as $table) {
            $chk = $db->query('SELECT * FROM ' . $table, $name);
            if ($db->numRows($chk) === 0) {
                $loaded = \COREPOS\pos\install\data\Loader::loadSampleData($db, $table, true);
                if (!$loaded) {
                    $errors[] = array(
                        'struct' => $table,
                        'query' => _('None'),
                        'details' => _('Failed loading sample data'),
                    );
                }
            } else {
                $db->endQuery($chk);
            }
        }

        $chk = $db->query('SELECT drawer_no FROM drawerowner', $name);
        if ($db->num_rows($chk) == 0){
            $db->query('INSERT INTO drawerowner (drawer_no) VALUES (1)', $name);
            $db->query('INSERT INTO drawerowner (drawer_no) VALUES (2)', $name);
        }

        CoreState::loadParams();
        
        return $errors;
    }

    private static $trans_models = array(
        '\\COREPOS\\pos\\lib\\models\\trans\\DTransactionsModel',
        '\\COREPOS\\pos\\lib\\models\\trans\\LocalTransModel',
        '\\COREPOS\\pos\\lib\\models\\trans\\LocalTransArchiveModel',
        '\\COREPOS\\pos\\lib\\models\\trans\\LocalTransTodayModel',
        '\\COREPOS\\pos\\lib\\models\\trans\\LocalTempTransModel',
        '\\COREPOS\\pos\\lib\\models\\trans\\SuspendedModel',
        '\\COREPOS\\pos\\lib\\models\\trans\\TaxRatesModel',
        '\\COREPOS\\pos\\lib\\models\\trans\\CouponAppliedModel',
        '\\COREPOS\\pos\\lib\\models\\trans\\PaycardTransactionsModel',
        '\\COREPOS\\pos\\lib\\models\\trans\\CapturedSignatureModel',
        '\\COREPOS\\pos\\lib\\models\\trans\\EmvReceiptModel',
        // placeholder,
        '__LTT__',
        // Views
        '\\COREPOS\\pos\\lib\\models\\trans\\MemDiscountAddModel',
        '\\COREPOS\\pos\\lib\\models\\trans\\MemDiscountRemoveModel',
        '\\COREPOS\\pos\\lib\\models\\trans\\StaffDiscountAddModel',
        '\\COREPOS\\pos\\lib\\models\\trans\\StaffDiscountRemoveModel',
        '\\COREPOS\\pos\\lib\\models\\trans\\ScreenDisplayModel',
        '\\COREPOS\\pos\\lib\\models\\trans\\TaxViewModel',
    );

    /**
      Create translog tables and views
      @param $db [SQLManager] database connection
      @param $name [string] database name
      @return [array] of error messages
    */
    public static function createTransDBs($db, $name)
    {
        $errors = array();
        $type = $db->dbmsName();

        if (\CoreLocal::get('laneno') == 0) {
            $errors[] = array(
                'struct' => _('No structures created for lane #0'),
                'query' => _('None'),
                'details' => _('Zero is reserved for server'),
            );

            return $errors;
        }

        /* lttsummary, lttsubtotals, and subtotals
         * always get rebuilt to account for tax rate
         * changes */
        if (!function_exists('buildLTTViews')) {
            include(__DIR__ . '/../buildLTTViews.php');
        }

        foreach (self::$trans_models as $class) {
            if ($class == '__LTT__') {
                $errors = buildLTTViews($db,$type,$errors);
                continue;
            }
            $obj = new $class($db);
            $errors[] = $obj->createIfNeeded($name);
        }
        
        /**
          Not using models for receipt views. Hopefully many of these
          can go away as deprecated.
        */
        $lttR = "CREATE view rp_ltt_receipt as 
            select
            l.description as description,
            case 
                when voided = 5 
                    then 'Discount'
                when trans_status = 'M'
                    then 'Mbr special'
                when trans_status = 'S'
                    then 'Staff special'
                when unitPrice = 0.01
                    then ''
                when scale <> 0 and quantity <> 0 
                    then ".$db->concat('quantity', "' @ '", 'unitPrice','')."
                when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity = 1
                    then ".$db->concat('volume', "' / '", 'unitPrice','')."
                when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity <> 1
                    then ".$db->concat('quantity', "' @ '", 'volume', "' /'", 'unitPrice','')."
                when abs(itemQtty) > 1 and discounttype = 3
                    then ".$db->concat('ItemQtty', "' / '", 'unitPrice','')."
                when abs(itemQtty) > 1
                    then ".$db->concat('quantity', "' @ '", 'unitPrice','')."
                when matched > 0
                    then '1 w/ vol adj'
                else ''
            end
            as comment,
            total,
            case 
                when trans_status = 'V' 
                    then 'VD'
                when trans_status = 'R'
                    then 'RF'
                when tax = 1 and foodstamp <> 0
                    then 'TF'
                when tax = 1 and foodstamp = 0
                    then 'T' 
                when tax = 0 and foodstamp <> 0
                    then 'F'
                WHEN (tax > 1 and foodstamp <> 0)
                    THEN ".$db->concat('SUBSTR(t.description,1,1)',"'F'",'')."
                WHEN (tax > 1 and foodstamp = 0)
                    THEN SUBSTR(t.description,1,1)
                when tax = 0 and foodstamp = 0
                    then '' 
            end
            as Status,
            trans_type,
            unitPrice,
            voided,
            CASE 
                WHEN upc = 'DISCOUNT' THEN (
                SELECT MAX(trans_id) FROM localtemptrans WHERE voided=3
                )-1
                WHEN trans_type = 'T' THEN trans_id+99999    
                ELSE trans_id
            END AS trans_id,
            l.emp_no,
            l.register_no,
            l.trans_no
            from localtranstoday as l
            left join taxrates as t
            on l.tax = t.id
            where voided <> 5 and UPC <> 'TAX'
            AND trans_type <> 'L'";
        self::dbStructureModify($db,'rp_ltt_receipt','DROP VIEW rp_ltt_receipt',$errors);
        if(!$db->tableExists('rp_ltt_receipt',$name)){
            self::dbStructureModify($db,'rp_ltt_receipt',$lttR,$errors);
        }

        $receiptV = "CREATE VIEW rp_receipt AS
            select
            case 
                when trans_type = 'T'
                    then     ".$db->concat( "SUBSTR(".$db->concat('UPPER(TRIM(description))','space(44)','').", 1, 44)" 
                        , "right(".$db->concat( 'space(8)', 'FORMAT(-1 * total, 2)','').", 8)" 
                        , "right(".$db->concat( 'space(4)', 'status','').", 4)",'')."
                when voided = 3 
                    then     ".$db->concat("SUBSTR(".$db->concat('description', 'space(30)','').", 1, 30)"
                        , 'space(9)'
                        , "'TOTAL'"
                        , 'right('.$db->concat( 'space(8)', 'FORMAT(unitPrice, 2)','').', 8)','')."
                when voided = 2
                    then     description
                when voided = 4
                    then     description
                when voided = 6
                    then     description
                when voided = 7 or voided = 17
                    then     ".$db->concat("SUBSTR(".$db->concat('description', 'space(30)','').", 1, 30)"
                        , 'space(14)'
                        , 'right('.$db->concat( 'space(8)', 'FORMAT(unitPrice, 2)','').', 8)'
                        , 'right('.$db->concat( 'space(4)', 'status','').', 4)','')."
                else
                    ".$db->concat("SUBSTR(".$db->concat('description', 'space(30)','').", 1, 30)"
                    , "' '" 
                    , "SUBSTR(".$db->concat('comment', 'space(13)','').", 1, 13)"
                    , 'right('.$db->concat('space(8)', 'FORMAT(total, 2)','').', 8)'
                    , 'right('.$db->concat('space(4)', 'status','').', 4)','')."
            end
            as linetoprint,
            emp_no,
            register_no,
            trans_no,
            trans_id
            from rp_ltt_receipt
            order by trans_id";
        if ($type == 'mssql') {
            $receiptV = "CREATE  VIEW rp_receipt AS
            select top 100 percent
            case 
                when trans_type = 'T'
                    then     right((space(44) + upper(rtrim(Description))), 44) 
                        + right((space(8) + convert(varchar, (-1 * Total))), 8) 
                        + right((space(4) + status), 4)
                when voided = 3 
                    then     left(Description + space(30), 30) 
                        + space(9) 
                        + 'TOTAL' 
                        + right(space(8) + convert(varchar, UnitPrice), 8)
                when voided = 2
                    then     description
                when voided = 4
                    then     description
                when voided = 6
                    then     description
                when voided = 7 or voided = 17
                    then     left(Description + space(30), 30) 
                        + space(14) 
                        + right(space(8) + convert(varchar, UnitPrice), 8) 
                        + right(space(4) + status, 4)
                when sequence < 1000
                    then     description
                else
                    left(Description + space(30), 30)
                    + ' ' 
                    + left(Comment + space(13), 13) 
                    + right(space(8) + convert(varchar, Total), 8) 
                    + right(space(4) + status, 4)
            end
            as linetoprint,
            sequence,
            emp_no,
            register_no,
            trans_no,
            trans_id
            from rp_ltt_receipt
            order by sequence";
        } elseif($type == 'pdolite'){
            $receiptV = str_replace('right(','str_right(',$receiptV);
            $receiptV = str_replace('FORMAT(','ROUND(',$receiptV);
        }

        self::dbStructureModify($db,'rp_receipt','DROP VIEW rp_receipt',$errors);
        if(!$db->tableExists('rp_receipt',$name)){
            self::dbStructureModify($db,'rp_receipt',$receiptV,$errors);
        }

        return $errors;
    }

    public static function createMinServer($db, $name)
    {
        $errors = array();
        $type = $db->dbmsName();
        if (\CoreLocal::get('laneno') == 0) {
            $errors[] = array(
                'struct' => _('No structures created for lane #0'),
                'query' => _('None'),
                'details' => _('Zero is reserved for server'),
            );

            return $errors;
        }

        $models = array(
            '\COREPOS\pos\lib\models\trans\DTransactionsModel',
            '\COREPOS\pos\lib\models\trans\SuspendedModel',
            '\COREPOS\pos\lib\models\trans\PaycardTransactionsModel',
            '\COREPOS\pos\lib\models\trans\CapturedSignatureModel',
        );
        foreach ($models as $class) {
            $obj = new $class($db);
            $errors[] = $obj->createIfNeeded($name);
        }

        $errors = self::createDlog($db, $name, $errors);

        return $errors;
    }

    private static function createDlog($db, $name, $errors)
    {
        $dlogQ = "CREATE VIEW dlog AS
            SELECT datetime AS tdate,
                register_no,
                emp_no,
                trans_no,
                upc,
                CASE 
                    WHEN trans_subtype IN ('CP','IC') OR upc LIKE '%000000052' THEN 'T' 
                    WHEN upc = 'DISCOUNT' THEN 'S' 
                    ELSE trans_type 
                END AS trans_type,
                CASE 
                    WHEN upc = 'MAD Coupon' THEN 'MA' 
                    WHEN upc LIKE '%00000000052' THEN 'RR' 
                    ELSE trans_subtype 
                END AS trans_subtype,
                trans_status,
                department,
                quantity,
                unitPrice,
                total,
                tax,
                foodstamp,
                ItemQtty,
                memType,
                staff,
                numflag,
                charflag,
                card_no,
                trans_id, "
                . $db->concat(
                    $db->convert('emp_no','char'),"'-'",
                    $db->convert('register_no','char'),"'-'",
                    $db->convert('trans_no','char'),
                    '') . " AS trans_num
            FROM dtransactions
            WHERE trans_status NOT IN ('D','X','Z')
                AND emp_no <> 9999 
                AND register_no <> 99";
        if (!$db->table_exists("dlog",$name)) {
            $errors = self::dbStructureModify($db,'dlog',$dlogQ,$errors);
        }

        return $errors;
    }

    public static function dbStructureModify($sql, $struct_name, $queries, &$errors=array())
    {
        if (!is_array($queries)) {
            $queries = array($queries);
        }

        $error = array(
            'struct' => $struct_name,
            'error' => 0,
            'query' => '',
            'details' => '',
        );
        foreach ($queries as $query) {
            $try = $sql->query($query);
            if ($try === false){
                $error['query'] .= $query . '; ';
                // failing to drop a view is fine
                if (!(stristr($query, "DROP ") && stristr($query,"VIEW "))) {
                    $error['error'] = 1;
                    $error['details'] = $sql->error() . '; ';
                    $error['important'] = true;
                }
            }
        }
        $errors[] = $error;

        return $errors;
    }

}

