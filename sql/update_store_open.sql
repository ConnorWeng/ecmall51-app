delimiter $$

drop procedure update_store_open$$

create procedure update_store_open(
)
begin
  start transaction;
  truncate table ecm_store_open;
  insert into ecm_store_open select * from ecm_store where state = 1 and mk_id in (select mk_id from ecm_market m where m.parent_id in (select mk_id from ecm_market_behalf) or m.mk_id in (select mk_id from ecm_market_behalf));
  commit;
end$$

delimiter ;
