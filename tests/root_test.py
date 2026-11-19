import urllib.request,urllib.error,http.cookiejar,re,json
base='http://127.0.0.1:8099/'
op=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
body=op.open(base).read().decode();assert 'name="app-base" content="/"' in body
assert 'href="/assets/app.css?v=' in body
assert op.open(base+'assets/app.js').status==200
assert json.load(op.open(base+'api.php?action=search'))['ok']
token=re.search('name="csrf-token" content="([^"]+)"',body).group(1)
request=urllib.request.Request(base+'api.php?action=cart.change',data=json.dumps({'variant_id':17,'quantity':1}).encode(),headers={'Content-Type':'application/json','X-CSRF-Token':token})
assert json.load(op.open(request))['ok']
print('PASS domain-root asset URLs, asset serving, JSON search, and CSRF-protected cart POST')
