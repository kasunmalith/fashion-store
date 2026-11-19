<?php
if(PHP_SAPI!=='cli')exit;
require_once dirname(__DIR__).'/app/bootstrap.php';
q('CREATE TABLE IF NOT EXISTS migrations (version VARCHAR(100) PRIMARY KEY, applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB');
foreach(glob(ROOT.'/database/migrations/*.sql') as $file){$name=basename($file);if(one('SELECT version FROM migrations WHERE version=?',[$name]))continue;db()->exec(file_get_contents($file));q('INSERT INTO migrations(version) VALUES(?)',[$name]);echo "Applied $name\n";}
$defaults=['store_name'=>'Mira Edit','legal_name'=>'Mira Edit Demonstration Store','tagline'=>'Modern fashion for every moment.','address'=>'Demonstration address — configure before launch','phone'=>'','email'=>'','logo'=>'','currency'=>'LKR','currency_symbol'=>'Rs.','timezone'=>'Asia/Colombo','top_message'=>'NEW SEASON EDIT · ORIGINAL SAMPLE COLLECTION','footer_text'=>'Demo store: all products, images, prices and stock are original sample content.','bank_details'=>'Configure verified bank instructions before accepting transfers.','invoice_note'=>'Payment is confirmed only after staff verification.','site_origin'=>'http://localhost','tax_basis_points'=>0,'reservation_minutes'=>1440,'delivery_zones'=>['Demo local zone'=>50000],'free_shipping_threshold'=>0,'social_links'=>[]];foreach(['inventory','preorders','advances','wishlist','reviews','brands','suppliers','coupons','delivery','accounts','used','warranty','purchases'] as $m)$defaults['module_'.$m]=true;$defaults['module_online']=false;
foreach($defaults as $k=>$v)q('INSERT IGNORE INTO settings(name,value) VALUES(?,?)',[$k,json_encode($v)]);
echo "Setup complete. No existing data was erased.\n";

