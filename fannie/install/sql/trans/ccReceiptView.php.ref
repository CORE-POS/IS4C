<?php
/*
View: ccReceiptView

Columns:

Depends on:
    efsnetRequest
    efsnetResponse
    efsnetRequestMod

Use:
View of transaction timing to generate
cashier performance reports
*/
$CREATE['trans.ccReceiptView'] = "
    CREATE VIEW ccReceiptView AS 
    SELECT (case when (r.mode = 'tender') then 'Credit Card Purchase' 
        when (r.mode = 'retail_sale') then 'Credit Card Purchase' 
        when (r.mode = 'Credit_Sale') then 'Credit Card Purchase' 
        when (r.mode = 'retail_credit_alone') then 'Credit Card Refund' 
        when (r.mode = 'Credit_Return') then 'Credit Card Refund' 
        when (r.mode = 'refund') then 'Credit Card Refund' 
        else '' end) AS tranType,
    (case when (r.mode = 'refund' or r.mode='retail_credit_alone') then (-(1) * r.amount) else r.amount end) AS amount,
    r.PAN AS PAN,
    (case when (r.manual = 1) then 'Manual' else 'Swiped' end) AS entryMethod,
    r.issuer AS issuer,
    r.name AS name,
    s.xResultMessage AS xResultMessage,
    s.xApprovalNumber AS xApprovalNumber,
    s.xTransactionID AS xTransactionID,
    r.date AS date,
    r.cashierNo AS cashierNo,
    r.laneNo AS laneNo,
    r.transNo AS transNo,
    r.transID AS transID,
    r.datetime AS datetime,
    0 AS sortorder from (efsnetRequest r left join efsnetResponse s 
    on(((s.date = r.date) and (s.cashierNo = r.cashierNo) 
    and (s.laneNo = r.laneNo) and (s.transNo = r.transNo) 
    and (s.transID = r.transID)))) 
    where ((s.validResponse = 1) and 
    ((s.xResultMessage like '%APPROVE%') or (s.xResultMessage like '%PENDING%'))) 
    AND r.date=DATE_FORMAT(CURDATE(),'%Y%m%d')

    union all 
    
    select 
    (case when (r.mode = 'tender') then 'Credit Card Purchase CANCELED' 
    when (r.mode = 'retail_sale') then 'Credit Card Purchase CANCELLED' 
    when (r.mode = 'Credit_Sale') then 'Credit Card Purchase CANCELLED' 
    when (r.mode = 'retail_credit_alone') then 'Credit Card Refund CANCELLED' 
    when (r.mode = 'Credit_Return') then 'Credit Card Refund CANCELLED' 
    when (r.mode = 'refund') then 'Credit Card Refund CANCELED' 
    else '' end) AS tranType,
    (case when (r.mode = 'refund' or r.mode='retail_credit_alone') then r.amount else (-(1) * r.amount) end) AS amount,
    r.PAN AS PAN,
    (case when (r.manual = 1) then 'Manual' else 'Swiped' end) AS entryMethod,
    r.issuer AS issuer,
    r.name AS name,
    s.xResultMessage AS xResultMessage,
    s.xApprovalNumber AS xApprovalNumber,
    s.xTransactionID AS xTransactionID,
    r.date AS date,
    r.cashierNo AS cashierNo,
    r.laneNo AS laneNo,
    r.transNo AS transNo,r.transID AS transID,
    r.datetime AS datetime,
    1 AS sortorder from ((efsnetRequestMod m left join efsnetRequest r 
    on(((r.date = m.date) and (r.cashierNo = m.cashierNo) 
    and (r.laneNo = m.laneNo) and (r.transNo = m.transNo) 
    and (r.transID = m.transID)))) left join efsnetResponse s 
    on(((s.date = r.date) and (s.cashierNo = r.cashierNo) 
    and (s.laneNo = r.laneNo) 
    and (s.transNo = r.transNo) 
    and (s.transID = r.transID)))) 
    where ((s.validResponse = 1) 
    and (s.xResultMessage like '%APPROVE%') and (m.validResponse = 1) 
    and ((m.xResponseCode = 0) or (m.xResultMessage like '%APPROVE%')) 
    and (m.mode = 'void'))
    AND m.date=DATE_FORMAT(CURDATE(),'%Y%m%d');
";
?>
