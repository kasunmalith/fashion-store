"""HTTP workflow checks against the isolated PHP test server on port 8098."""
import json,re,urllib.request,urllib.error,http.cookiejar,uuid,pathlib,sys
ROOT=pathlib.Path(__file__).resolve().parents[1]
access=json.loads((ROOT/'storage/private/test-access.json').read_text())
BASE='http://127.0.0.1:8098/shop/'
passed=failed=0
class Client:
 def __init__(self):
  self.jar=http.cookiejar.CookieJar();self.opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.jar));self.csrf=''
 def page(self,path=''):
  r=self.opener.open(BASE+path);body=r.read();m=re.search(rb'name="csrf-token" content="([^"]+)"',body)
  if m:self.csrf=m.group(1).decode()
  return r.status,body
 def api(self,action,data=None,csrf=True):
  req=urllib.request.Request(BASE+'api.php?action='+action,data=None if data is None else json.dumps(data).encode(),headers={} if data is None else {'Content-Type':'application/json',**({'X-CSRF-Token':self.csrf} if csrf else {})})
  try:r=self.opener.open(req)
  except urllib.error.HTTPError as e:r=e
  body=r.read()
  try:return r.status,json.loads(body)
  except Exception:raise RuntimeError(f'Non-JSON response {r.status}: {body[:200]!r}')
def check(ok,label):
 global passed,failed
 if ok:passed+=1;print('PASS',label)
 else:failed+=1;print('FAIL',label)
def key():return str(uuid.uuid4())
admin=Client();guest=Client();other=Client();admin.page('admin/');guest.page();other.page()
check(guest.api('admin.list&entity=orders')[0]==401,'Anonymous admin endpoint is denied')
check(guest.api('cart.change',{'variant_id':1,'quantity':1},False)[0]==419,'CSRF is required for mutations')
check(guest.api('cart.change')[0]==405,'GET cannot mutate cart')
check(admin.api('admin.login',{'email':access['email'],'password':access['password']})[0]==200,'Staff AJAX sign-in')
admin.page('admin/')
for entity in ['products','categories','attributes','attribute_options','brands','suppliers','orders','customers','payments','invoices','purchase_receipts','reviews','coupons','hero_slides','homepage_sections','content_blocks','pages','staff_users','audit_logs']:
 check(admin.api('admin.list&entity='+entity)[0]==200,'Admin data list '+entity)
