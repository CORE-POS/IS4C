-- populate SpecialOrders with existing data --

truncate table SpecialOrders;

insert into SpecialOrders (specialOrderID, statusFlag, subStatus, notes, noteSuperID, firstName, lastName, street, city, state, zip, phone, altPhone, email)
select i.id, s.status_flag, s.sub_status, n.notes, n.superID, c.first_name, c.last_name, c.street, c.city, c.state, c.zip, c.phone, c.email_2, c.email_1 from SpecialOrderID as i left join SpecialOrderStatus as s on i.id = s.order_id left join SpecialOrderNotes as n ON i.id=n.order_id left join SpecialOrderContact as c ON i.id=c.card_no;
