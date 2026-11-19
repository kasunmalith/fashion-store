<?php
if (PHP_SAPI !== 'cli') exit;
require_once dirname(__DIR__).'/app/bootstrap.php';
if (one('SELECT id FROM products LIMIT 1')) { echo "Catalog already contains products; no changes made.\n"; exit; }

$categories = ['Dresses','Tops','Bottoms','Co-ords','Occasion Wear'];
$categoryIds = [];
foreach ($categories as $i => $name) {
    q('INSERT INTO categories(name,slug,description,sort_order) VALUES(?,?,?,?)', [$name, strtolower(str_replace(' ','-',$name)), 'Original sample '.$name.' collection.', $i]);
    $categoryIds[$name] = (int)db()->lastInsertId();
}
q('INSERT INTO brands(name,description) VALUES(?,?)', ['Mira Edit','Fictional sample fashion label.']);
$brandId=(int)db()->lastInsertId();
q("INSERT INTO attributes(name,type,visible,filterable,variant) VALUES('Size','dropdown',1,1,1)");
$sizeId=(int)db()->lastInsertId();
$sizes=[];
foreach (['XS','S','M','L','XL'] as $size) { q('INSERT INTO attribute_options(attribute_id,label) VALUES(?,?)',[$sizeId,$size]); $sizes[$size]=(int)db()->lastInsertId(); }
foreach ($categoryIds as $id) q('INSERT INTO category_attributes(category_id,attribute_id) VALUES(?,?)',[$id,$sizeId]);

$items = [
 ['Luna Linen Midi Dress','Dresses',8900],['Sienna Wrap Dress','Dresses',9700],['Dusk Pleat Midi Dress','Dresses',10800],['Aster Everyday Dress','Dresses',7900],
 ['Solstice Satin Blouse','Tops',6200],['Nora Cotton Shirt','Tops',5400],['Iris Ribbed Top','Tops',3900],['Cove Tailored Vest','Tops',6900],
 ['Harbor Wide Leg Trouser','Bottoms',7600],['Moss Pleated Skirt','Bottoms',6500],['Nova Palazzo Pant','Bottoms',7200],['Cleo Relaxed Culotte','Bottoms',5900],
 ['Eden Linen Co-ord','Co-ords',12900],['Aria Knit Co-ord','Co-ords',11200],['Vale Weekend Set','Co-ords',9900],['Olive Summer Set','Co-ords',10500],
 ['Nocturne Evening Gown','Occasion Wear',18900],['Pearl Occasion Jumpsuit','Occasion Wear',14900],['Serene Draped Dress','Occasion Wear',16900],['Ember Statement Dress','Occasion Wear',17500],
];
tx(function() use ($items,$categoryIds,$brandId,$sizeId,$sizes) {
 foreach ($items as $i=>$item) {
  [$name,$category,$price]=$item;
  $slug=strtolower(preg_replace('/[^a-z0-9]+/','-',strtolower($name)));
  $sku='MIRA-'.str_pad((string)($i+1),3,'0',STR_PAD_LEFT);
  $image='assets/fashion-'.str_pad((string)($i+1),2,'0',STR_PAD_LEFT).'.webp';
  $summary='An original sample piece for the Mira Edit collection.';
  q('INSERT INTO products(name,slug,sku,category_id,brand_id,short_description,description,status,featured,new_arrival,deal,has_variants,tags) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)',[$name,$slug,$sku,$categoryIds[$category],$brandId,$summary,$summary.' This item, description, price, imagery and stock are demonstration content.','active',1,(int)($i<8),(int)($i%6===0),1,'sample fashion']);
  $pid=(int)db()->lastInsertId();
  q('INSERT INTO product_images(product_id,path,alt) VALUES(?,?,?)',[$pid,$image,'Original sample fashion image for '.$name]);
  foreach ($sizes as $size=>$optionId) {
   $combination=json_encode([$sizeId=>$optionId]);
   $value=$price*100;
   q('INSERT INTO product_variants(product_id,sku,label,combination,price,sale_price,cost,image) VALUES(?,?,?,?,?,?,?,?)',[$pid,$sku.'-'.$size,'Size: '.$size,$combination,$value,$i%6===0?intdiv($value*85,100):null,intdiv($value*60,100),$image]);
   $vid=(int)db()->lastInsertId();
   q('INSERT INTO variant_attribute_values(variant_id,attribute_id,option_id) VALUES(?,?,?)',[$vid,$sizeId,$optionId]);
   q('INSERT INTO inventory_balances(variant_id,physical) VALUES(?,?)',[$vid,10]);
   Commerce::movement($vid,10,'initial',null,'Sample catalog opening stock');
  }
 }
 q('INSERT INTO hero_slides(heading,description,image,primary_label,primary_url,secondary_label,secondary_url) VALUES(?,?,?,?,?,?,?)',['Style in every\nmoment.','Modern pieces made for wherever the day takes you.','assets/fashion-hero.webp','Shop the collection','shop/','New arrivals','shop/?new_arrival=1']);
 foreach ([['New season edit','Fresh silhouettes for today'],['Everyday ease','Pieces to wear on repeat'],['Occasion ready','Dress for the moment'],['Made to explore','Find your new favourite']] as $i=>$b) q("INSERT INTO content_blocks(type,title,body,sort_order) VALUES('benefit',?,?,?)",[$b[0],$b[1],$i]);
 foreach (['Dresses','Tops','Co-ords','Occasion Wear'] as $i=>$cat) q("INSERT INTO content_blocks(type,title,body,image,link,sort_order) VALUES('promotion',?,?,?,?,?)",[$cat,'Discover the edit','assets/fashion-'.str_pad((string)($i*4+1),2,'0',STR_PAD_LEFT).'.webp','category/'.strtolower(str_replace(' ','-',$cat)).'/',$i]);
 foreach ([['New arrivals','new_arrival',8],['The latest edit','featured',8],['Shop by category','categories',5],['Explore every style','featured',8]] as $i=>$s) q('INSERT INTO homepage_sections(title,type,selection,item_limit,sort_order) VALUES(?,?,?,?,?)',[$s[0],$s[1],'[]',$s[2],$i]);
 foreach (['privacy'=>'Privacy policy','terms'=>'Terms and conditions','returns'=>'Returns policy','shipping'=>'Delivery information'] as $slug=>$title) q('INSERT INTO pages(slug,title,body) VALUES(?,?,?)',[$slug,$title,'Sample policy content. Replace this text with your approved business policy before accepting real orders.']);
});
echo "Seeded 20 original sample products, 100 size variants, categories and homepage content.\n";