_,res=admin.api('admin.product&id=4');p=res['data'];v=p['variants'][0]
check(len(v['options'])==2,'AJAX product provides size/color options')
suffix=uuid.uuid4().hex[:8]
product={'name':'HTTP QA Product '+suffix,'slug':'http-qa-'+suffix,'sku':'HTTP-QA-'+suffix,'category_id':9,'status':'active','unit':'pack','condition_type':'new','has_variants':0,'attributes':{},'images':[{'path':'assets/rice.svg','alt':'QA rice'}],'variants':[{'sku':'HTTP-QA-V-'+suffix,'price':'1000.01','cost':'300.00','stock':6,'options':{}}]}
code,res=admin.api('admin.product.save',product);check(code==200,'Create product through AJAX');pid=res['data']['id']
_,res=guest.api('product&id='+str(pid));public=res['data'];vid=public['variants'][0]['id'];check(public['name']==product['name'] and 'cost' not in public['variants'][0] and 'supplier_id' not in public,'Created product is public without private fields')
product['id']=pid;product['variants'][0]['id']=vid;product['variants'][0]['price']='2000.02';product['images']=[{'path':'assets/shirt.svg','alt':'Changed QA image'}]
check(admin.api('admin.product.save',product)[0]==200,'Edit price and image through AJAX')
_,res=guest.api('product&id='+str(pid));check(res['data']['variants'][0]['price']==200002 and res['data']['images'][0]['path']=='assets/shirt.svg','Storefront product reflects edited values')
check(admin.api('admin.stock',{'variant_id':vid,'delta':2,'reason':'HTTP test'})[0]==200,'AJAX stock adjustment')
_,res=guest.api('product&id='+str(pid));check(res['data']['variants'][0]['available']==8,'Adjusted stock appears publicly')
check(guest.api('cart.change',{'variant_id':vid,'quantity':2,'mode':'add'})[1]['data']['count']==2,'AJAX add cart')
check(guest.api('cart.change',{'variant_id':vid,'quantity':1})[1]['data']['total']==200002,'AJAX quantity totals')
check(guest.api('cart.change',{'variant_id':vid,'quantity':0})[1]['data']['count']==0,'AJAX remove cart')
guest.api('cart.change',{'variant_id':vid,'quantity':1})
checkout={'name':'HTTP QA Buyer','email':'httpqa@example.test','phone':'0000000000','billing_address':'Test address','shipping_address':'Test address','delivery':'collection','payment_method':'bank_transfer','policy':1,'idempotency_key':key()}
code,res=guest.api('checkout',checkout);check(code==200,'Database-backed AJAX checkout');oid=res['data']['id'];token=res['data']['access_token']
check(guest.api('checkout',checkout)[1]['data']['id']==oid,'HTTP checkout retry returns same order')
check(other.api('order&id='+str(oid))[0]==403,'Another browser session cannot read order')
_,order=guest.api('order&id='+str(oid));payment=order['data']['payments'][0];check(order['data']['outstanding']==200002 and payment['status']=='pending','Customer payment remains unverified')
check(admin.api('admin.payment.verify',{'id':payment['id'],'status':'confirmed'})[0]==200,'Staff verifies payment')
admin.api('admin.payment.verify',{'id':payment['id'],'status':'confirmed'})
_,order=guest.api('order&id='+str(oid));check(order['data']['paid']==200002,'Repeated verification credits once')
code,pdf=guest.page('invoice.php?id='+str(oid)+'&pdf=1');check(code==200 and pdf.startswith(b'%PDF-'),'Download is a real PDF')
(ROOT/'storage/private/qa-invoice.pdf').write_bytes(pdf)
try:other.page('invoice.php?id='+str(oid)+'&pdf=1');denied=False
except urllib.error.HTTPError as e:denied=e.code==403
check(denied,'Invoice ownership is enforced')
for state in ['confirmed','processing','ready','completed','completed']:
 check(admin.api('admin.order.status',{'id':oid,'status':state,'notes':'HTTP test'})[0]==200,'Order workflow '+state)
for report in ['daily','monthly','yearly','product','category','brand','customer','collections','outstanding','preorders','inventory','low_stock','movements','best','profit']:
 check(admin.api('admin.reports&type='+report+'&from=2000-01-01&to=2030-12-31')[0]==200,'Report '+report)
code,receipt=guest.page('invoice.php?id='+str(oid)+'&receipt='+str(payment['id'])+'&pdf=1');check(receipt.startswith(b'%PDF-'),'Confirmed payment receipt PDF')
for path in ['', 'index.php?page=shop','index.php?page=product&id=4','index.php?page=cart','index.php?page=checkout','index.php?page=login','index.php?page=register','index.php?page=track','index.php?page=contact','index.php?page=policy&slug=privacy']:
 check(guest.page(path)[0]==200,'Customer route '+(path or 'home'))
check(guest.api('product&id='+str(pid))[1]['data']['variants'][0]['available']==7,'Fulfilled HTTP order deducts once')
# The same router supports a recommended domain-root public deployment.
oldbase=BASE;BASE='http://127.0.0.1:8098/';check(guest.page('')[0]==200,'Root document entry point resolves');BASE=oldbase
(ROOT/'storage/private/http-results.json').write_text(json.dumps({'passed':passed,'failed':failed,'order_id':oid,'token':token,'product_id':pid},indent=2))
print(f'RESULT: {passed} passed; {failed} failed.');sys.exit(bool(failed))
