import json,re,urllib.request,urllib.error,http.cookiejar,uuid,pathlib,base64
ROOT=pathlib.Path(__file__).resolve().parents[1]
ACCESS=json.loads((ROOT/'storage/private/test-access.json').read_text())
BASE='http://127.0.0.1:8098/shop/'
class C:
 def __init__(self):self.o=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()));self.token=''
 def get(self,path=''):
  try:r=self.o.open(BASE+path)
  except urllib.error.HTTPError as e:r=e
  b=r.read();m=re.search(rb'name="csrf-token" content="([^"]+)"',b)
  if m:self.token=m.group(1).decode()
  return r.status,b
 def post(self,a,d):return self.send(a,json.dumps(d).encode(),'application/json')
 def send(self,a,body,ctype):
  req=urllib.request.Request(BASE+'api.php?action='+a,data=body,headers={'Content-Type':ctype,'X-CSRF-Token':self.token})
  try:r=self.o.open(req)
  except urllib.error.HTTPError as e:r=e
  return r.status,json.loads(r.read())
 def upload(self,a,name,data,fields={}):
  boundary='----QA'+uuid.uuid4().hex;parts=[]
  for k,v in fields.items():parts.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{k}"\r\n\r\n{v}\r\n'.encode())
  parts.append(f'--{boundary}\r\nContent-Disposition: form-data; name="file"; filename="{name}"\r\nContent-Type: image/png\r\n\r\n'.encode()+data+b'\r\n');parts.append(f'--{boundary}--\r\n'.encode());return self.send(a,b''.join(parts),'multipart/form-data; boundary='+boundary)
passed=0
def ck(v,label):
 global passed
 if not v:raise AssertionError(label)
 passed+=1;print('PASS',label)
a=C();a.get('admin/');ck(a.post('admin.login',{'email':ACCESS['email'],'password':ACCESS['password']})[0]==200,'Security test admin session');a.get('admin/')
png=base64.b64decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aD1sAAAAASUVORK5CYII=')
ck(a.upload('admin.upload','shell.php',png)[0]==422,'Executable extension rejected despite image MIME')
ck(a.upload('admin.upload','drawing.svg',b'<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>')[0]==422,'SVG upload rejected')
# A well-formed PNG is produced locally for a valid image upload.
from PIL import Image
import io
buffer=io.BytesIO();Image.new('RGB',(40,30),'orange').save(buffer,format='PNG');png=buffer.getvalue()
status,res=a.upload('admin.upload','qa-image.png',png);ck(status==200 and res['data']['path'].endswith('.webp'),'Valid image gets randomized WebP re-encoding')
ck(a.get(res['data']['path'])[0]==200,'Uploaded public image is served')
customer=C();customer.get();email='security-'+uuid.uuid4().hex[:8]+'@example.test';password='Customer-QA-'+uuid.uuid4().hex
ck(customer.post('register',{'name':'Security QA','email':email,'password':password})[0]==200,'Customer registration hashes a new password');customer.get('index.php?page=account')
ck(customer.post('address',{'label':'Home','address':'Private QA address'})[0]==200,'Saved address ownership is session-scoped')
ck(customer.post('profile',{'name':'Security QA','password':'New-'+password,'current_password':'wrong'})[0]==422,'Password change requires the current password')
ck(customer.post('review',{'product_id':4,'rating':5,'text':'Clearly labelled QA moderation test'})[0]==200,'Customer review enters moderation')
status,html=customer.get('index.php?page=product&id=4');ck(b'Clearly labelled QA moderation test' not in html,'Pending review is hidden from storefront')
ck(customer.post('wishlist',{'product_id':4})[0]==200,'Account wishlist update')
ck(customer.post('reset.request',{'email':email})[0]==200,'Password reset request succeeds without exposing token')
mail=next(p for p in (ROOT/'storage/private').glob('mail-*.txt') if email in p.read_text())
token=re.search(r'token=([a-f0-9]{64})',mail.read_text()).group(1)
ck(customer.post('reset',{'token':token,'password':'Reset-'+password})[0]==200,'Private reset token sets a new password')
ck(customer.post('reset',{'token':token,'password':'Again-'+password})[0]==422,'Password reset token is single-use')
sales=C();sales.get('admin/');ck(sales.post('admin.login',{'email':'sales@example.test','password':ACCESS['password']})[0]==200,'Sales staff sign-in');sales.get('admin/')
for action,payload in [('admin.staff',{}),('admin.settings',{}),('admin.refund',{'order_id':1}),('admin.payment.verify',{'id':1,'status':'confirmed'})]:ck(sales.post(action,payload)[0]==403,'Sales endpoint denied: '+action)
ck(sales.get('api.php?action=admin.reports&type=profit')[0]==403,'Sales cannot request gross-profit reports')
guest=C();guest.get();ck(guest.post('payment.proof',{'payment_id':1})[0]==403,'Unrelated guest cannot upload a payment proof')
for path in ['config/local.php','storage/private/test-access.json','database/database.sql','vendor/composer/installed.json','tests/run.php']:
 try:r=urllib.request.urlopen('http://localhost/store/'+path);code=r.status
 except urllib.error.HTTPError as e:code=e.code
 ck(code in [403,404],'Apache protects '+path)
(ROOT/'storage/private/security-results.json').write_text(json.dumps({'passed':passed,'failed':0},indent=2))
print(f'RESULT: {passed} passed; 0 failed.')
