<?php
class Catalog {
 static function descendants(int $id):array {$ids=[$id];$rows=all('SELECT id,parent_id FROM categories');for($i=0;$i<count($ids);$i++)foreach($rows as $r)if((int)$r['parent_id']===$ids[$i]&&!in_array((int)$r['id'],$ids,true))$ids[]=(int)$r['id'];return $ids;}
 static function search(array $d=[],bool $admin=false):array {
  $w=$admin?['1=1']:["p.status='active'","c.enabled=1"];$a=[];
  if(!$admin&&!enabled('used'))$w[]="p.condition_type='new'";if($admin&&!empty($d['status'])){$w[]='p.status=?';$a[]=$d['status'];}if(!empty($d['ids'])){$ids=array_slice(array_map('intval',(array)$d['ids']),0,100);$w[]='p.id IN ('.implode(',',array_fill(0,count($ids),'?')).')';array_push($a,...$ids);}
  if(!empty($d['q'])){$w[]='(p.name LIKE ? OR p.sku LIKE ? OR p.barcode LIKE ? OR p.tags LIKE ? OR b.name LIKE ? OR c.name LIKE ? OR EXISTS(SELECT 1 FROM product_variants sv WHERE sv.product_id=p.id AND (sv.sku LIKE ? OR sv.barcode LIKE ?)))';for($i=0;$i<8;$i++)$a[]='%'.mb_substr($d['q'],0,100).'%';}
  if(!empty($d['category'])){$ids=self::descendants((int)$d['category']);$w[]='p.category_id IN ('.implode(',',array_fill(0,count($ids),'?')).')';array_push($a,...$ids);}
  if(!empty($d['brand'])&&enabled('brands')){$w[]='p.brand_id=?';$a[]=(int)$d['brand'];}
  foreach(['featured','new_arrival','deal'] as $flag)if(!empty($d[$flag]))$w[]="p.$flag=1";
  if(!empty($d['condition'])&&enabled('used')){$w[]='p.condition_type=?';$a[]=$d['condition'];}
  $vw=['v.product_id=p.id'];if(!$admin)$vw[]='v.enabled=1';if(!$admin&&!enabled('preorders'))$vw[]="v.availability='normal'";$va=[];
  if(isset($d['min'])&&$d['min']!==''){$vw[]='COALESCE(v.sale_price,v.price)>=?';$va[]=cents($d['min']);}
  if(isset($d['max'])&&$d['max']!==''){$vw[]='COALESCE(v.sale_price,v.price)<=?';$va[]=cents($d['max']);}
  if(!empty($d['stock']))$vw[]="(v.track_stock=0 OR (v.availability='normal' AND i.physical>i.reserved))";
  if(!empty($d['preorder'])){if(!$admin)module('preorders');$vw[]="v.availability='preorder'";}
  $w[]='EXISTS(SELECT 1 FROM product_variants v JOIN inventory_balances i ON i.variant_id=v.id WHERE '.implode(' AND ',$vw).')';array_push($a,...$va);
  foreach(($d['attr']??[]) as $id=>$value)if($value!==''){$w[]='(EXISTS(SELECT 1 FROM product_attribute_values av JOIN attributes at ON at.id=av.attribute_id WHERE av.product_id=p.id AND at.filterable=1 AND at.id=? AND (av.value=? OR JSON_CONTAINS(IF(JSON_VALID(av.value),av.value,JSON_ARRAY()),JSON_QUOTE(?)))) OR EXISTS(SELECT 1 FROM variant_attribute_values vv JOIN product_variants vf ON vf.id=vv.variant_id JOIN attributes at ON at.id=vv.attribute_id JOIN attribute_options ao ON ao.id=vv.option_id WHERE vf.product_id=p.id AND vf.enabled=1 AND at.filterable=1 AND at.id=? AND ao.label=?))';array_push($a,(int)$id,(string)$value,(string)$value,(int)$id,(string)$value);}
  if(!empty($d['rating'])&&enabled('reviews')){$w[]="(SELECT AVG(rating) FROM reviews WHERE product_id=p.id AND status='approved')>=?";$a[]=(int)$d['rating'];}
  $sort=['newest'=>'p.id DESC','price_asc'=>'price ASC','price_desc'=>'price DESC','popular'=>'p.views DESC','best'=>'sold DESC','name'=>'p.name ASC'][$d['sort']??'newest']??'p.id DESC';$page=max(1,(int)($d['p']??1));$limit=min(48,max(1,(int)($d['limit']??12)));$offset=($page-1)*$limit;
  $from=' FROM products p JOIN categories c ON c.id=p.category_id LEFT JOIN brands b ON b.id=p.brand_id WHERE '.implode(' AND ',$w);
  $total=(int)one('SELECT COUNT(*) n'.$from,$a)['n'];$rows=all("SELECT p.id,p.name,p.slug,p.sku,p.category_id,p.status,p.featured,p.deal,p.new_arrival,p.condition_type,p.short_description,p.has_variants,c.name category,b.name brand,(SELECT MIN(COALESCE(price_v.sale_price,price_v.price)) FROM product_variants price_v WHERE price_v.product_id=p.id AND price_v.enabled=1) price,(SELECT path FROM product_images WHERE product_id=p.id ORDER BY sort_order,id LIMIT 1) image,(SELECT COALESCE(SUM(oi.quantity),0) FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE oi.product_id=p.id AND o.status='completed') sold,(SELECT AVG(rating) FROM reviews WHERE product_id=p.id AND status='approved') rating,(SELECT SUM(i.physical-i.reserved) FROM product_variants sv JOIN inventory_balances i ON i.variant_id=sv.id WHERE sv.product_id=p.id) stock".$from." ORDER BY $sort LIMIT $limit OFFSET $offset",$a);
  if(!$admin&&!enabled('brands'))foreach($rows as &$row)unset($row['brand']);unset($row);return ['items'=>$rows,'total'=>$total,'page'=>$page,'pages'=>(int)ceil($total/$limit)];
 }
 static function product(int $id,bool $admin=false):array {
  $p=one('SELECT p.*,c.name category,b.name brand FROM products p JOIN categories c ON c.id=p.category_id LEFT JOIN brands b ON b.id=p.brand_id WHERE p.id=?'.($admin?'':" AND p.status='active' AND c.enabled=1"),[$id]);if(!$p)fail('Product not found.',404);
  $p['variants']=all('SELECT v.*,i.physical,i.reserved,i.physical-i.reserved available FROM product_variants v JOIN inventory_balances i ON i.variant_id=v.id WHERE v.product_id=?'.($admin?'':(' AND v.enabled=1'.(!enabled('preorders')?" AND v.availability='normal'":''))),[$id]);if(!$admin&&!$p['variants'])fail('Product is not available.',404);
  $optionRows=all('SELECT vv.variant_id,vv.attribute_id,vv.option_id FROM variant_attribute_values vv JOIN product_variants v ON v.id=vv.variant_id WHERE v.product_id=?',[$id]);$optionMap=[];foreach($optionRows as $r)$optionMap[$r['variant_id']][]=['attribute_id'=>$r['attribute_id'],'option_id'=>$r['option_id']];foreach($p['variants'] as &$v){$v['options']=$optionMap[$v['id']]??[];if(!$admin||!allowed('costs'))unset($v['cost']);if(!$admin){if(!enabled('inventory'))$v['track_stock']=0;if(!enabled('advances')){$v['deposit_type']='none';$v['deposit_value']=0;}}}unset($v);
  $p['images']=all('SELECT * FROM product_images WHERE product_id=? ORDER BY sort_order,id',[$id]);$p['attributes']=all('SELECT a.*,v.value FROM product_attribute_values v JOIN attributes a ON a.id=v.attribute_id WHERE v.product_id=?'.($admin?'':' AND a.visible=1').' ORDER BY a.sort_order',[$id]);
  if(!$admin){unset($p['supplier_id']);if(!enabled('warranty'))unset($p['warranty']);if(!enabled('brands'))unset($p['brand'],$p['brand_id']);}return $p;
 }
 static function sellable(int $id,bool $lock=false):array {
  $v=one("SELECT v.*,p.name,p.status,p.category_id,p.brand_id,p.unit,p.condition_type,c.enabled category_enabled,c.name category,b.name brand,i.physical,i.reserved FROM product_variants v JOIN products p ON p.id=v.product_id JOIN categories c ON c.id=p.category_id LEFT JOIN brands b ON b.id=p.brand_id JOIN inventory_balances i ON i.variant_id=v.id WHERE v.id=?".($lock?' FOR UPDATE':''),[$id]);
  if(!$v||!$v['enabled']||$v['status']!=='active'||!$v['category_enabled'])fail('This item is no longer available.');if($v['availability']==='preorder')module('preorders');if($v['condition_type']==='used')module('used');return $v;
 }
}
